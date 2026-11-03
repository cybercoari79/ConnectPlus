<?php
declare(strict_types=1);
session_start();

/*
 * ATX 3.0 — Analisador + Substituidor de URL Smali
 * PHP 7.4+ recomendado. Mantém a análise em memória e só gera um arquivo
 * modificado quando o usuário solicita a substituição.
 */

$error = '';
$success = '';
$report = [];
$sourceName = '';
$source = '';
$modifiedInfo = '';

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function decodeArrayData(string $block): string {
    preg_match_all('/0x([0-9a-fA-F]{1,2})t\b/i', $block, $m);
    $out = '';
    foreach ($m[1] as $hex) {
        $out .= chr(hexdec($hex));
    }
    return $out;
}

function bytesOf(string $s): array {
    $out = [];
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $out[] = ord($s[$i]);
    }
    return $out;
}

function makeArrayData(string $s, string $newline = "\n"): string {
    $bytes = bytesOf($s);
    $lines = [];
    foreach (array_chunk($bytes, 8) as $chunk) {
        $vals = [];
        foreach ($chunk as $v) {
            $vals[] = sprintf('0x%02xt', $v);
        }
        $lines[] = '    ' . implode(' ', $vals);
    }
    return '.array-data 1' . $newline
        . implode($newline, $lines) . $newline
        . '.end array-data';
}

