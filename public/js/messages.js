/**
 * MERJ Learn — Messagerie type WhatsApp / iMessage
 */
(function () {
  'use strict';

  const app = document.getElementById('messages-app');
  if (!app) return;

  const config = {
    csrfToken: app.dataset.csrfToken,
    initialPartnerId: parseInt(app.dataset.initialPartner, 10) || 0,
    currentUserId: parseInt(app.dataset.currentUserId, 10),
    globalViewer: app.dataset.globalViewer === '1',
    apiConversations: app.dataset.apiConversations,
    apiThread: app.dataset.apiThread,
    apiSend: app.dataset.apiSend,
    storageKey: 'merj-messages-scope',
  };

  const els = {
    shell: app.querySelector('.messages-shell'),
    list: document.getElementById('messages-list'),
    listLoading: document.getElementById('messages-list-loading'),
    search: document.getElementById('messages-search'),
    threadEmpty: document.getElementById('messages-thread-empty'),
    thread: document.getElementById('messages-thread'),
    threadBody: document.getElementById('messages-thread-body'),
    threadLoading: document.getElementById('messages-thread-loading'),
    threadName: document.getElementById('messages-thread-name'),
    threadRole: document.getElementById('messages-thread-role'),
    threadAvatar: document.getElementById('messages-thread-avatar'),
    composeForm: document.getElementById('messages-compose-form'),
    composeInput: document.getElementById('messages-compose-input'),
    sendBtn: document.getElementById('messages-send-btn'),
    backBtn: document.getElementById('messages-back-btn'),
    newBtn: document.getElementById('messages-new-btn'),
    emptyNewBtn: document.getElementById('messages-empty-new-btn'),
    newModal: document.getElementById('messagesNewModal'),
    contactSearch: document.getElementById('messages-contact-search'),
    contactList: document.getElementById('messages-contact-list'),
    infoBtn: document.getElementById('messages-info-btn'),
    observerNotice: document.getElementById('messages-observer-notice'),
    composeFooter: document.querySelector('.messages-compose'),
    subtitle: document.getElementById('messages-subtitle'),
    scopeToggle: document.getElementById('messages-scope-toggle'),
  };

  let conversations = [];
  let threadMessages = [];
  let activePartnerId = null;
  let activeWithUserId = null;
  let activePartner = null;
  let observerMode = false;
  let viewScope = 'personal';
  let pollTimer = null;
  let newModalInstance = null;

  function init() {
    if (els.newModal && typeof bootstrap !== 'undefined') {
      newModalInstance = new bootstrap.Modal(els.newModal);
    }

    if (config.globalViewer) {
      const savedScope = localStorage.getItem(config.storageKey);
      viewScope = savedScope === 'global' ? 'global' : 'personal';
    }
    updateScopeUi();

    bindEvents();
    loadConversations().then(() => {
      const initialWith = parseInt(new URL(window.location.href).searchParams.get('with') || '0', 10) || null;
      if (config.initialPartnerId > 0) {
        openThread(config.initialPartnerId, initialWith);
      }
    });
    startPolling();
  }

  function bindEvents() {
    els.search?.addEventListener('input', renderConversationList);
    els.composeForm?.addEventListener('submit', onSend);
    els.composeInput?.addEventListener('input', onComposeInput);
    els.composeInput?.addEventListener('keydown', onComposeKeydown);
    els.backBtn?.addEventListener('click', closeThreadMobile);
    els.newBtn?.addEventListener('click', openNewModal);
    els.emptyNewBtn?.addEventListener('click', openNewModal);
    els.contactSearch?.addEventListener('input', filterContactList);
    els.contactList?.addEventListener('click', onContactPick);
    els.infoBtn?.addEventListener('click', showPartnerInfo);
    els.scopeToggle?.addEventListener('click', onScopeToggle);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopPolling();
      } else {
        startPolling();
        if (activePartnerId) refreshThread(activePartnerId, activeWithUserId, false);
        loadConversations();
      }
    });
  }

  function apiUrl(template, partnerId, withUserId) {
    let url = template.replace(/\/0$/, '/' + String(partnerId));
    if (withUserId) {
      url += (url.includes('?') ? '&' : '?') + 'with=' + String(withUserId);
    }
    return url;
  }

  function conversationsApiUrl() {
    const url = new URL(config.apiConversations, window.location.origin);
    if (config.globalViewer) {
      url.searchParams.set('scope', viewScope);
    }
    return url.toString();
  }

  function onScopeToggle(e) {
    const btn = e.target.closest('[data-scope]');
    if (!btn || btn.dataset.scope === viewScope) return;

    viewScope = btn.dataset.scope === 'global' ? 'global' : 'personal';
    localStorage.setItem(config.storageKey, viewScope);
    updateScopeUi();
    closeThreadMobile();
    showListLoading();
    loadConversations();
  }

  function showListLoading() {
    if (!els.list || !els.listLoading) return;
    els.list.innerHTML = '';
    els.list.appendChild(els.listLoading);
    els.listLoading.classList.remove('d-none');
  }

  function updateScopeUi() {
    if (!config.globalViewer) return;

    els.scopeToggle?.querySelectorAll('[data-scope]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.scope === viewScope);
    });

    if (els.subtitle) {
      els.subtitle.textContent = viewScope === 'global'
        ? 'Vue globale — toutes les conversations de la plateforme'
        : 'Vos conversations personnelles';
    }

    const hideNew = viewScope === 'global';
    if (els.newBtn) {
      els.newBtn.disabled = hideNew;
      els.newBtn.classList.toggle('d-none', hideNew);
    }
    if (els.emptyNewBtn) {
      els.emptyNewBtn.classList.toggle('d-none', hideNew);
    }
  }

  async function loadConversations() {
    try {
      const res = await fetch(conversationsApiUrl(), { headers: { Accept: 'application/json' } });
      const data = await res.json();
      if (!data.success) return;

      if (data.scope) {
        viewScope = data.scope;
        updateScopeUi();
      }

      conversations = data.conversations || [];
      renderConversationList();
      if (typeof data.totalUnread !== 'undefined') {
        updateNavUnreadBadge(data.totalUnread);
      }
    } catch (e) {
      console.error('Conversations load error', e);
    } finally {
      els.listLoading?.classList.add('d-none');
    }
  }

  function renderConversationList() {
    if (!els.list) return;

    const query = (els.search?.value || '').trim().toLowerCase();
    const filtered = conversations.filter((conv) => {
      if (!query) return true;
      const name = (conv.displayName || conv.partner?.name || '').toLowerCase();
      const preview = conv.preview?.toLowerCase() || '';
      return name.includes(query) || preview.includes(query);
    });

    if (filtered.length === 0) {
      els.list.innerHTML = '<p class="messages-list__empty">Aucune conversation trouvée.</p>';
      return;
    }

    els.list.innerHTML = filtered.map((conv) => {
      const partner = conv.partner;
      const isObserver = conv.observerMode === true;
      const partnerIds = conv.participantIds || [partner.id];
      const withUserId = isObserver ? partnerIds[0] : null;
      const threadPartnerId = isObserver ? partnerIds[1] : partner.id;
      const isActive = isObserver
        ? activePartnerId === threadPartnerId && activeWithUserId === withUserId
        : activePartnerId === partner.id && !activeWithUserId;
      const unread = conv.unreadCount > 0;
      const previewPrefix = conv.isMine && conv.lastMessage ? 'Vous : ' : '';
      const previewClass = unread ? 'messages-conversation-item__preview messages-conversation-item__preview--unread' : 'messages-conversation-item__preview';
      const displayName = conv.displayName || partner.name;
      const initials = isObserver ? '↔' : partner.initials;

      return `
        <button type="button"
                class="messages-conversation-item${isActive ? ' is-active' : ''}${isObserver ? ' messages-conversation-item--observer' : ''}"
                data-partner-id="${threadPartnerId}"
                data-with-user-id="${withUserId || ''}"
                data-observer-mode="${isObserver ? '1' : '0'}"
                role="option"
                aria-selected="${isActive}">
          <span class="messages-avatar" aria-hidden="true">${escapeHtml(initials)}</span>
          <span class="messages-conversation-item__body">
            <span class="messages-conversation-item__top">
              <span class="messages-conversation-item__name">${escapeHtml(displayName)}</span>
              <span class="messages-conversation-item__time">${formatListTime(conv.sentAt)}</span>
            </span>
            <p class="${previewClass}">${escapeHtml(previewPrefix + (conv.preview || ''))}</p>
          </span>
          ${unread ? `<span class="messages-conversation-item__badge">${conv.unreadCount > 9 ? '9+' : conv.unreadCount}</span>` : ''}
        </button>
      `;
    }).join('');

    els.list.querySelectorAll('.messages-conversation-item').forEach((btn) => {
      btn.addEventListener('click', () => {
        const partnerId = parseInt(btn.dataset.partnerId, 10);
        const withUserId = parseInt(btn.dataset.withUserId, 10) || null;
        openThread(partnerId, withUserId);
      });
    });
  }

  async function openThread(partnerId, withUserId = null) {
    if (!partnerId) return;

    activePartnerId = partnerId;
    activeWithUserId = withUserId;
    observerMode = withUserId !== null && withUserId > 0;
    threadMessages = [];
    renderConversationList();

    els.threadEmpty?.classList.add('d-none');
    els.thread?.classList.remove('d-none');
    els.shell?.classList.add('is-thread-open');

    els.threadBody.innerHTML = '';
    els.threadLoading?.classList.remove('d-none');

    updateThreadUrl(partnerId, withUserId);
    setComposeMode(observerMode);

    await refreshThread(partnerId, withUserId, true);
  }

  async function refreshThread(partnerId, withUserId, scrollToEnd) {
    try {
      const res = await fetch(apiUrl(config.apiThread, partnerId, withUserId), { headers: { Accept: 'application/json' } });
      const data = await res.json();

      if (!data.success) {
        showToast('Conversation introuvable.', 'danger');
        return;
      }

      observerMode = data.observerMode === true;
      activePartner = data.partner;
      threadMessages = data.messages || [];
      updateThreadHeader(data);
      setComposeMode(observerMode);
      renderMessages(threadMessages, scrollToEnd);
      if (typeof data.totalUnread !== 'undefined') {
        updateNavUnreadBadge(data.totalUnread);
      }
      loadConversations();
    } catch (e) {
      console.error('Thread load error', e);
      showToast('Impossible de charger la conversation.', 'danger');
    } finally {
      els.threadLoading?.classList.add('d-none');
    }
  }

  function updateThreadHeader(data) {
    const partner = data.partner;
    if (!partner) return;
    const title = data.displayName || partner.name;
    const role = data.observerMode && data.participants
      ? data.participants.map((p) => p.role).join(' · ')
      : partner.role;
    if (els.threadName) els.threadName.textContent = title;
    if (els.threadRole) els.threadRole.textContent = role;
    if (els.threadAvatar) els.threadAvatar.textContent = data.observerMode ? '↔' : partner.initials;
  }

  function setComposeMode(isObserver) {
    els.observerNotice?.classList.toggle('d-none', !isObserver);
    els.composeFooter?.classList.toggle('d-none', isObserver);
  }

  function renderMessages(messages, scrollToEnd) {
    if (!els.threadBody) return;

    let html = '';
    let lastDay = '';

    messages.forEach((msg, index) => {
      const dayKey = msg.sentAt ? msg.sentAt.slice(0, 10) : '';
      if (dayKey && dayKey !== lastDay) {
        html += `<div class="messages-day-separator"><span>${formatDayLabel(msg.sentAt)}</span></div>`;
        lastDay = dayKey;
      }
      html += renderBubble(msg, index > 0 ? messages[index - 1] : null);
    });

    if (!html) {
      html = '<p class="messages-list__empty">Aucun message. Envoyez le premier !</p>';
    }

    els.threadBody.innerHTML = html;

    if (scrollToEnd) {
      requestAnimationFrame(() => {
        els.threadBody.scrollTop = els.threadBody.scrollHeight;
      });
    }
  }

  function isSameSender(a, b) {
    if (!a || !b) return false;
    if (a.isMine !== b.isMine) return false;
    if (a.isMine) return true;
    return a.expediteur?.id === b.expediteur?.id;
  }

  function renderBubble(msg, prevMsg) {
    const mine = msg.isMine;
    const grouped = isSameSender(msg, prevMsg);
    const rowClass = [
      'messages-bubble-row',
      mine ? 'messages-bubble-row--mine' : 'messages-bubble-row--theirs',
      grouped ? 'messages-bubble-row--grouped' : '',
    ].filter(Boolean).join(' ');
    const bubbleClass = mine ? 'messages-bubble messages-bubble--mine' : 'messages-bubble messages-bubble--theirs';
    const time = formatMessageTime(msg.sentAt);
    const status = mine ? renderStatus(msg) : '';

    let avatarHtml = '';
    if (!mine) {
      if (!grouped) {
        avatarHtml = `<span class="messages-avatar messages-avatar--sm" aria-hidden="true">${escapeHtml(msg.expediteur?.initials || '')}</span>`;
      } else {
        avatarHtml = '<span class="messages-bubble-row__avatar-spacer" aria-hidden="true"></span>';
      }
    }

    return `
      <div class="${rowClass}">
        ${avatarHtml}
        <div class="${bubbleClass}">
          <span class="messages-bubble__text">${escapeHtml(msg.contenu)}</span>
          <span class="messages-bubble__meta">
            <span>${time}</span>
            ${status}
          </span>
        </div>
      </div>
    `;
  }

  function renderStatus(msg) {
    if (msg.isRead) {
      return '<span class="messages-bubble__status messages-bubble__status--read" title="Lu"><i class="bi bi-check2-all"></i></span>';
    }
    return '<span class="messages-bubble__status" title="Envoyé"><i class="bi bi-check2"></i></span>';
  }

  async function onSend(e) {
    e.preventDefault();
    if (!activePartnerId || !els.composeInput || observerMode) return;

    const contenu = els.composeInput.value.trim();
    if (!contenu) return;

    els.sendBtn.disabled = true;

    try {
      const res = await fetch(config.apiSend, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          partnerId: activePartnerId,
          contenu,
          _token: config.csrfToken,
        }),
      });

      const data = await res.json();
      if (!data.success) {
        showToast(data.message || 'Envoi impossible.', 'danger');
        return;
      }

      els.composeInput.value = '';
      autoResizeTextarea();
      onComposeInput();

      appendMessage(data.message);
      loadConversations();
    } catch (err) {
      console.error('Send error', err);
      showToast('Erreur réseau lors de l\'envoi.', 'danger');
    } finally {
      els.sendBtn.disabled = !els.composeInput?.value.trim();
    }
  }

  function appendMessage(msg) {
    const empty = els.threadBody.querySelector('.messages-list__empty');
    if (empty) empty.remove();

    const prev = threadMessages.length > 0 ? threadMessages[threadMessages.length - 1] : null;
    threadMessages.push(msg);

    const lastSeparator = els.threadBody.querySelector('.messages-day-separator:last-of-type');
    const dayLabel = formatDayLabel(msg.sentAt);

    if (!lastSeparator || !lastSeparator.textContent.toLowerCase().includes(dayLabel.split(' ')[0].toLowerCase())) {
      const existingDays = Array.from(els.threadBody.querySelectorAll('.messages-day-separator span')).map((el) => el.textContent);
      if (!existingDays.some((d) => d === dayLabel)) {
        els.threadBody.insertAdjacentHTML('beforeend', `<div class="messages-day-separator"><span>${dayLabel}</span></div>`);
      }
    }

    els.threadBody.insertAdjacentHTML('beforeend', renderBubble(msg, prev));
    els.threadBody.scrollTop = els.threadBody.scrollHeight;
  }

  function onComposeInput() {
    autoResizeTextarea();
    if (els.sendBtn) {
      els.sendBtn.disabled = !(els.composeInput?.value.trim());
    }
  }

  function onComposeKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      els.composeForm?.requestSubmit();
    }
  }

  function autoResizeTextarea() {
    if (!els.composeInput) return;
    els.composeInput.style.height = 'auto';
    els.composeInput.style.height = Math.min(els.composeInput.scrollHeight, 120) + 'px';
  }

  function closeThreadMobile() {
    els.shell?.classList.remove('is-thread-open');
    activePartnerId = null;
    activeWithUserId = null;
    activePartner = null;
    observerMode = false;
    setComposeMode(false);
    updateThreadUrl(0, null);
    renderConversationList();
  }

  function openNewModal() {
    if (viewScope === 'global') return;
    newModalInstance?.show();
    if (els.contactSearch) {
      els.contactSearch.value = '';
      filterContactList();
    }
  }

  function onContactPick(e) {
    const btn = e.target.closest('[data-partner-id]');
    if (!btn) return;
    const partnerId = parseInt(btn.dataset.partnerId, 10);
    newModalInstance?.hide();
    openThread(partnerId);
  }

  function filterContactList() {
    if (!els.contactList || !els.contactSearch) return;
    const query = els.contactSearch.value.trim().toLowerCase();
    els.contactList.querySelectorAll('.messages-contact-item').forEach((item) => {
      const name = item.querySelector('.messages-contact-item__name')?.textContent?.toLowerCase() || '';
      const meta = item.querySelector('.messages-contact-item__meta')?.textContent?.toLowerCase() || '';
      item.classList.toggle('d-none', query !== '' && !name.includes(query) && !meta.includes(query));
    });
  }

  function showPartnerInfo() {
    if (!activePartner) return;
    showToast(`${activePartner.name} · ${activePartner.email}`, 'info');
  }

  function startPolling() {
    stopPolling();
    pollTimer = window.setInterval(() => {
      if (activePartnerId) {
        refreshThread(activePartnerId, activeWithUserId, false);
      } else {
        loadConversations();
      }
    }, 15000);
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  function updateThreadUrl(partnerId, withUserId) {
    const url = new URL(window.location.href);
    if (partnerId > 0) {
      url.searchParams.set('user', String(partnerId));
      if (withUserId) {
        url.searchParams.set('with', String(withUserId));
      } else {
        url.searchParams.delete('with');
      }
    } else {
      url.searchParams.delete('user');
      url.searchParams.delete('with');
    }
    history.replaceState(null, '', url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : ''));
  }

  function formatListTime(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    const now = new Date();
    const diff = now - date;

    if (diff < 86400000 && date.getDate() === now.getDate()) {
      return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }
    if (diff < 604800000) {
      return date.toLocaleDateString('fr-FR', { weekday: 'short' });
    }
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
  }

  function formatMessageTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }

  function formatDayLabel(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === now.toDateString()) return "Aujourd'hui";
    if (date.toDateString() === yesterday.toDateString()) return 'Hier';

    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showToast(message, type) {
    if (typeof window.showAppToast === 'function') {
      window.showAppToast(message, type);
      return;
    }
    console.log(`[${type}] ${message}`);
  }

  function updateNavUnreadBadge(count) {
    document.querySelectorAll('[data-nav-messages-unread]').forEach((badge) => {
      const value = Math.max(0, parseInt(count, 10) || 0);
      if (value <= 0) {
        badge.classList.add('d-none');
        badge.textContent = '0';
        badge.setAttribute('aria-label', '0 message non lu');
        return;
      }

      badge.classList.remove('d-none');
      badge.textContent = value > 99 ? '99+' : String(value);
      badge.setAttribute('aria-label', `${value} message(s) non lu(s)`);
    });
  }

  window.updateMessagesUnreadBadge = updateNavUnreadBadge;

  init();
})();
