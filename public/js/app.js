document.addEventListener('DOMContentLoaded', function () {
  initTomSelect();
  initConfirmModal();
  initToasts();
  initDropdownElevation();
  initPasswordToggle();
});

function initPasswordToggle() {
  document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = btn.closest('.input-group')?.querySelector('input');
      const icon = btn.querySelector('i');
      if (!input || !icon) {
        return;
      }

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      icon.classList.toggle('bi-eye', !isPassword);
      icon.classList.toggle('bi-eye-slash', isPassword);
      btn.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
  });
}

function initTomSelect() {
  if (typeof TomSelect === 'undefined') {
    return;
  }

  document.querySelectorAll('select:not(.no-tom-select)').forEach(function (el) {
    if (el.tomselect || el.closest('.ts-wrapper')) {
      return;
    }

    const placeholder = el.getAttribute('placeholder') || el.dataset.placeholder || '';

    new TomSelect(el, {
      create: false,
      allowEmptyOption: true,
      placeholder: placeholder,
      plugins: el.multiple ? ['remove_button', 'clear_button'] : ['clear_button'],
      render: {
        no_results: function () {
          return '<div class="no-results">Aucun résultat</div>';
        }
      }
    });
  });
}

function initConfirmModal() {
  var modalEl = document.getElementById('confirmModal');
  if (!modalEl) {
    return;
  }

  var modal = new bootstrap.Modal(modalEl);
  var titleEl = modalEl.querySelector('[data-confirm-title]');
  var bodyEl = modalEl.querySelector('[data-confirm-body]');
  var confirmBtn = modalEl.querySelector('[data-confirm-submit]');
  var pendingForm = null;

  document.querySelectorAll('[data-confirm-delete]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var formId = trigger.getAttribute('data-confirm-form');
      var form = formId ? document.getElementById(formId) : trigger.closest('form');
      if (!form) {
        return;
      }

      pendingForm = form;
      var name = trigger.getAttribute('data-confirm-name') || 'cet élément';
      var message = trigger.getAttribute('data-confirm-message') || 'Cette action est irréversible.';

      if (titleEl) {
        titleEl.textContent = 'Supprimer ' + name + ' ?';
      }
      if (bodyEl) {
        bodyEl.textContent = message;
      }

      modal.show();
    });
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (pendingForm) {
        pendingForm.submit();
        pendingForm = null;
      }
      modal.hide();
    });
  }
}

function initToasts() {
  document.querySelectorAll('.toast-app').forEach(function (toastEl) {
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
  });
}

function initDropdownElevation() {
  document.addEventListener('show.bs.dropdown', function (event) {
    var container = event.target.closest('.dropdown, .btn-group');
    if (!container) {
      return;
    }
    var elevate = container.closest('.card, [class*="col-"], .table-responsive, tr, .list-group-item');
    if (elevate) {
      elevate.classList.add('dropdown-elevated');
    }
  });

  document.addEventListener('hide.bs.dropdown', function () {
    document.querySelectorAll('.dropdown-elevated').forEach(function (element) {
      element.classList.remove('dropdown-elevated');
    });
  });
}
