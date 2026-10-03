/* ============================================================
   TechSense — Main JavaScript
   Handles: Wizard, Step Guide, Charts helpers, AJAX, UI utils
============================================================ */
'use strict';

// ── DOM Ready ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initWizard();
  initStepGuide();
  initDismissibleAlerts();
  initTooltips();
  initSearchDebounce();
  initNotificationBell();
  highlightActiveNav();
});

// ── Sidebar Toggle (mobile) ──────────────────────────────
function initSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.ts-sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('d-none');
  });
  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.add('d-none');
    });
  }
}

// ── Highlight active sidebar link ────────────────────────
function highlightActiveNav() {
  const path = window.location.pathname;
  document.querySelectorAll('.ts-nav-link').forEach(link => {
    if (link.getAttribute('href') && path.endsWith(link.getAttribute('href').split('/').pop())) {
      link.classList.add('active');
    }
  });
}

// ── Auto-dismiss Flash Alerts ────────────────────────────
function initDismissibleAlerts() {
  setTimeout(() => {
    document.querySelectorAll('.alert-success, .alert-info').forEach(el => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      if (bsAlert) bsAlert.close();
    });
  }, 4000);
}

// ── Bootstrap Tooltips ───────────────────────────────────
function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });
}

// ── Troubleshooting Wizard ───────────────────────────────
function initWizard() {
  if (!document.getElementById('ts-wizard')) return;

  const categoryCards = document.querySelectorAll('.ts-category-select-card');
  const categoryInput = document.getElementById('selectedCategory');
  const nextToStep2  = document.getElementById('nextToStep2');
  const nextToStep3  = document.getElementById('nextToStep3');
  const nextToStep4  = document.getElementById('nextToStep4');
  const symptomsBox  = document.getElementById('symptomsBox');
  const loadingSymptoms = document.getElementById('loadingSymptoms');

  // Category selection
  categoryCards.forEach(card => {
    card.addEventListener('click', () => {
      categoryCards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      if (categoryInput) categoryInput.value = card.dataset.catId;
      if (nextToStep2) nextToStep2.disabled = false;
    });
  });

  // Next: step 1 → 2
  if (nextToStep2) {
    nextToStep2.addEventListener('click', () => {
      const cat = categoryInput ? categoryInput.value : '';
      if (!cat) { showToast('Please select a problem category.', 'warning'); return; }
      goToStep(2);
    });
  }

  // Next: step 2 → 3 (load symptoms via AJAX)
  if (nextToStep3) {
    nextToStep3.addEventListener('click', () => {
      const desc = document.getElementById('problemDescription');
      if (desc && desc.value.trim().length < 10) {
        showToast('Please describe your problem in at least 10 characters.', 'warning');
        return;
      }
      const catId = categoryInput ? categoryInput.value : '';
      if (symptomsBox && catId) {
        if (loadingSymptoms) loadingSymptoms.classList.remove('d-none');
        symptomsBox.innerHTML = '';
        fetch(`/techsense/api/get-symptoms.php?category_id=${catId}`)
          .then(r => r.json())
          .then(data => {
            if (loadingSymptoms) loadingSymptoms.classList.add('d-none');
            if (data.symptoms && data.symptoms.length > 0) {
              data.symptoms.forEach(s => {
                symptomsBox.insertAdjacentHTML('beforeend', `
                  <div class="ts-symptom-chip">
                    <input type="checkbox" id="sym_${s.id}" name="symptoms[]" value="${s.id}">
                    <label for="sym_${s.id}"><i class="bi bi-check-circle-fill text-ts-accent"></i>${escapeHtml(s.symptom_name)}</label>
                  </div>`);
              });
            } else {
              symptomsBox.innerHTML = '<p class="text-muted small">No symptoms available for this category.</p>';
            }
          })
          .catch(() => {
            if (loadingSymptoms) loadingSymptoms.classList.add('d-none');
            symptomsBox.innerHTML = '<p class="text-danger small">Failed to load symptoms. Please try again.</p>';
          });
      }
      goToStep(3);
    });
  }

  // Next: step 3 → 4 (submit analysis)
  if (nextToStep4) {
    nextToStep4.addEventListener('click', () => {
      const checked = document.querySelectorAll('input[name="symptoms[]"]:checked');
      if (checked.length === 0) {
        showToast('Please select at least one symptom.', 'warning');
        return;
      }
      // Submit the wizard form
      const form = document.getElementById('wizardForm');
      if (form) form.submit();
    });
  }
}