function analyzeSmali(string $content): array {
    $items = [];
    preg_match_all(
        '/(?<label>^[ \t]*:[A-Za-z0-9_.$-]+[ \t]*\R[ \t]*)(?<block>\.array-data\s+1\b.*?\.end\s+array-data\b)/ims',
        $content,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    foreach ($matches['block'] as $i => $m) {
        $block = $m[0];
        $offset = $m[1];
        $text = decodeArrayData($block);
        $line = substr_count(substr($content, 0, $offset), "\n") + 1;
        $items[] = [
            'index' => $i + 1,
            'label' => trim($matches['label'][$i][0]),
            'line' => $line,
            'bytes' => strlen($text),
            'text' => $text,
            'is_url' => preg_match('~https?://~i', $text) === 1,
            'block_start' => $offset,
            'block_length' => strlen($block),
            'block' => $block,
        ];
    }
    return $items;
}

/**
 * Locate the array register used by fill-array-data, its preceding new-array,
 * and the register that supplies the array size. This is more reliable than
 * assuming the array register and size register are identical.
 */
function locateArraySize(string $content, string $label): array {
    $fillPattern = '/fill-array-data\s+(v\d+|p\d+)\s*,\s*'
        . preg_quote($label, '/') . '\b/';
    if (!preg_match($fillPattern, $content, $fm, PREG_OFFSET_CAPTURE)) {
        throw new RuntimeException("Não encontrei fill-array-data para {$label}.");
    }

    $arrayReg = $fm[1][0];
    $fillPos = $fm[0][1];
    $beforeFill = substr($content, 0, $fillPos);

    // Find the closest preceding new-array that writes to the fill-array-data register.
    $newPattern = '/new-array\s+' . preg_quote($arrayReg, '/')
        . '\s*,\s*(v\d+|p\d+)\s*,\s*\[B\b/';
    if (!preg_match_all($newPattern, $beforeFill, $nm, PREG_OFFSET_CAPTURE) || empty($nm[0])) {
        throw new RuntimeException("Não encontrei o new-array que cria {$arrayReg}.");
    }

    $last = count($nm[0]) - 1;
    $newArrayPos = $nm[0][$last][1];
    $sizeReg = $nm[1][$last][0];
    $beforeNewArray = substr($content, 0, $newArrayPos);

    // The latest const for the size register before new-array is the candidate.
    $constPattern = '/\b(const(?:\/16|\/4|\/high16)?|const-wide(?:\/16|\/32|\/high16)?)\s+'
        . preg_quote($sizeReg, '/') . '\s*,\s*(0x[0-9a-fA-F]+|\d+)\b/';
    if (!preg_match_all($constPattern, $beforeNewArray, $cm, PREG_OFFSET_CAPTURE) || empty($cm[0])) {
        throw new RuntimeException("Não encontrei uma instrução const para o registrador de tamanho {$sizeReg} antes de new-array.");
    }

    $ci = count($cm[0]) - 1;
    $literal = $cm[2][$ci][0];
    $size = preg_match('/^0x/i', $literal) ? hexdec($literal) : (int)$literal;

    return [
        'array_reg' => $arrayReg,
        'size_reg' => $sizeReg,
        'declared_size' => $size,
        'const_start' => $cm[0][$ci][1],
        'const_text' => $cm[0][$ci][0],
        'new_array_pos' => $newArrayPos,
        'fill_pos' => $fillPos,
    ];
}

function replaceUrl(string $content, string $oldUrl, string $newUrl): array {
    if ($oldUrl === '' || $newUrl === '') {
        throw new RuntimeException('Informe a URL antiga e a URL nova.');
    }
    if (strlen($newUrl) > 255) {
        throw new RuntimeException('A URL nova ultrapassa 255 bytes; esta ferramenta não altera arrays de largura maior.');
    }

    $items = analyzeSmali($content);
    $matches = [];
    foreach ($items as $item) {
        if ($item['text'] === $oldUrl) {
            $matches[] = $item;
        }
    }

    if (count($matches) !== 1) {
        if (count($matches) > 1) {
            throw new RuntimeException('A URL antiga aparece em mais de um array. Nada foi alterado.');
        }
        throw new RuntimeException('A URL antiga não foi encontrada exatamente em um único .array-data 1.');
    }

    $item = $matches[0];
    $label = preg_replace('/\s+/', '', $item['label']);
    $layout = locateArraySize($content, $label);

    $newBlock = makeArrayData($newUrl);
    $modified = substr($content, 0, $item['block_start'])
        . $newBlock
        . substr($content, $item['block_start'] + $item['block_length']);

    // Adjust the const literal used as the size argument to new-array.
    // Re-find the const by its original text in the modified content, before new-array.
    $newLayout = locateArraySize($modified, $label);
    $constText = $newLayout['const_text'];
    $newConst = 'const/16 ' . $newLayout['size_reg'] . ', ' . strlen($newUrl);
    $modified = substr($modified, 0, $newLayout['const_start'])
        . $newConst
        . substr($modified, $newLayout['const_start'] + strlen($constText));

    // Verify the resulting payload and the declared size.
    $after = analyzeSmali($modified);
    $verified = [];
    foreach ($after as $candidate) {
        if ($candidate['label'] === $item['label'] && $candidate['text'] === $newUrl) {
            $verified[] = $candidate;
        }
    }
    if (count($verified) !== 1) {
        throw new RuntimeException('A validação final do bloco substituído falhou.');
    }
    $finalLayout = locateArraySize($modified, $label);
    if ($finalLayout['declared_size'] !== strlen($newUrl)) {
        throw new RuntimeException('O tamanho declarado não corresponde ao número de bytes da URL nova.');
    }

    return [
        'content' => $modified,
        'label' => $label,
        'array_reg' => $finalLayout['array_reg'],
        'size_reg' => $finalLayout['size_reg'],
        'old_size' => $layout['declared_size'],
        'new_size' => strlen($newUrl),
        'backup_content' => $content,
    ];
}

function readInput(): array {
    $content = '';
    $name = 'Texto colado';

    if (isset($_FILES['smali']) && is_array($_FILES['smali']) &&
        ($_FILES['smali']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['smali']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no envio do arquivo. Verifique o limite de upload do PHP.');
        }
        if ((int)($_FILES['smali']['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('O arquivo ultrapassa o limite de 5 MB.');
        }
        $tmp = (string)$_FILES['smali']['tmp_name'];
        $read = file_get_contents($tmp);
        if ($read === false || $read === '') {
            throw new RuntimeException('Não foi possível ler o arquivo ou ele está vazio.');
        }
        $content = $read;
        $name = basename((string)$_FILES['smali']['name']);
    } else {
        $content = (string)($_POST['smali_text'] ?? '');
        if (trim($content) === '') {
            throw new RuntimeException('Envie um arquivo Smali ou cole o conteúdo.');
        }
    }

    return [$name, $content];
}

if (isset($_GET['download'])) {
    $path = (string)($_SESSION['atx_download_path'] ?? '');
    $name = (string)($_SESSION['atx_download_name'] ?? 'atx_modificado.smali');
    if ($path !== '' && is_file($path)) {
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }
    http_response_code(404);
    exit('Arquivo não encontrado ou sessão expirada.');
}

if (isset($_GET['download_backup'])) {
    $path = (string)($_SESSION['atx_backup_path'] ?? '');
    $name = (string)($_SESSION['atx_backup_name'] ?? 'atx_original.bak');
    if ($path !== '' && is_file($path)) {
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }
    http_response_code(404);
    exit('Backup não encontrado ou sessão expirada.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        [$sourceName, $source] = readInput();
        $action = (string)($_POST['action'] ?? 'analyze');
        if ($action === 'replace') {
            $result = replaceUrl(
                $source,
                trim((string)($_POST['old_url'] ?? '')),
                trim((string)($_POST['new_url'] ?? ''))
            );

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($sourceName));
            $stamp = date('Ymd_His');
            $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'atx3_' . session_id();
            if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
                throw new RuntimeException('Não foi possível criar a pasta temporária privada.');
            }
            $outPath = $base . DIRECTORY_SEPARATOR . pathinfo($safeName, PATHINFO_FILENAME) . '_modificado_' . $stamp . '.smali';
            $backupPath = $base . DIRECTORY_SEPARATOR . pathinfo($safeName, PATHINFO_FILENAME) . '_original_' . $stamp . '.bak';

            if (file_put_contents($backupPath, $result['backup_content'], LOCK_EX) === false) {
                throw new RuntimeException('Não foi possível gravar o backup.');
            }
            if (file_put_contents($outPath, $result['content'], LOCK_EX) === false) {
                @unlink($backupPath);
                throw new RuntimeException('Não foi possível gerar o Smali modificado.');
            }

            $_SESSION['atx_download_path'] = $outPath;
            $_SESSION['atx_download_name'] = basename($outPath);
            $_SESSION['atx_backup_path'] = $backupPath;
            $_SESSION['atx_backup_name'] = basename($backupPath);

            $modifiedInfo = "Arquivo: {$sourceName}\n"
                . "Bloco: {$result['label']}\n"
                . "Registrador do array: {$result['array_reg']}\n"
                . "Registrador do tamanho: {$result['size_reg']}\n"
                . "Tamanho anterior declarado: {$result['old_size']} bytes\n"
                . "Tamanho novo: {$result['new_size']} bytes\n"
                . "Validação: OK\nBackup: criado";
            $success = 'Substituição concluída e validada.';
            $source = $result['content'];
        }

        $report = analyzeSmali($source);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$oldDefault = 'https://painel.cybercoari.com.br/api/atx.php?URL=';
$newDefault = 'https://teste.cybercoari.com.br/api/atx.php?URL=';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ATX 3.0 — Analisador e Substituidor Smali</title>
<style>
*{box-sizing:border-box}
body{margin:0;padding:16px;background:#0b1020;color:#eef2ff;font-family:Arial,sans-serif}
.container{max-width:1000px;margin:auto}
.card{background:#121a2b;border:1px solid #293754;border-radius:12px;padding:18px;margin-bottom:16px}
h1{font-size:24px;margin:0 0 10px}
h2{font-size:18px;margin:0 0 12px}
p,.help{color:#b7c4d9;line-height:1.5}
label{display:block;font-weight:bold;margin:14px 0 7px}
input[type=text],input[type=file],textarea{width:100%;padding:12px;border-radius:8px;border:1px solid #34425f;background:#080d18;color:#eef2ff}
textarea{min-height:260px;resize:vertical;font-family:monospace;font-size:13px;color:#d9f99d}
button,.download{display:inline-block;margin:12px 8px 0 0;background:#2563eb;color:white;border:0;border-radius:8px;padding:12px 16px;text-decoration:none;font-size:15px;cursor:pointer}
button.secondary{background:#334155}
.error{background:#450a0a;border:1px solid #7f1d1d;color:#fecaca;padding:13px;border-radius:8px}
.success{background:#052e16;border:1px solid #166534;color:#bbf7d0;padding:13px;border-radius:8px}
.item{background:#0e1626;border:1px solid #293754;border-radius:9px;padding:13px;margin-top:10px}
.item.url{border-color:#2563eb}
.meta{color:#9eacc4;font-size:12px;margin-bottom:8px}
.decoded{white-space:pre-wrap;overflow-wrap:anywhere;font-family:monospace;color:#d9f99d}
.badge{display:inline-block;background:#1d4ed8;padding:3px 6px;border-radius:5px;font-size:11px;margin-left:5px}
pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#080d18;padding:12px;border-radius:8px}
small{color:#9eacc4}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h1>ATX 3.0 — Analisador e Substituidor Smali</h1>
<p>Analise todos os blocos <code>.array-data 1</code> e, quando quiser, substitua uma URL com validação e backup.</p>
<form method="post" enctype="multipart/form-data">
<label for="smali">Arquivo Smali (.smali ou .txt)</label>
<input id="smali" type="file" name="smali" accept=".smali,.txt">
<label for="smali_text">Ou cole o Smali inteiro</label>
<textarea id="smali_text" name="smali_text" placeholder="Cole aqui o conteúdo completo do arquivo Smali..."><?=h((string)($_POST['smali_text'] ?? ''))?></textarea>
<label for="old_url">URL antiga completa</label>
<input id="old_url" type="text" name="old_url" value="<?=h((string)($_POST['old_url'] ?? $oldDefault))?>">
<label for="new_url">URL nova completa</label>
<input id="new_url" type="text" name="new_url" value="<?=h((string)($_POST['new_url'] ?? $newDefault))?>">
<button type="submit" name="action" value="analyze" class="secondary">Analisar sem alterar</button>
<button type="submit" name="action" value="replace">Substituir, validar e gerar arquivo</button>
</form>
<p><small>Limite do arquivo: 5 MB. A URL antiga precisa corresponder exatamente a um único bloco decodificado.</small></p>
</div>

<?php if ($error !== ''): ?><div class="card error"><strong>Erro:</strong> <?=h($error)?></div><?php endif; ?>
<?php if ($success !== ''): ?>
<div class="card success">
<strong><?=h($success)?></strong>
<pre><?=h($modifiedInfo)?></pre>
<a class="download" href="?download=1">Baixar Smali modificado</a>
<a class="download" href="?download_backup=1">Baixar backup original</a>
</div>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''): ?>
<div class="card">
<h2>Resultado da análise</h2>
<p>Fonte: <?=h($sourceName)?> · Blocos encontrados: <?=count($report)?></p>
<?php if (!$report): ?>
<p>Nenhum bloco <code>.array-data 1</code> foi encontrado.</p>
<?php else: foreach ($report as $item): ?>
<div class="item <?=$item['is_url'] ? 'url' : ''?>">
<div class="meta">
Bloco #<?= (int)$item['index']?> · <?=h($item['label'])?> · linha <?= (int)$item['line']?> · <?= (int)$item['bytes']?> bytes
<?php if ($item['is_url']): ?><span class="badge">POSSÍVEL URL</span><?php endif; ?>
</div>
<div class="decoded"><?=h($item['text'])?></div>
</div>
<?php endforeach; endif; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>