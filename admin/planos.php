<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../services/MercadoPago.php';
$u=require_auth();
$plans=plans_live();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Planos · ConnectPlus</title>
<link rel="stylesheet" href="/assets/css/style.css?v=1">
<style>
body{min-height:100vh}.plans-wrap{max-width:1100px;margin:0 auto;padding:2rem 1rem 4rem}.topbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1rem;margin-top:1.5rem}.plan-card{display:flex;flex-direction:column;border:1px solid rgba(110,168,254,.18);border-radius:1.15rem;padding:1.35rem;background:linear-gradient(145deg,rgba(20,31,50,.94),rgba(8,15,28,.95));box-shadow:0 18px 45px rgba(0,0,0,.28)}.plan-price{font-size:1.85rem;font-weight:800;color:#93c5fd;margin:.6rem 0}.plan-card p{color:#94a3b8;line-height:1.45}.plan-card .btn{margin-top:auto;width:100%}.status-box{margin-top:1rem;padding:1rem 1.15rem;border-radius:.9rem;border:1px solid rgba(148,163,184,.16);background:rgba(8,18,35,.55)}.pix-box{display:none;margin-top:1.25rem;padding:1.15rem;border-radius:1rem;border:1px solid rgba(52,211,153,.28);background:rgba(6,24,18,.55)}.pix-box.show{display:block}.pix-key{font-family:ui-monospace,monospace;word-break:break-all;padding:.75rem;border-radius:.65rem;background:#050a13;color:#9fc5ff;border:1px solid rgba(110,168,254,.2);margin:.65rem 0;font-size:.78rem}.pix-qr{max-width:260px;width:100%;border-radius:.75rem;margin:.75rem 0;background:#fff;padding:.5rem}.muted{color:#8493aa}.ok{color:#34d399;font-weight:600}.warn{color:#fbbf24}.pay-status{margin-top:.75rem;font-weight:700}.admin-orders-box{overflow:hidden}
@media(max-width:640px){.plans-wrap{padding:1rem .85rem 2.5rem}.plans-grid{grid-template-columns:1fr}.plan-card{padding:1.05rem}}
</style>
</head>
<body class="dashboard-body">
<div class="plans-wrap">
  <div class="topbar">
    <div><div class="eyebrow">CONNECTPLUS</div><h1 class="page-title" style="margin-bottom:.25rem;">Planos</h1><p class="muted">Escolha um plano e pague via PIX.</p></div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap"><a class="btn secondary" href="/admin/dashboard.php">Voltar ao painel</a><a class="btn secondary" href="/logout.php">Sair</a></div>
  </div>

  <div class="status-box">
    <div><strong>Conta:</strong> <?=e($u['username'])?></div>
    <div><strong>Plano atual:</strong> <?=e($u['plan']??'Gratuito')?></div>
    <div><strong>Validade:</strong>
      <?php if(is_admin($u)): ?>Sem vencimento (admin)
      <?php elseif(!empty($u['expiresAt'])): ?><?=e(date('d/m/Y H:i',strtotime((string)$u['expiresAt'])))?>
      <?php else: ?>Sem limite definido<?php endif; ?>
    </div>
  </div>

  <div class="plans-grid">
    <?php foreach($plans as $plan): ?>
    <div class="plan-card">
      <h3><?=e($plan['name'])?></h3>
      <div class="plan-price">R$ <?=e(number_format((float)$plan['price'],2,',','.'))?></div>
      <p><?=e($plan['description'])?></p>
      <p class="muted"><?=e($plan['days'])?> dias de acesso</p>
      <button class="btn primary" type="button" onclick="buyPlan('<?=e($plan['id'])?>')">Pagar via PIX</button>
    </div>
    <?php endforeach; ?>
  </div>

  <div id="pixBox" class="pix-box">
    <h3 style="margin:0 0 .5rem">Pagamento PIX</h3>
    <p class="muted">Pedido: <span id="pixOrder">—</span></p>
    <img id="pixQr" class="pix-qr" alt="QR Code PIX" style="display:none">
    <div class="pix-key" id="pixKey">Gerando pagamento...</div>
    <p><strong>Valor:</strong> <span id="pixAmount">—</span></p>
    <p class="pay-status" id="payStatus">Aguardando pagamento...</p>
    <button class="btn secondary" type="button" onclick="copyPix()">Copiar PIX</button>
  </div>

  <?php if(is_admin($u)): ?>
  <div class="status-box admin-orders-box">
    <h3 style="margin:0 0 .75rem">Admin · pedidos pendentes</h3>
    <button class="btn secondary" type="button" onclick="loadPending()">Atualizar lista</button>
    <div id="pendingList" style="margin-top:1rem"></div>
    <hr style="border-color:rgba(148,163,184,.15);margin:1.25rem 0">
    <h4 style="margin:0 0 .5rem">Liberar dias manualmente</h4>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem">
      <input id="extUser" placeholder="username" style="flex:1;min-width:140px;padding:.65rem .8rem;border-radius:.65rem;border:1px solid rgba(148,163,184,.2);background:#0b1322;color:#fff">
      <input id="extDays" type="number" min="1" value="30" style="width:100px;padding:.65rem .8rem;border-radius:.65rem;border:1px solid rgba(148,163,184,.2);background:#0b1322;color:#fff">
      <button class="btn primary" type="button" onclick="extendManual()">Liberar</button>
    </div>
    <p id="adminMsg" class="muted" style="margin-top:.75rem"></p>
  </div>
  <?php endif; ?>
</div>
<script>
let currentOrderId=null,pollTimer=null;
function money(v){return Number(v||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});}
async function buyPlan(planId){
  const box=document.getElementById('pixBox'), status=document.getElementById('payStatus');
  box.classList.add('show'); status.textContent='Gerando pagamento...';
  try{
    const res=await fetch('/api/order-create.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({planId})});
    const data=await res.json().catch(()=>({}));
    if(!res.ok)throw new Error(data.error||'Falha ao criar pagamento');
    const o=data.order||{}; currentOrderId=o.id||null;
    document.getElementById('pixOrder').textContent=currentOrderId||'—';
    document.getElementById('pixAmount').textContent=money(o.price);
    const key=(o.qrCode||'').trim();
    document.getElementById('pixKey').textContent=key||'QR Code indisponível. Verifique a configuração do Mercado Pago.';
    const img=document.getElementById('pixQr');
    if(o.qrCodeBase64){img.src='data:image/png;base64,'+o.qrCodeBase64;img.style.display='block';}else img.style.display='none';
    status.textContent='Aguardando pagamento...';
    clearInterval(pollTimer);
    if(currentOrderId)pollTimer=setInterval(checkStatus,5000);
  }catch(e){status.textContent=e.message||'Erro ao criar pagamento';status.className='pay-status warn';}
}
async function checkStatus(){
  if(!currentOrderId)return;
  try{
    const res=await fetch('/api/order-status.php?id='+encodeURIComponent(currentOrderId),{headers:{'Accept':'application/json'}});
    const data=await res.json().catch(()=>({})); if(!res.ok)throw new Error(data.error||'Falha');
    const o=data.order||{}; const st=String(o.status||'pending');
    if(st==='paid'||String(o.mpStatus||'')==='approved'){
      clearInterval(pollTimer);document.getElementById('payStatus').textContent='✅ Pagamento confirmado! Acesso liberado.';
      document.getElementById('payStatus').className='pay-status ok';setTimeout(()=>location.href='/admin/dashboard.php',1800);
    }
  }catch(e){}
}
async function copyPix(){
  const text=document.getElementById('pixKey').textContent.trim(); if(!text||text.startsWith('Gerando'))return;
  try{await navigator.clipboard.writeText(text);alert('PIX copiado.');}catch(e){window.prompt('Copie o código PIX:',text);}
}
async function loadPending(){
  const el=document.getElementById('pendingList'); if(!el)return;
  const res=await fetch('/api/orders-pending.php'); const data=await res.json().catch(()=>({}));
  if(!res.ok){el.innerHTML='<p class="warn">'+(data.error||'Erro')+'</p>';return;}
  const orders=data.orders||[];
  if(!orders.length){el.innerHTML='<p class="muted">Nenhum pedido pendente.</p>';return;}
  el.innerHTML=orders.map(o=>'<div style="padding:.75rem 0;border-bottom:1px solid rgba(148,163,184,.12)"><div><strong>'+o.username+'</strong> · '+o.planName+' · '+money(o.price)+'</div><div class="muted" style="font-size:.8rem">'+o.id+(o.paymentId?' · MP '+o.paymentId:'')+'</div><button class="btn primary" style="margin-top:.5rem" onclick="confirmOrder(\''+o.id+'\')">Liberar</button></div>').join('');
}
async function confirmOrder(orderId){
  if(!confirm('Liberar acesso ao usuário deste pedido?'))return;
  const res=await fetch('/api/confirm-order.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({orderId})});
  const data=await res.json().catch(()=>({}));if(!res.ok)return alert(data.error||'Falha');alert('Liberado! Válido até: '+(data.expiresAt||'sem limite'));loadPending();
}
async function extendManual(){
  const username=document.getElementById('extUser').value.trim(),days=Number(document.getElementById('extDays').value||0),msg=document.getElementById('adminMsg');
  if(!username)return alert('Informe um usuário.');
  const res=await fetch('/api/admin-extend.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({username,days})});
  const data=await res.json().catch(()=>({}));if(!res.ok){msg.textContent=data.error||'Erro';msg.className='warn';return;}
  msg.textContent='OK: '+data.username+' até '+data.expiresAt;msg.className='ok';
}
<?php if(is_admin($u)): ?>loadPending();<?php endif; ?>
</script>
</body></html>
