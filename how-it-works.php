<?php
// ============================================================
// TechSense — How It Works
// ============================================================
$pageTitle = 'How It Works';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-navy text-white text-center">
  <div class="container py-4">
    <span class="badge bg-primary-subtle text-info fw-semibold px-3 py-2 rounded-pill mb-3">User Guide</span>
    <h1 class="display-5 fw-bold mb-3">How TechSense Solves IT Problems</h1>
    <p class="lead text-white-50 max-w-700 mx-auto">
      Learn how our step-by-step diagnostic workflow maps your symptoms to validated solutions.
    </p>
  </div>
</div>

<div class="py-5 bg-white">
  <div class="container py-3 max-w-900 mx-auto">
    
    <!-- Step 1 -->
    <div class="d-flex gap-4 mb-5">
      <div class="flex-shrink-0">
        <div class="ts-step-circle m-0">1</div>
      </div>
      <div>
        <h4 class="fw-bold text-navy">Category Selection</h4>
        <p class="text-muted">
          IT issues stem from diverse hardware and logical layers. Choosing a specific category (e.g. <em>Computer Hardware, Operating System, Internet/Network, Printer</em>) isolates the problem domain, ensuring that relevant diagnostic checks are loaded immediately.
        </p>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="d-flex gap-4 mb-5">
      <div class="flex-shrink-0">
        <div class="ts-step-circle m-0">2</div>
      </div>
      <div>
        <h4 class="fw-bold text-navy">Problem Narrative Input</h4>
        <p class="text-muted">
          Users describe what they are experiencing in plain text (e.g. <em>"My computer turns on and fans spin, but the monitor remains blank with no signal"</em>). The recommendation engine parses critical IT keywords (e.g. "display", "wifi", "overheat", "reboot") to apply contextual weighting.
        </p>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="d-flex gap-4 mb-5">
      <div class="flex-shrink-0">
        <div class="ts-step-circle m-0">3</div>
      </div>
      <div>
        <h4 class="fw-bold text-navy">Symptom Checklist Identification</h4>
        <p class="text-muted">
          Based on the selected category, a curated list of specific symptoms is presented (such as <em>No Display, RAM Not Detected, Strange Clicking Sounds, IP Conflict</em>). Users select all symptoms observed on the target machine.
        </p>
      </div>
    </div>

    <!-- Step 4 -->
    <div class="d-flex gap-4 mb-5">
      <div class="flex-shrink-0">
        <div class="ts-step-circle m-0">4</div>
      </div>
      <div>
        <h4 class="fw-bold text-navy">Scoring &amp; Recommendation Engine</h4>
        <p class="text-muted">
          The algorithm matches the selected symptoms against database-defined problem vectors:
        </p>
        <div class="bg-light p-3 rounded border small mb-2 font-monospace">
          Score (%) = (Matched Symptoms / Total Problem Symptoms) × 100 + Keyword Bonus
        </div>
        <p class="text-muted">
          Problems with the highest relevance are ranked and presented with confidence ratings, severity badges, and time estimates.
        </p>
      </div>
    </div>

    <!-- Step 5 -->
    <div class="d-flex gap-4 mb-5">
      <div class="flex-shrink-0">
        <div class="ts-step-circle m-0">5</div>
      </div>
      <div>
        <h4 class="fw-bold text-navy">Interactive Step-by-Step Resolution</h4>
        <p class="text-muted">
          Users do not have to absorb a dense page of troubleshooting advice at once. The interactive guide presents one physical check or command at a time. At each step, clicking <strong>"Problem Fixed"</strong> saves your session and prompts for feedback.
        </p>
      </div>
    </div>

  </div>
</div>

<!-- FAQ Accordion -->
<div class="py-5 bg-light">
  <div class="container py-3 max-w-800 mx-auto">
    <h3 class="fw-bold text-navy text-center mb-4">Frequently Asked Questions</h3>
    
    <div class="accordion shadow-sm" id="faqAccordion">
      <div class="accordion-item border-0 mb-2 rounded overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
            Does TechSense require an active internet connection to troubleshoot?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Yes, TechSense is a web application running on local intranet or cloud hosting. You can access it from a smartphone, tablet, or secondary laptop while troubleshooting an offline desktop or network switch.
          </div>
        </div>
      </div>

      <div class="accordion-item border-0 mb-2 rounded overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            Is my troubleshooting history saved?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Yes! Every completed troubleshooting session is permanently associated with your user account. You can review past diagnostic checks, see what worked, and review timestamps in "My History".
          </div>
        </div>
      </div>

      <div class="accordion-item border-0 mb-2 rounded overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            Can administrators add custom company hardware problems?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Absolutely. The Administrator Dashboard provides full CRUD controls to add proprietary device issues, company-specific software crashes, internal printer configurations, and custom step sequences.
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="<?= BASE_URL ?>/<?= $isLoggedIn ? 'user/troubleshoot.php' : 'register.php' ?>" class="btn btn-ts-primary px-4 py-2">
        Get Started with TechSense Today
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
