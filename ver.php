<?php
declare(strict_types=1);
/*
 * ATX 3.0 — Analisador completo de .array-data 1
 *
 * Você pode:
 * 1) colar o Smali INTEIRO no campo; ou
 * 2) selecionar um arquivo .smali/.txt.
 *
 * O programa encontra TODOS os blocos .array-data 1 automaticamente.
 * Não é necessário limpar/extrair cada bloco manualmente.
 * SOMENTE LEITURA.
 */

$results = [];
$error = '';
$sourceName = '';

function decodeBlock(string $block): array
{
    preg_match_all('/0x([0-9a-fA-F]{1,2})t\b/i', $block, $m);

    $raw = '';
    foreach ($m[1] as $hex) {
        $raw .= chr(hexdec($hex));
    }

    return [trim($raw), strlen($raw)];
}

function analyzeSmali(string $name, string $content): array
{
    $results = [];

    // Aceita .array-data 1 mesmo que haja espaços/tabs diferentes.
    preg_match_all(
        '/\.array-data\s+1\b(.*?)\.end\s+array-data\b/is',
        $content,
        $blocks,
        PREG_OFFSET_CAPTURE
    );

    foreach ($blocks[1] as $index => $match) {
        $body = $match[0];
        $offset = $match[1];

        [$decoded, $bytes] = decodeBlock($body);

        // Linha aproximada do início do bloco.
        $line = substr_count(substr($content, 0, $offset), "\n") + 1;

        $results[] = [
            'index' => $index + 1,
            'file' => $name,
            'line' => $line,
            'bytes' => $bytes,
            'text' => $decoded,
            'url' => preg_match('~https?://~i', $decoded) === 1,
        ];
    }

    return $results;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $content = '';
        $sourceName = 'Texto colado';

        // Prioridade: arquivo selecionado.
        if (
            isset($_FILES['arquivo']) &&
            is_array($_FILES['arquivo']) &&
            $_FILES['arquivo']['error'] === UPLOAD_ERR_OK
        ) {
            $sourceName = (string)$_FILES['arquivo']['name'];
            $content = (string)file_get_contents($_FILES['arquivo']['tmp_name']);

            if ($content === '') {
                throw new RuntimeException('O arquivo está vazio.');
            }
        } else {
            $content = (string)($_POST['smali'] ?? '');

            if (trim($content) === '') {
                throw new RuntimeException(
                    'Cole o Smali inteiro ou selecione um arquivo.'
                );
            }
        }

        $results = analyzeSmali($sourceName, $content);

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ATX 3.0 — Analisador completo</title>
<style>
*{box-sizing:border-box}
body{
    margin:0;
    padding:18px;
    background:#0b1020;
    color:#eef2ff;
    font-family:Arial,sans-serif
}
.container{max-width:1100px;margin:auto}
.card{
    background:#121a2b;
    border:1px solid #293754;
    border-radius:12px;
    padding:20px;
    margin-bottom:18px
}
h1{margin-top:0}
textarea{
    width:100%;
    min-height:320px;
    resize:vertical;
    background:#080d18;
    color:#d9f99d;
    border:1px solid #34425f;
    border-radius:8px;
    padding:12px;
    font-family:monospace;
    font-size:13px
}
input[type=file]{width:100%;padding:10px 0}
button{
    margin-top:12px;
    background:#2563eb;
    color:white;
    border:0;
    border-radius:8px;
    padding:12px 18px;
    font-size:16px
}
.help{
    color:#aebbd0;
    line-height:1.5
}
.item{
    background:#0e1626;
    border:1px solid #293754;
    border-radius:10px;
    padding:15px;
    margin-top:12px
}
.item.url{border-color:#2563eb}
.meta{
    color:#9eacc4;
    font-size:13px;
    margin-bottom:8px
}
.text{
    white-space:pre-wrap;
    word-break:break-all;
    font-family:monospace;
    color:#d9f99d
}
.badge{
    display:inline-block;
    background:#1d4ed8;
    padding:3px 7px;
    border-radius:5px;
    font-size:11px;
    margin-left:6px
}
.error{
    color:#fecaca;
    background:#450a0a;
    border:1px solid #7f1d1d;
    padding:12px;
    border-radius:8px
}
.empty{color:#aebbd0}
</style>
</head>
<body>
<div class="container">

<div class="card">
<h1>ATX 3.0 — Analisador completo</h1>

<p class="help">
<strong>Agora você não precisa separar os blocos.</strong>
Cole o Smali inteiro abaixo. O programa vai procurar automaticamente
todos os <code>.array-data 1</code> e decodificar cada um.
</p>

<form method="post" enctype="multipart/form-data">

<label><strong>Smali inteiro:</strong></label>
<textarea name="smali" placeholder="Cole aqui o Smali inteiro..."><?=htmlspecialchars((string)($_POST['smali'] ?? ''), ENT_QUOTES, 'UTF-8')?></textarea>

<p class="help">Ou selecione diretamente um arquivo:</p>
<input type="file" name="arquivo" accept=".smali,.txt">

<br>
<button type="submit">Analisar todos os .array-data</button>
</form>
</div>

<?php if ($error !== ''): ?>
<div class="card error">
<?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?>
</div>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''): ?>

<div class="card">
<strong>Fonte:</strong>
<?=htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8')?>
<br>
<strong>Total de blocos encontrados:</strong>
<?=count($results)?>
</div>

<?php if (!$results): ?>

<div class="card empty">
Nenhum <code>.array-data 1</code> foi encontrado.
</div>

<?php else: ?>

<?php foreach ($results as $item): ?>
<div class="item <?= $item['url'] ? 'url' : '' ?>">

<div class="meta">
Bloco #<?= (int)$item['index'] ?>
— <?=htmlspecialchars($item['file'], ENT_QUOTES, 'UTF-8')?>
— linha <?= (int)$item['line']?>
— <?= (int)$item['bytes']?> bytes

<?php if ($item['url']): ?>
<span class="badge">URL</span>
<?php endif; ?>

</div>

<div class="text"><?=htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')?></div>

</div>
<?php endforeach; ?>

<?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>