function goToStep(stepNum) {
  document.querySelectorAll('.ts-wizard-panel').forEach(p => p.classList.add('d-none'));
  const panel = document.getElementById(`step${stepNum}`);
  if (panel) panel.classList.remove('d-none');

  // Update step indicators
  document.querySelectorAll('.ts-wizard-step').forEach((s, idx) => {
    const n = idx + 1;
    s.classList.remove('active', 'done');
    if (n < stepNum) s.classList.add('done');
    if (n === stepNum) s.classList.add('active');
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Step-by-Step Guide Controller ───────────────────────
function initStepGuide() {
  const guide = document.getElementById('ts-step-guide');
  if (!guide) return;

  const steps       = JSON.parse(guide.dataset.steps || '[]');
  const sessionId   = guide.dataset.sessionId || '';
  let currentStep   = 0;

  const stepCounter = document.getElementById('stepCounter');
  const stepTitle   = document.getElementById('stepTitle');
  const stepInstr   = document.getElementById('stepInstruction');
  const dotsWrap    = document.getElementById('stepDots');
  const btnFixed    = document.getElementById('btnFixed');
  const btnContinue = document.getElementById('btnContinue');
  const finalPanel  = document.getElementById('finalPanel');
  const guidePanel  = document.getElementById('guidePanel');

  if (!steps.length) return;

  function renderStep() {
    const step = steps[currentStep];
    if (!step) return;
    if (stepCounter) stepCounter.textContent = `Step ${currentStep + 1} of ${steps.length}`;
    if (stepTitle)   stepTitle.textContent   = `Step ${currentStep + 1}`;
    if (stepInstr)   stepInstr.textContent   = step.instruction;

    // Dots
    if (dotsWrap) {
      dotsWrap.innerHTML = '';
      steps.forEach((_, i) => {
        const dot = document.createElement('div');
        dot.className = 'ts-guide-step-dot' +
          (i < currentStep ? ' done' : i === currentStep ? ' active' : '');
        dotsWrap.appendChild(dot);
      });
    }

    if (btnContinue) {
      btnContinue.textContent = currentStep === steps.length - 1 ? 'Finish Guide' : 'Continue →';
    }
  }

  if (btnFixed) {
    btnFixed.addEventListener('click', () => {
      markResolved('resolved', sessionId);
    });
  }
  if (btnContinue) {
    btnContinue.addEventListener('click', () => {
      if (currentStep < steps.length - 1) {
        currentStep++;
        renderStep();
      } else {
        // Show final panel
        if (guidePanel) guidePanel.classList.add('d-none');
        if (finalPanel) finalPanel.classList.remove('d-none');
      }
    });
  }

  document.querySelectorAll('.btn-final-resolved').forEach(b => {
    b.addEventListener('click', () => markResolved(b.dataset.result, sessionId));
  });

  renderStep();
}

function markResolved(result, sessionId) {
  if (!sessionId) return;
  fetch('/techsense/api/save-session.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ session_id: sessionId, result: result })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      window.location.href = `/techsense/user/feedback.php?session_id=${sessionId}`;
    }
  })
  .catch(() => {
    window.location.href = `/techsense/user/feedback.php?session_id=${sessionId}`;
  });
}

// ── Search Debounce ──────────────────────────────────────
function initSearchDebounce() {
  const inp = document.getElementById('liveSearch');
  if (!inp) return;
  let timer;
  inp.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const q = inp.value.trim();
      if (q.length >= 2) {
        fetch(`/techsense/api/search.php?q=${encodeURIComponent(q)}`)
          .then(r => r.json())
          .then(data => renderSearchResults(data))
          .catch(() => {});
      } else {
        const box = document.getElementById('searchResults');
        if (box) box.innerHTML = '';
      }
    }, 350);
  });
}

function renderSearchResults(data) {
  const box = document.getElementById('searchResults');
  if (!box) return;
  if (!data.results || data.results.length === 0) {
    box.innerHTML = '<p class="text-muted small p-2">No results found.</p>';
    return;
  }
  box.innerHTML = data.results.map(r => `
    <a href="/techsense/user/knowledge-base.php?problem_id=${r.id}" class="d-block p-2 text-decoration-none border-bottom search-result-item">
      <div class="fw-semibold text-navy small">${escapeHtml(r.problem_name)}</div>
      <div class="text-muted" style="font-size:0.78rem;">${escapeHtml(r.category_name)}</div>
    </a>
  `).join('');
}

// ── Notification Bell ────────────────────────────────────
function initNotificationBell() {
  // Badge auto-refresh every 60s
  const badge = document.querySelector('.navbar .badge.bg-danger');
  if (!badge) return;
  setInterval(() => {
    fetch('/techsense/api/notifications.php?action=count')
      .then(r => r.json())
      .then(d => {
        if (d.count !== undefined) {
          badge.textContent = d.count;
          badge.style.display = d.count > 0 ? '' : 'none';
        }
      })
      .catch(() => {});
  }, 60000);
}

// ── Toast Utility ────────────────────────────────────────
function showToast(message, type = 'info') {
  let container = document.getElementById('ts-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'ts-toast-container';
    container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
    document.body.appendChild(container);
  }
  const colorMap = { success: '#198754', danger: '#dc3545', warning: '#ffc107', info: '#0dcaf0' };
  const toast = document.createElement('div');
  toast.style.cssText = `background:#1b2d42;color:#fff;padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;max-width:320px;border-left:4px solid ${colorMap[type] || '#0dcaf0'};box-shadow:0 4px 16px rgba(0,0,0,.3);`;
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// ── Escape HTML ──────────────────────────────────────────
function escapeHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}

// ── Global Chart.js defaults ─────────────────────────────
if (typeof Chart !== 'undefined') {
  Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#6c757d';
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.padding = 16;

  // Color palette
  window.TS_COLORS = {
    blue:    '#1a6eb5',
    accent:  '#00d4aa',
    orange:  '#f39c12',
    red:     '#e74c3c',
    purple:  '#9b59b6',
    green:   '#27ae60',
    gray:    '#95a5a6',
    navy:    '#0d1b2a',
  };
  window.TS_PALETTE = Object.values(TS_COLORS);
}
