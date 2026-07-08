/**
 * MERJ — Planning FullCalendar
 * Calendrier interactif unifié (admin / enseignant / étudiant)
 */
(function () {
  'use strict';

  const app = document.getElementById('planning-app');
  if (!app || typeof FullCalendar === 'undefined') {
    return;
  }

  const config = {
    role: app.dataset.role,
    canCreate: app.dataset.canCreate === '1',
    canEdit: app.dataset.canEdit === '1',
    csrfToken: app.dataset.csrfToken,
    apiEvents: app.dataset.apiEvents,
    apiConflicts: app.dataset.apiConflicts,
    exportPdfUrl: app.dataset.exportPdfUrl,
    exportIcsUrl: app.dataset.exportIcsUrl,
    initialDate: app.dataset.initialDate || null,
    initialView: app.dataset.initialView || null,
  };

  const TYPE_LABELS = { cours: 'Cours', examen: 'Examen', reunion: 'Réunion', autre: 'Autre' };
  const VIEW_KEYS = ['timeGridDay', 'timeGridWeek', 'dayGridMonth', 'listWeek'];

  let calendar = null;
  let miniCalendar = null;
  let undoState = null;
  let activePopoverEvent = null;
  let deleteConfirmPending = false;

  const els = {
    skeleton: document.getElementById('planning-skeleton'),
    empty: document.getElementById('planning-empty'),
    periodTitle: document.getElementById('planning-period-title'),
    calendarEl: document.getElementById('planning-calendar'),
    miniEl: document.getElementById('planning-mini-calendar'),
    popover: document.getElementById('planning-event-popover'),
    form: document.getElementById('planning-event-form'),
    offcanvasEl: document.getElementById('planningEventOffcanvas'),
    conflictBanner: document.getElementById('planning-conflict-banner'),
    toastContainer: document.getElementById('planning-toast-container'),
  };

  const offcanvas = els.offcanvasEl ? new bootstrap.Offcanvas(els.offcanvasEl) : null;
  const shortcutsModal = document.getElementById('planningShortcutsModal')
    ? new bootstrap.Modal(document.getElementById('planningShortcutsModal'))
    : null;

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    readFiltersFromUrl();
    initMainCalendar();
    initMiniCalendar();
    bindToolbar();
    bindForm();
    bindPopover();
    bindKeyboard();
    bindExport();
    updateExportLink();
  }

  /** Lit les filtres depuis l'URL (partageable, survit au F5) */
  function readFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const setMulti = (id, key) => {
      const el = document.getElementById(id);
      if (!el || !params.get(key)) return;
      const vals = params.get(key).split(',');
      Array.from(el.options).forEach((opt) => {
        opt.selected = vals.includes(opt.value);
      });
    };
    setMulti('filter-type', 'type');
    setMulti('filter-classe', 'classe');
    setMulti('filter-enseignant', 'enseignant');
    setMulti('filter-cours', 'cours');
    const weekends = document.getElementById('filter-weekends');
    if (weekends) weekends.checked = params.get('weekends') === '1';
  }

  /** Écrit les filtres actifs dans l'URL */
  function syncFiltersToUrl() {
    const params = new URLSearchParams(window.location.search);
    const getMulti = (id, key) => {
      const el = document.getElementById(id);
      if (!el) return;
      const vals = Array.from(el.selectedOptions).map((o) => o.value);
      if (vals.length) params.set(key, vals.join(','));
      else params.delete(key);
    };
    getMulti('filter-type', 'type');
    getMulti('filter-classe', 'classe');
    getMulti('filter-enseignant', 'enseignant');
    getMulti('filter-cours', 'cours');
    const weekends = document.getElementById('filter-weekends');
    if (weekends?.checked) params.set('weekends', '1');
    else params.delete('weekends');
    if (calendar) {
      params.set('date', formatLocalDate(calendar.getDate()));
      params.set('view', calendar.view.type);
    }
    const qs = params.toString();
    history.replaceState(null, '', qs ? '?' + qs : window.location.pathname);
    updateExportLink();
  }

  function getFilterParams() {
    const p = new URLSearchParams();
    const addMulti = (id, key) => {
      const el = document.getElementById(id);
      if (!el) return;
      const vals = Array.from(el.selectedOptions).map((o) => o.value);
      if (vals.length) p.set(key, vals.join(','));
    };
    addMulti('filter-type', 'type');
    addMulti('filter-classe', 'classe');
    addMulti('filter-enseignant', 'enseignant');
    addMulti('filter-cours', 'cours');
    return p;
  }

  function getExportQueryString() {
    const p = getFilterParams();
    if (calendar) {
      const v = calendar.view;
      p.set('start', v.activeStart.toISOString());
      p.set('end', v.activeEnd.toISOString());
    }
    return p.toString();
  }

  function updateExportLink() {
    const qs = getExportQueryString();
    const pdfBtn = document.getElementById('planning-export-pdf-btn');
    const icsBtn = document.getElementById('planning-export-ics-btn');
    if (pdfBtn) {
      pdfBtn.href = config.exportPdfUrl + (qs ? '?' + qs : '');
    }
    if (icsBtn) {
      icsBtn.href = config.exportIcsUrl + (qs ? '?' + qs : '');
    }
  }

  function initMainCalendar() {
    const isMobile = window.innerWidth < 768;
    const weekendsEl = document.getElementById('filter-weekends');
    const showWeekends = weekendsEl?.checked ?? false;

    const initialView = config.initialView || (isMobile ? 'listWeek' : 'timeGridWeek');
    const initialDate = config.initialDate || undefined;

    calendar = new FullCalendar.Calendar(els.calendarEl, {
      locale: 'fr',
      firstDay: 1,
      initialView,
      initialDate,
      headerToolbar: false,
      height: 'auto',
      slotMinTime: '07:30:00',
      slotMaxTime: '19:30:00',
      slotDuration: '00:30:00',
      slotEventOverlap: false,
      nowIndicator: true,
      weekends: showWeekends,
      allDaySlot: false,
      selectable: config.canCreate,
      selectMirror: true,
      editable: config.canEdit,
      eventStartEditable: true,
      eventDurationEditable: true,
      eventSources: [{ events: fetchEvents }],
      eventContent: renderEventContent,
      eventClassNames: (arg) => {
        const classes = ['fc-event--' + (arg.event.extendedProps.type || 'autre')];
        if (arg.event.end && arg.event.end < new Date()) classes.push('fc-event--past');
        if (arg.event.extendedProps.editable) classes.push('fc-event-draggable');
        return classes;
      },
      eventDidMount: (info) => {
        // Supprimer les couleurs inline FC (texte blanc sur fond pâle)
        info.el.style.removeProperty('background-color');
        info.el.style.removeProperty('border-color');
        info.el.style.removeProperty('color');
        const main = info.el.querySelector('.fc-event-main');
        if (main) {
          main.style.removeProperty('background-color');
          main.style.removeProperty('border-color');
          main.style.removeProperty('color');
        }
        if (!info.event.extendedProps.editable) {
          info.el.style.cursor = 'pointer';
        }
      },
      loading: (isLoading) => {
        els.skeleton?.classList.toggle('is-hidden', !isLoading);
      },
      datesSet: (info) => {
        updatePeriodTitle(info.view);
        syncFiltersToUrl();
        if (miniCalendar) {
          miniCalendar.gotoDate(info.view.currentStart);
        }
        updateCalendarDensity();
      },
      eventsSet: (events) => {
        const isTimeOrList =
          calendar.view.type.startsWith('timeGrid') || calendar.view.type.startsWith('list');
        const showEmpty = isTimeOrList && events.length === 0;
        els.empty?.classList.toggle('d-none', !showEmpty);
        updateCalendarDensity();
      },
      select: (info) => {
        if (!config.canCreate) return;
        openForm(null, info.start, info.end);
      },
      dateClick: (info) => {
        if (!config.canCreate) return;
        if (calendar.view.type.startsWith('timeGrid')) return;
        const start = info.date;
        const end = new Date(start.getTime() + 2 * 60 * 60 * 1000);
        openForm(null, start, end);
      },
      eventClick: (info) => {
        info.jsEvent.preventDefault();
        showPopover(info.event, info.el);
      },
      eventDrop: (info) => handleEventMove(info, 'déplacé'),
      eventResize: (info) => handleEventMove(info, 'redimensionné'),
    });

    calendar.render();
    updatePeriodTitle(calendar.view);
    bindFilterChanges();
  }

  function initMiniCalendar() {
    if (!els.miniEl) return;
    miniCalendar = new FullCalendar.Calendar(els.miniEl, {
      locale: 'fr',
      initialView: 'dayGridMonth',
      headerToolbar: { left: 'prev', center: 'title', right: 'next' },
      height: 'auto',
      fixedWeekCount: false,
      dateClick: (info) => {
        calendar.gotoDate(info.date);
        if (window.innerWidth < 768) calendar.changeView('listWeek');
      },
    });
    miniCalendar.render();
  }

  async function fetchEvents(fetchInfo, successCallback, failureCallback) {
    try {
      const params = new URLSearchParams({
        start: fetchInfo.startStr,
        end: fetchInfo.endStr,
      });
      const filters = getFilterParams();
      filters.forEach((v, k) => params.set(k, v));

      const res = await fetch(config.apiEvents + '?' + params.toString(), {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) throw new Error('Chargement impossible');
      const data = await res.json();
      successCallback(data);
    } catch (e) {
      showToast('Erreur lors du chargement des événements.', 'danger');
      failureCallback(e);
    }
  }

  function formatLocalDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  function updateCalendarDensity() {
    if (!calendar || !els.calendarEl) return;

    const view = calendar.view;
    if (!view.type.startsWith('timeGrid')) {
      delete els.calendarEl.dataset.maxOverlap;
      return;
    }

    const events = calendar.getEvents();
    let maxOverlap = 1;
    const day = new Date(view.currentStart);
    day.setHours(0, 0, 0, 0);
    const rangeEnd = new Date(view.currentEnd);

    while (day < rangeEnd) {
      const concurrency = getMaxConcurrencyForDay(events, day);
      maxOverlap = Math.max(maxOverlap, concurrency);
      day.setDate(day.getDate() + 1);
    }

    els.calendarEl.dataset.maxOverlap = String(maxOverlap);
  }

  function getMaxConcurrencyForDay(events, dayDate) {
    const dayStart = new Date(dayDate);
    dayStart.setHours(0, 0, 0, 0);
    const dayEnd = new Date(dayStart);
    dayEnd.setDate(dayEnd.getDate() + 1);

    const dayEvents = events.filter((event) => {
      if (!event.start) return false;
      const start = event.start;
      const end = event.end || new Date(start.getTime() + 2 * 60 * 60 * 1000);
      return start < dayEnd && end > dayStart;
    });

    if (dayEvents.length <= 1) {
      return dayEvents.length;
    }

    const points = [];
    dayEvents.forEach((event) => {
      const start = Math.max(event.start.getTime(), dayStart.getTime());
      const end = Math.min(
        (event.end || new Date(event.start.getTime() + 2 * 60 * 60 * 1000)).getTime(),
        dayEnd.getTime(),
      );
      points.push({ t: start, d: 1 });
      points.push({ t: end, d: -1 });
    });

    points.sort((a, b) => (a.t === b.t ? a.d - b.d : a.t - b.t));

    let current = 0;
    let max = 0;
    points.forEach((point) => {
      current += point.d;
      if (current > max) max = current;
    });

    return max;
  }

  function renderEventContent(arg) {
    const props = arg.event.extendedProps;
    const time = arg.timeText;
    const start = arg.event.start;
    const end = arg.event.end || new Date(start.getTime() + 2 * 60 * 60 * 1000);
    const durationMins = (end.getTime() - start.getTime()) / 60000;
    const maxOverlap = parseInt(els.calendarEl?.dataset.maxOverlap || '1', 10);

    let density = 'full';
    if (durationMins < 50 || maxOverlap >= 3) {
      density = 'compact';
    } else if (durationMins < 90 || maxOverlap >= 2) {
      density = 'medium';
    }

    const showTime = durationMins >= 40 && maxOverlap < 4;
    const showLieu = props.lieu && durationMins >= 90 && maxOverlap < 3;

    const lieu = showLieu
      ? '<div class="planning-event-content__lieu"><svg width="9" height="9" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 1a4 4 0 0 0-4 4c0 3 4 7 4 7s4-4 4-7a4 4 0 0 0-4-4zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg><span>' + escapeHtml(props.lieu) + '</span></div>'
      : '';

    const timeHtml = showTime
      ? '<div class="planning-event-content__time">' + escapeHtml(time) + '</div>'
      : '';

    return {
      html: '<div class="planning-event-content planning-event-content--' + density + '">' +
        timeHtml +
        '<div class="planning-event-content__title">' + escapeHtml(arg.event.title) + '</div>' +
        lieu +
        '</div>',
    };
  }

  function bindFilterChanges() {
    ['filter-type', 'filter-classe', 'filter-enseignant', 'filter-cours'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('change', onFilterChange);
    });
    const weekends = document.getElementById('filter-weekends');
    if (weekends) {
      weekends.addEventListener('change', () => {
        calendar.setOption('weekends', weekends.checked);
        onFilterChange();
      });
    }
    document.getElementById('planning-filters-reset')?.addEventListener('click', () => {
      ['filter-type', 'filter-classe', 'filter-enseignant', 'filter-cours'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        Array.from(el.options).forEach((o) => (o.selected = false));
        if (el.tomselect) el.tomselect.clear();
      });
      if (weekends) weekends.checked = false;
      calendar.setOption('weekends', false);
      calendar.today();
      onFilterChange();
    });
  }

  function onFilterChange() {
    syncFiltersToUrl();
    calendar.refetchEvents();
  }

  function bindToolbar() {
    document.getElementById('planning-today')?.addEventListener('click', () => calendar.today());
    document.getElementById('planning-prev')?.addEventListener('click', () => calendar.prev());
    document.getElementById('planning-next')?.addEventListener('click', () => calendar.next());

    document.querySelectorAll('.planning-view-switch [data-view]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const view = btn.dataset.view;
        calendar.changeView(view);
        document.querySelectorAll('.planning-view-switch .btn').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        syncFiltersToUrl();
      });
    });

    document.getElementById('planning-new-btn')?.addEventListener('click', () => openForm());
    document.getElementById('planning-empty-create')?.addEventListener('click', () => openForm());
    document.getElementById('planning-empty-today')?.addEventListener('click', () => calendar.today());
    document.getElementById('planning-shortcuts-btn')?.addEventListener('click', () => shortcutsModal?.show());
    document.getElementById('planning-sidebar-toggle')?.addEventListener('click', () => {
      const sidebar = document.getElementById('planning-sidebar');
      const btn = document.getElementById('planning-sidebar-toggle');
      if (!sidebar || !btn) return;

      const collapsed = sidebar.classList.toggle('is-collapsed');
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      btn.classList.toggle('active', !collapsed);
      btn.setAttribute('title', collapsed ? 'Afficher le panneau filtres' : 'Replier le panneau filtres');
      btn.setAttribute('aria-label', collapsed ? 'Afficher le panneau filtres' : 'Replier le panneau filtres');

      requestAnimationFrame(function () {
        calendar?.updateSize();
        if (miniCalendar && !document.getElementById('planning-mini-calendar-wrap')?.hasAttribute('hidden')) {
          miniCalendar.updateSize();
        }
      });
    });

    document.getElementById('planning-mini-cal-toggle')?.addEventListener('click', toggleMiniCalendar);
  }

  function toggleMiniCalendar() {
    const wrap = document.getElementById('planning-mini-calendar-wrap');
    const btn = document.getElementById('planning-mini-cal-toggle');
    if (!wrap || !btn) return;

    const isOpen = wrap.hasAttribute('hidden');
    if (isOpen) {
      wrap.removeAttribute('hidden');
      btn.setAttribute('aria-expanded', 'true');
      btn.classList.add('active');
      btn.setAttribute('title', 'Masquer le mini-calendrier');
      btn.setAttribute('aria-label', 'Masquer le mini-calendrier');
      if (miniCalendar) {
        requestAnimationFrame(function () {
          miniCalendar.updateSize();
        });
      }
    } else {
      wrap.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', 'false');
      btn.classList.remove('active');
      btn.setAttribute('title', 'Afficher le mini-calendrier');
      btn.setAttribute('aria-label', 'Afficher le mini-calendrier');
    }
  }

  function bindExport() {
    document.getElementById('planning-export-pdf-btn')?.addEventListener('click', (e) => {
      e.preventDefault();
      updateExportLink();
      window.location.href = document.getElementById('planning-export-pdf-btn').href;
    });

    document.getElementById('planning-export-ics-btn')?.addEventListener('click', (e) => {
      e.preventDefault();
      updateExportLink();
      window.location.href = document.getElementById('planning-export-ics-btn').href;
    });
  }

  function updatePeriodTitle(view) {
    if (!els.periodTitle) return;
    els.periodTitle.textContent = view.title;
  }

  /** Ouverture du formulaire création / édition */
  function openForm(eventData, start, end) {
    if (!config.canCreate && !eventData) return;
    hidePopover();
    clearFormErrors();
    els.conflictBanner?.classList.add('d-none');

    const isEdit = !!eventData;
    document.getElementById('planningEventOffcanvasLabel').textContent = isEdit ? 'Modifier l\'événement' : 'Nouvel événement';

    document.getElementById('event-id').value = isEdit ? eventData.id : '';
    document.getElementById('event-titre').value = isEdit ? eventData.title : '';
    document.getElementById('event-type').value = isEdit ? (eventData.extendedProps.type || 'cours') : 'cours';
    document.getElementById('event-lieu').value = isEdit ? (eventData.extendedProps.lieu || '') : '';
    document.getElementById('event-description').value = isEdit ? (eventData.extendedProps.description || '') : '';

    const startDate = isEdit ? eventData.start : (start || new Date());
    const endDate = isEdit ? (eventData.end || addHours(eventData.start, 2)) : (end || addHours(startDate, 2));

    document.getElementById('event-start').value = toLocalDatetime(startDate);
    document.getElementById('event-end').value = toLocalDatetime(endDate);
    document.getElementById('event-end').min = document.getElementById('event-start').value;
    validateEventDates();

    setSelectValue('event-cours', isEdit ? eventData.extendedProps.coursId : '');
    setSelectValue('event-classe', isEdit ? eventData.extendedProps.classeId : '');
    setSelectValue('event-enseignant', isEdit ? eventData.extendedProps.enseignantId : '');

    offcanvas?.show();
  }

  function bindForm() {
    if (!els.form) return;

    document.getElementById('event-start')?.addEventListener('change', (e) => {
      const endEl = document.getElementById('event-end');
      if (endEl && e.target.value) {
        endEl.min = e.target.value;
        if (!endEl.value || endEl.value <= e.target.value) {
          endEl.value = toLocalDatetime(addHours(new Date(e.target.value), 2));
        }
        validateEventDates();
      }
    });

    document.getElementById('event-end')?.addEventListener('change', validateEventDates);
    document.getElementById('event-end')?.addEventListener('input', validateEventDates);

    els.form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearFormErrors();

      const dateErrors = getEventDateErrors();
      if (Object.keys(dateErrors).length > 0) {
        showFormErrors(dateErrors);
        return;
      }

      const payload = {
        titre: document.getElementById('event-titre').value.trim(),
        type: document.getElementById('event-type').value,
        start: new Date(document.getElementById('event-start').value).toISOString(),
        end: new Date(document.getElementById('event-end').value).toISOString(),
        lieu: document.getElementById('event-lieu').value.trim(),
        description: document.getElementById('event-description').value.trim(),
        coursId: valOrNull('event-cours'),
        classeId: valOrNull('event-classe'),
        enseignantId: valOrNull('event-enseignant'),
      };

      const eventId = document.getElementById('event-id').value;
      const conflicts = await checkConflicts(payload, eventId || null);

      if (conflicts.length > 0 && !els.conflictBanner?.dataset.force) {
        showConflictBanner(conflicts);
        return;
      }
      els.conflictBanner?.removeAttribute('data-force');

      const method = eventId ? 'PATCH' : 'POST';
      const url = eventId ? config.apiEvents + '/' + eventId : config.apiEvents;

      const res = await apiRequest(url, method, payload);
      if (res.success) {
        offcanvas?.hide();
        calendar.refetchEvents();
        showToast(res.message || 'Événement enregistré.');
      } else if (res.errors) {
        showFormErrors(res.errors);
      } else {
        showToast(res.message || 'Erreur.', 'danger');
      }
    });
  }

  function showConflictBanner(conflicts) {
    if (!els.conflictBanner) return;
    const list = conflicts.map((c) => `<li>${escapeHtml(c.title)} — ${formatDatetime(c.start)}</li>`).join('');
    els.conflictBanner.innerHTML = `
      <strong>Conflit horaire détecté</strong>
      <ul class="mb-2 mt-1 small">${list}</ul>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-warning btn-sm" id="conflict-force-save">Enregistrer quand même</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="conflict-adjust">Modifier les horaires</button>
      </div>`;
    els.conflictBanner.classList.remove('d-none');
    document.getElementById('conflict-force-save')?.addEventListener('click', () => {
      els.conflictBanner.dataset.force = '1';
      els.form.requestSubmit();
    });
    document.getElementById('conflict-adjust')?.addEventListener('click', () => {
      els.conflictBanner.classList.add('d-none');
      document.getElementById('event-start')?.focus();
    });
  }

  async function checkConflicts(payload, excludeId) {
    const params = new URLSearchParams({
      start: payload.start,
      end: payload.end,
    });
    if (payload.classeId) params.set('classeId', payload.classeId);
    if (payload.enseignantId) params.set('enseignantId', payload.enseignantId);
    if (excludeId) params.set('excludeId', excludeId);

    const res = await fetch(config.apiConflicts + '?' + params.toString(), {
      headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    return data.conflicts || [];
  }

  async function handleEventMove(info, actionLabel) {
    const event = info.event;
    if (!event.extendedProps.editable) {
      info.revert();
      return;
    }

    const oldStart = info.oldEvent.start;
    const oldEnd = info.oldEvent.end;

    const payload = {
      start: event.start.toISOString(),
      end: (event.end || addHours(event.start, 2)).toISOString(),
    };

    const res = await apiRequest(config.apiEvents + '/' + event.id, 'PATCH', payload);

    if (res.success) {
      undoState = { id: event.id, start: oldStart, end: oldEnd };
      showToast(`Événement ${actionLabel}.`, 'success', true);
    } else {
      info.revert();
      showToast(res.message || 'Modification impossible.', 'danger');
    }
  }

  function bindPopover() {
    document.getElementById('popover-close')?.addEventListener('click', hidePopover);
    document.addEventListener('click', (e) => {
      if (!els.popover?.contains(e.target) && !e.target.closest('.fc-event')) {
        hidePopover();
      }
    });
  }

  function showPopover(fcEvent, anchorEl) {
    activePopoverEvent = fcEvent;
    deleteConfirmPending = false;
    const props = fcEvent.extendedProps;
    const type = props.type || 'autre';

    const badge = document.getElementById('popover-type-badge');
    badge.textContent = TYPE_LABELS[type] || type;
    badge.className = 'planning-popover__badge badge-role badge-' + type;

    document.getElementById('popover-title').textContent = fcEvent.title;

    const endTime = fcEvent.end ? formatTime(fcEvent.end) : '';
    document.getElementById('popover-datetime').innerHTML =
      '<i class="bi bi-clock" aria-hidden="true"></i>' +
      '<span>' + escapeHtml(formatDatetime(fcEvent.start)) + (endTime ? ' — ' + escapeHtml(endTime) : '') + '</span>';

    const metaRows = [];
    if (props.lieu) metaRows.push({ icon: 'geo-alt', label: 'Lieu', value: props.lieu });
    if (props.coursTitre) metaRows.push({ icon: 'journal-text', label: 'Cours', value: props.coursTitre });
    if (props.classeNom) metaRows.push({ icon: 'people', label: 'Classe', value: props.classeNom });
    if (props.enseignantNom) metaRows.push({ icon: 'person', label: 'Enseignant', value: props.enseignantNom });

    document.getElementById('popover-meta').innerHTML = metaRows.map(function (row) {
      return '<div class="planning-popover__row">' +
        '<i class="bi bi-' + row.icon + '" aria-hidden="true"></i>' +
        '<div class="planning-popover__row-content">' +
        '<span class="planning-popover__label">' + escapeHtml(row.label) + '</span>' +
        '<span class="planning-popover__value">' + escapeHtml(row.value) + '</span>' +
        '</div></div>';
    }).join('');

    const descWrap = document.getElementById('popover-description-wrap');
    const descEl = document.getElementById('popover-description');
    if (props.description) {
      descEl.textContent = props.description;
      descWrap.classList.remove('d-none');
    } else {
      descEl.textContent = '';
      descWrap.classList.add('d-none');
    }

    const actions = document.getElementById('popover-actions');
    actions.innerHTML = '';
    if (props.editable) {
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn btn-primary btn-sm flex-grow-1';
      editBtn.innerHTML = '<i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier';
      editBtn.addEventListener('click', function () {
        hidePopover();
        openForm(fcEvent);
      });
      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn btn-outline-danger btn-sm';
      delBtn.textContent = 'Supprimer';
      delBtn.addEventListener('click', function () {
        toggleDeleteConfirm(delBtn, fcEvent);
      });
      actions.append(editBtn, delBtn);
    }

    els.popover.classList.remove('d-none');
    positionPopover(anchorEl);
  }

  function positionPopover(anchorEl) {
    const pop = els.popover;
    const rect = anchorEl.getBoundingClientRect();
    const popW = pop.offsetWidth || 320;
    const popH = pop.offsetHeight || 280;
    const margin = 12;

    let left = rect.right + margin;
    let top = rect.top;

    if (left + popW > window.innerWidth - margin) {
      left = rect.left - popW - margin;
    }
    if (left < margin) {
      left = Math.max(margin, (window.innerWidth - popW) / 2);
    }
    if (top + popH > window.innerHeight - margin) {
      top = window.innerHeight - popH - margin;
    }
    top = Math.max(margin, top);

    pop.style.top = top + 'px';
    pop.style.left = left + 'px';
  }

  function toggleDeleteConfirm(btn, fcEvent) {
    if (!deleteConfirmPending) {
      deleteConfirmPending = true;
      btn.textContent = 'Confirmer la suppression';
      btn.classList.replace('btn-outline-danger', 'btn-danger');
      return;
    }
    deleteEvent(fcEvent.id);
  }

  async function deleteEvent(id) {
    const res = await apiRequest(config.apiEvents + '/' + id, 'DELETE', null);
    if (res.success) {
      hidePopover();
      calendar.refetchEvents();
      showToast('Événement supprimé.');
    } else {
      showToast(res.message || 'Suppression impossible.', 'danger');
    }
  }

  function hidePopover() {
    els.popover?.classList.add('d-none');
    activePopoverEvent = null;
    deleteConfirmPending = false;
  }

  function bindKeyboard() {
    document.addEventListener('keydown', (e) => {
      if (e.target.matches('input, textarea, select')) return;
      switch (e.key) {
        case 't': calendar.today(); break;
        case 'ArrowLeft': calendar.prev(); break;
        case 'ArrowRight': calendar.next(); break;
        case 'n':
          if (config.canCreate) openForm();
          break;
        case '1': calendar.changeView('timeGridDay'); break;
        case '2': calendar.changeView('timeGridWeek'); break;
        case '3': calendar.changeView('dayGridMonth'); break;
        case '4': calendar.changeView('listWeek'); break;
        case '?': shortcutsModal?.show(); break;
      }
    });
  }

  async function apiRequest(url, method, body) {
    const opts = {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-Token': config.csrfToken,
      },
    };
    if (body && method !== 'GET') opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    return res.json();
  }

  function showToast(message, type = 'success', withUndo = false) {
    const container = els.toastContainer;
    if (!container) return;

    const bg = type === 'danger' ? 'text-bg-danger' : 'text-bg-success';
    const toastEl = document.createElement('div');
    toastEl.className = `toast planning-toast ${bg}`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `<div class="d-flex align-items-center">
      <div class="toast-body">${escapeHtml(message)}</div>
      ${withUndo ? '<button type="button" class="btn btn-sm btn-light me-2 undo-btn">Annuler</button>' : ''}
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
    container.appendChild(toastEl);

    if (withUndo) {
      toastEl.querySelector('.undo-btn')?.addEventListener('click', async () => {
        if (undoState) {
          await apiRequest(config.apiEvents + '/' + undoState.id, 'PATCH', {
            start: undoState.start.toISOString(),
            end: (undoState.end || addHours(undoState.start, 2)).toISOString(),
          });
          calendar.refetchEvents();
          undoState = null;
        }
        bootstrap.Toast.getInstance(toastEl)?.hide();
      });
    }

    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  function getEventDateErrors() {
    const startEl = document.getElementById('event-start');
    const endEl = document.getElementById('event-end');
    if (!startEl?.value || !endEl?.value) return {};

    const start = new Date(startEl.value);
    const end = new Date(endEl.value);
    if (end <= start) {
      return { end: 'La date de fin doit être postérieure à la date de début.' };
    }

    return {};
  }

  function validateEventDates() {
    const errors = getEventDateErrors();
    const endEl = document.getElementById('event-end');
    if (!endEl) return;

    if (errors.end) {
      endEl.setCustomValidity(errors.end);
    } else {
      endEl.setCustomValidity('');
    }
  }

  function showFormErrors(errors) {
    Object.entries(errors).forEach(([field, msg]) => {
      const fb = document.querySelector(`[data-error="${field}"]`);
      const input = document.getElementById('event-' + field) || document.getElementById('event-' + field.replace('Id', ''));
      if (fb) fb.textContent = msg;
      if (input) input.classList.add('is-invalid');
    });
  }

  function clearFormErrors() {
    els.form?.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    els.form?.querySelectorAll('[data-error]').forEach((el) => (el.textContent = ''));
  }

  function setSelectValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = value || '';
    if (el.tomselect) {
      value ? el.tomselect.setValue(String(value)) : el.tomselect.clear();
    }
  }

  function valOrNull(id) {
    const v = document.getElementById(id)?.value;
    return v ? parseInt(v, 10) : null;
  }

  function toLocalDatetime(date) {
    const d = new Date(date);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function addHours(date, h) {
    return new Date(new Date(date).getTime() + h * 60 * 60 * 1000);
  }

  function formatDatetime(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) +
      ' · ' + formatTime(d);
  }

  function formatTime(d) {
    return new Date(d).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }
})();
