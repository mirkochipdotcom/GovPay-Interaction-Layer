// Piccolo script frontend per demo
(function(){
  const onReady = function(){
  console.log('app.js onReady');
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
    const toIt = (iso)=>{
      if(!iso) return '';
      const parts = iso.split('-');
      if(parts.length !== 3) return iso;
      const [yy,mm,dd] = parts;
      return dd && mm && yy ? `${dd}/${mm}/${yy}` : iso;
    };

    const updateVisibleValue = ()=>{
      const s = hiddenDa.value;
      const e = hiddenA.value;
      if(s && e){
        input.value = `${toIt(s)} - ${toIt(e)}`;
      }else if(s){
        input.value = `dal ${toIt(s)}`;
      }else if(e){
        input.value = `fino al ${toIt(e)}`;
      }else{
        input.value = '';
      }
    };

    const syncFromHidden = ()=>{
      start.value = hiddenDa.value || '';
      end.value = hiddenA.value || '';
      updateVisibleValue();
    };

    const syncFromInputs = ()=>{
      hiddenDa.value = start.value || '';
      hiddenA.value = end.value || '';
      updateVisibleValue();
    };

    const presetMonth = ()=>{
      const e = new Date();
      const s = new Date();
      s.setHours(0,0,0,0);
      e.setHours(0,0,0,0);
      s.setMonth(s.getMonth() - 1);
      start.value = fmt(s);
      end.value = fmt(e);
      syncFromInputs();
    };

    syncFromHidden();

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
          syncFromInputs();
        }else if(type === 'month'){
          presetMonth();
        }else if(type === 'all'){
          start.value = '';
          end.value = '';
          syncFromInputs();
        }
      });
    });

    ['change','blur'].forEach(evt => {
      start.addEventListener(evt, syncFromInputs);
      end.addEventListener(evt, syncFromInputs);
    });

    // Apply
    const applyBtn = panel.querySelector('[data-action="apply"]');
    if(applyBtn){
      applyBtn.addEventListener('click', ()=>{
        syncFromInputs();
        hidePanel();
      });
    }

    const form = input.form;
    if(form){
      form.addEventListener('submit', syncFromInputs);
    }
  }

  // Inizializza subito
  initDateRange();
  // Robust fallback per il menu hamburger:
  // Registriamo un listener che si attiva dopo gli altri handler (setTimeout 0) e verifica
  // se il collapse è stato modificato; se nessun handler ha alternato lo stato, lo facciamo noi.
  try{
    const toggler = document.querySelector('.custom-navbar-toggler');
    const collapse = document.getElementById('mainNav');
    if(toggler && collapse){
      toggler.addEventListener('click', function(e){
        console.log('custom-navbar-toggler click detected on', e.target);
        // Non impediamo il default: lasciamo che eventuali handler (es. Bootstrap) facciano il loro lavoro.
        const prev = collapse.classList.contains('show');
        // Esegui dopo gli altri handler
        setTimeout(function(){
          try{
            const now = collapse.classList.contains('show');
            console.log('navbar prev/now', prev, now);
            if(now === prev){
              // Nessun handler ha modificato lo stato: toggliamo noi
              if(now){
                collapse.classList.remove('show');
                toggler.setAttribute('aria-expanded','false');
              } else {
                collapse.classList.add('show');
                toggler.setAttribute('aria-expanded','true');
              }
            }
          }catch(innerErr){
            console.warn('Navbar fallback inner error', innerErr);
          }
        }, 10);
      });
    }
  }catch(err){
    console.warn('Navbar fallback init error', err);
  }
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
