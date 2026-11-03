function toggleSidebar(){const s=document.getElementById('sidebar'); if(s)s.classList.toggle('open');}
document.addEventListener('click',e=>{if(e.target.closest('.nav-item')&&window.innerWidth<=900){const s=document.getElementById('sidebar');if(s)s.classList.remove('open');}});
