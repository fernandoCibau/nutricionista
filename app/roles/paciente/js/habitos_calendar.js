document.addEventListener('DOMContentLoaded', function() {
    // Inject minimal styles for calendar
    const style = document.createElement('style');
    style.innerHTML = `
    .hc-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
    .hc-cell{padding:8px;border-radius:6px;text-align:center;cursor:pointer;background:#f8f9fa}
    .hc-cell.small{padding:6px;font-size:.9rem}
    .hc-cell.header{background:transparent;font-weight:600}
    .hc-cell.completed{background:#198754;color:#fff}
    .hc-cell.today{box-shadow:0 0 0 2px rgba(13,110,253,.15) inset}
    @media (max-width:480px){.hc-grid{gap:4px}.hc-cell{padding:6px;font-size:.8rem}}
    `;
    document.head.appendChild(style);

    const modalEl = document.getElementById('habitCalendarModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const container = document.getElementById('habitCalendarContainer');
    const monthYear = document.getElementById('calMonthYear');
    const prevBtn = document.getElementById('calPrev');
    const nextBtn = document.getElementById('calNext');
    const titleEl = document.getElementById('habitCalendarTitle');

    let currentDate = new Date();
    let datesSet = new Set();
    let currentHabitId = null;

    function formatDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const day = String(d.getDate()).padStart(2,'0');
        return `${y}-${m}-${day}`;
    }

    function fetchDates(habitId) {
        return fetch(`habito_calendar.php?id_habito=${habitId}`, {credentials:'same-origin'})
            .then(r => r.json());
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const startDay = firstDay.getDay(); // 0 Sun - 6 Sat
        // We'll render calendar starting from Monday? For simplicity keep Sunday-first.
        const daysInMonth = new Date(year, month+1, 0).getDate();

        monthYear.textContent = firstDay.toLocaleString(undefined, {month:'long', year:'numeric'});

        // Weekday headers
        const weekdays = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        let html = '<div class="hc-grid">';
        weekdays.forEach(w => html += `<div class="hc-cell header">${w}</div>`);

        // Empty cells before first day
        for (let i=0;i<startDay;i++) html += `<div class="hc-cell"></div>`;

        for (let d=1; d<=daysInMonth; d++) {
            const dt = new Date(year, month, d);
            const iso = formatDate(dt);
            const classes = ['hc-cell','small'];
            if (datesSet.has(iso)) classes.push('completed');
            const todayIso = formatDate(new Date());
            if (iso === todayIso) classes.push('today');
            html += `<div class="${classes.join(' ')}" data-date="${iso}">${d}</div>`;
        }

        html += '</div>';
        container.innerHTML = html;

        // Attach click handlers
        container.querySelectorAll('.hc-cell[data-date]').forEach(cell => {
            cell.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                toggleDate(currentHabitId, date, this);
            });
        });
    }

    function toggleDate(habitId, date, cellEl) {
        // Determine action: if currently completed, we'll unmark; else mark
        const isCompleted = datesSet.has(date);
        const formData = new FormData();
        formData.append('id_habito', habitId);
        formData.append('fecha', date);
        // cantidad optional (default 1)

        fetch('marcar_habito.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(resp => resp.json())
        .then(data => {
            if (data && data.status === 'ok') {
                if (data.action === 'marcado') {
                    datesSet.add(date);
                    cellEl.classList.add('completed');
                } else if (data.action === 'desmarcado') {
                    datesSet.delete(date);
                    cellEl.classList.remove('completed');
                }
                // Optionally show racha somewhere
                if (data.racha !== undefined) {
                    // update title with racha
                    titleEl.textContent = `${titleEl.getAttribute('data-base') || 'Calendario'} — Racha: ${data.racha}`;
                }
            } else {
                alert('Error al actualizar. Intente de nuevo.');
            }
        }).catch(err => {
            console.error(err);
            alert('Error de red al actualizar.');
        });
    }

    // Prev/Next
    prevBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth()-1);
        renderCalendar();
    });
    nextBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth()+1);
        renderCalendar();
    });

    // Open calendar when clicking button
    document.querySelectorAll('.btn-open-calendar').forEach(btn => {
        btn.addEventListener('click', function() {
            const hid = this.getAttribute('data-habit-id');
            const desc = this.getAttribute('data-habit-desc') || 'Calendario';
            currentHabitId = hid;
            titleEl.setAttribute('data-base', desc);
            titleEl.textContent = desc;
            // reset to current month
            currentDate = new Date();
            datesSet = new Set();
            container.innerHTML = '<div class="text-center py-4">Cargando...</div>';
            modal.show();
            fetchDates(hid).then(json => {
                if (json && json.dates) {
                    json.dates.forEach(d => datesSet.add(d));
                    // update title with racha if provided
                    if (json.racha !== undefined) titleEl.textContent = `${desc} — Racha: ${json.racha}`;
                    renderCalendar();
                } else {
                    container.innerHTML = '<div class="text-danger">No se pudieron cargar las fechas.</div>';
                }
            }).catch(err => {
                console.error(err);
                container.innerHTML = '<div class="text-danger">Error al cargar.</div>';
            });
        });
    });

});
