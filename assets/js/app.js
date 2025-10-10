// Piccolo script frontend per demo
(function(){
  const onReady = function(){
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

  // Date range picker semplice con preset (ultima settimana/mese/tutto)
  function initDateRange(){
    const input = document.getElementById('dateRangeInput');
    const panel = document.getElementById('dateRangePanel');
    const start = document.getElementById('dateStart');
    const end = document.getElementById('dateEnd');
    const hiddenDa = document.getElementById('dataDa');
    const hiddenA = document.getElementById('dataA');
    if(!input || !panel || !start || !end || !hiddenDa || !hiddenA) return;

    // Helpers
    const fmt = (d)=> d.toISOString().slice(0,10);
    const toIt = (iso)=>{ const [yy,mm,dd] = iso.split('-'); return `${dd}/${mm}/${yy}`; };
    const setVisibleValue = ()=>{
      const s = hiddenDa.value;
      const e = hiddenA.value;
      input.value = (s && e) ? `${toIt(s)} - ${toIt(e)}` : '';
    };

    // Preselezione: se vuoto, imposta ultimo mese
    const presetMonth = ()=>{
      const e = new Date();
      const s = new Date();
      s.setMonth(s.getMonth() - 1);
      start.value = fmt(s);
      end.value = fmt(e);
    };
    if(!hiddenDa.value && !hiddenA.value){
      presetMonth();
      hiddenDa.value = start.value;
      hiddenA.value = end.value;
      setVisibleValue();
    }else{
      start.value = hiddenDa.value || '';
      end.value = hiddenA.value || '';
      setVisibleValue();
    }

    // Apertura/chiusura pannello
    const showPanel = ()=>{ panel.classList.remove('d-none'); panel.style.display = 'block'; };
    const hidePanel = ()=>{ panel.classList.add('d-none'); panel.style.display = ''; };
    input.addEventListener('focus', showPanel);
    input.addEventListener('click', showPanel);
    document.addEventListener('click', (e)=>{ if(!panel.contains(e.target) && e.target !== input){ hidePanel(); } });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') hidePanel(); });

    // Preset buttons
    panel.querySelectorAll('[data-range]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const type = btn.getAttribute('data-range');
        if(type === 'week'){
          const e = new Date();
          const s = new Date();
          s.setDate(s.getDate() - 7);
          start.value = fmt(s);
          end.value = fmt(e);
        }else if(type === 'month'){
          presetMonth();
        }else if(type === 'all'){
          start.value = '';
          end.value = '';
        }
      });
    });

    // Apply
    const applyBtn = panel.querySelector('[data-action="apply"]');
    if(applyBtn){
      applyBtn.addEventListener('click', ()=>{
        hiddenDa.value = start.value || '';
        hiddenA.value = end.value || '';
        setVisibleValue();
        hidePanel();
      });
    }
  }

  // Inizializza subito
  initDateRange();
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
