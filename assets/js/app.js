// Piccolo script frontend per demo
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('debug-button');
  if(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const out = document.getElementById('pendenze-output');
      if(out) out.textContent = 'Eseguita azione di debug al ' + new Date().toLocaleString();
    });
  }

  const fetchBtn = document.getElementById('fetch-pendenze');
  if(fetchBtn){
    fetchBtn.addEventListener('click', async function(){
      const out = document.getElementById('pendenze-output');
      out.textContent = 'Richiesta in corso...';
      try{
        // Demo: chiama l'endpoint locale /pendenze in GET
        const res = await fetch('/pendenze', { method: 'GET' });
        const text = await res.text();
        out.textContent = text.slice(0, 2000);
      }catch(err){
        out.textContent = 'Errore di rete: ' + err;
      }
    });
  }
});
