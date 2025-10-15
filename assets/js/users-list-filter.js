document.addEventListener('DOMContentLoaded', function () {
  try {
    const q = document.getElementById('usersSearch');
    const clearBtn = document.getElementById('usersClear');
    const table = document.getElementById('usersTable');
    const counter = document.getElementById('usersCounter');
    const noUsersRow = document.getElementById('noUsersRow');
    const noMatch = document.getElementById('noUsersMatchRow');
    if (!table || !q) return;
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.id !== 'noUsersRow' && r.id !== 'noUsersMatchRow');
    const total = rows.length;
    const updateCounter = (visible) => {
      if (counter) counter.textContent = `Mostrati ${visible} di ${total}`;
    };
    const norm = (s) => (s || '').toString().toLowerCase();
    const filter = () => {
      const term = norm(q.value);
      let visible = 0;
      rows.forEach(r => {
        const text = norm(r.textContent || '');
        const match = term === '' || text.indexOf(term) !== -1;
        r.classList.toggle('d-none', !match);
        if (match) visible++;
      });
      if (noUsersRow) noUsersRow.classList.toggle('d-none', total > 0);
      if (noMatch) noMatch.classList.toggle('d-none', visible > 0 || total === 0);
      updateCounter(visible);
    };
    q.addEventListener('input', filter);
    clearBtn && clearBtn.addEventListener('click', () => { q.value = ''; q.focus(); filter(); });
    filter();
  } catch (e) {
    // non-fatal, do not break the page
    console.error('users-list-filter error', e);
  }
});
