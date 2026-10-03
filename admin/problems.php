<?php
// ============================================================
// TechSense — Admin Knowledge Base CRUD (Problems & Solutions)
// ============================================================
$pageTitle = 'Problem Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Handle POST actions: Save (Add/Edit), Delete, Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed.');
        redirect(BASE_URL . '/admin/problems.php');
    }

    $action = $_POST['action'] ?? '';

    // SAVE PROBLEM (Add or Edit)
    if ($action === 'save') {
        $problemId      = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
        $problemName    = sanitize($_POST['problem_name'] ?? '');
        $categoryId     = (int)($_POST['category_id'] ?? 0);
        $description    = sanitize($_POST['description'] ?? '');
        $difficulty     = in_array($_POST['difficulty'] ?? '', ['Beginner', 'Intermediate', 'Advanced']) ? $_POST['difficulty'] : 'Beginner';
        $estimatedTime  = sanitize($_POST['estimated_time'] ?? '10-20 minutes');
        $warning        = sanitize($_POST['warning'] ?? '');
        $status         = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $symptoms       = isset($_POST['symptoms']) && is_array($_POST['symptoms']) ? array_map('intval', $_POST['symptoms']) : [];
        $causes         = isset($_POST['causes']) && is_array($_POST['causes']) ? array_filter(array_map('sanitize', $_POST['causes'])) : [];
        $steps          = isset($_POST['steps']) && is_array($_POST['steps']) ? array_filter(array_map('sanitize', $_POST['steps'])) : [];
        $solution       = sanitize($_POST['solution'] ?? '');

        if (empty($problemName) || $categoryId <= 0) {
            setFlash('danger', 'Problem Name and Category are required.');
        } else {
            try {
                $pdo->beginTransaction();

                if ($problemId > 0) {
                    // Update existing
                    $upd = $pdo->prepare("UPDATE problems SET category_id = ?, problem_name = ?, description = ?, difficulty = ?, estimated_time = ?, warning = ?, status = ? WHERE id = ?");
                    $upd->execute([$categoryId, $problemName, $description, $difficulty, $estimatedTime, $warning, $status, $problemId]);
                } else {
                    // Insert new
                    $ins = $pdo->prepare("INSERT INTO problems (category_id, problem_name, description, difficulty, estimated_time, warning, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$categoryId, $problemName, $description, $difficulty, $estimatedTime, $warning, $status]);
                    $problemId = (int)$pdo->lastInsertId();
                }

                // 1. Re-sync Symptoms
                $delPs = $pdo->prepare("DELETE FROM problem_symptoms WHERE problem_id = ?");
                $delPs->execute([$problemId]);
                if (!empty($symptoms)) {
                    $insPs = $pdo->prepare("INSERT INTO problem_symptoms (problem_id, symptom_id) VALUES (?, ?)");
                    foreach ($symptoms as $symId) {
                        $insPs->execute([$problemId, $symId]);
                    }
                }

                // 2. Re-sync Causes
                $delC = $pdo->prepare("DELETE FROM causes WHERE problem_id = ?");
                $delC->execute([$problemId]);
                if (!empty($causes)) {
                    $insC = $pdo->prepare("INSERT INTO causes (problem_id, cause_description, order_num) VALUES (?, ?, ?)");
                    $order = 1;
                    foreach ($causes as $causeText) {
                        $insC->execute([$problemId, $causeText, $order++]);
                    }
                }

                // 3. Re-sync Steps
                $delS = $pdo->prepare("DELETE FROM troubleshooting_steps WHERE problem_id = ?");
                $delS->execute([$problemId]);
                if (!empty($steps)) {
                    $insS = $pdo->prepare("INSERT INTO troubleshooting_steps (problem_id, step_number, instruction) VALUES (?, ?, ?)");
                    $stepNum = 1;
                    foreach ($steps as $stepInstruction) {
                        $insS->execute([$problemId, $stepNum++, $stepInstruction]);
                    }
                }

                // 4. Re-sync Solution
                $delSol = $pdo->prepare("DELETE FROM solutions WHERE problem_id = ?");
                $delSol->execute([$problemId]);
                if (!empty($solution)) {
                    $insSol = $pdo->prepare("INSERT INTO solutions (problem_id, solution_description) VALUES (?, ?)");
                    $insSol->execute([$problemId, $solution]);
                }

                $pdo->commit();
                setFlash('success', 'Troubleshooting problem saved successfully.');

            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('danger', 'Error saving problem: ' . $e->getMessage());
            }
        }
    }

    // TOGGLE STATUS
    elseif ($action === 'toggle_status') {
        $probId = (int)$_POST['problem_id'];
        $p = $pdo->query("SELECT status FROM problems WHERE id = $probId")->fetch();
        if ($p) {
            $newStatus = $p['status'] === 'active' ? 'inactive' : 'active';
            $pdo->prepare("UPDATE problems SET status = ? WHERE id = ?")->execute([$newStatus, $probId]);
            setFlash('success', "Problem status changed to {$newStatus}.");
        }
    }

    // DELETE PROBLEM
    elseif ($action === 'delete') {
        $probId = (int)$_POST['problem_id'];
        $del = $pdo->prepare("DELETE FROM problems WHERE id = ?");
        $del->execute([$probId]);
        setFlash('success', 'Troubleshooting record deleted successfully.');
    }

    redirect(BASE_URL . '/admin/problems.php');
}

// Filters & Listing
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$q              = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage        = 10;

$where = ["1=1"];
$params = [];

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}
if (!empty($q)) {
    $where[] = "(p.problem_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM problems p WHERE $whereSql";
$dataSql  = "SELECT p.*, c.category_name, 
             (SELECT COUNT(*) FROM troubleshooting_steps s WHERE s.problem_id = p.id) as step_count,
             (SELECT COUNT(*) FROM problem_symptoms ps WHERE ps.problem_id = p.id) as symptom_count 
             FROM problems p 
             JOIN categories c ON p.category_id = c.id 
             WHERE $whereSql 
             ORDER BY p.id DESC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$problems = $pagination['data'];

// All categories for modal & filter
$categories = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Troubleshooting Knowledge Base</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Manage Problems &amp; Solutions</span>
        </div>
      </div>
      <div>
        <button class="btn btn-ts-primary btn-sm px-3 shadow-sm" onclick="openCreateProblemModal()">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Troubleshooting Problem
        </button>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/problems.php" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control ts-form-control border-start-0" placeholder="Search problem name or description..." value="<?= e($q) ?>">
          </div>
        </div>
        <div class="col-sm-6 col-md-4">
          <select name="category" class="form-select ts-form-control">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
      </form>
    </div>

    <!-- Problems Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Problem Name</th>
                <th>Category</th>
                <th>Difficulty</th>
                <th>Symptoms</th>
                <th>Steps</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($problems)): ?>
                <?php foreach ($problems as $p): ?>
                  <tr>
                    <td class="text-muted small">#<?= $p['id'] ?></td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($p['problem_name']) ?></div>
                      <div class="text-muted small text-truncate" style="max-width: 280px;"><?= e($p['description']) ?></div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= e($p['category_name']) ?></span>
                    </td>
                    <td><?= difficultyBadge($p['difficulty']) ?></td>
                    <td>
                      <span class="badge bg-info-subtle text-info"><?= $p['symptom_count'] ?> symptoms</span>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle text-secondary"><?= $p['step_count'] ?> steps</span>
                    </td>
                    <td>
                      <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($p['status']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <!-- Edit -->
                        <button type="button" class="btn btn-light border" 
                                onclick="openEditProblem(<?= $p['id'] ?>)" 
                                title="Edit Problem">
                          <i class="bi bi-pencil"></i>
                        </button>

                        <!-- Toggle Status -->
                        <form method="POST" action="<?= BASE_URL ?>/admin/problems.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="problem_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-light border" title="Toggle Status">
                            <i class="bi <?= $p['status'] === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                          </button>
                        </form>

                        <!-- Delete -->
                        <form method="POST" action="<?= BASE_URL ?>/admin/problems.php" onsubmit="return confirm('Delete this troubleshooting problem permanently?');" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="problem_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-light border text-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">No problems found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/admin/problems.php?category=' . $categoryFilter . '&q=' . urlencode($q)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<!-- ============================================================
     ADD / EDIT PROBLEM MODAL (TABS FORM)
============================================================ -->
<div class="modal fade" id="problemModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="POST" action="<?= BASE_URL ?>/admin/problems.php" class="modal-content" id="problemForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="problem_id" id="probModalId" value="0">

      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title fw-bold" id="probModalTitle">Add Troubleshooting Problem</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        
        <!-- Modal Tabs Nav -->
        <ul class="nav nav-tabs px-3 pt-2 bg-light" id="probTabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">1. Basic Info</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-symptoms" type="button">2. Symptoms</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-causes" type="button">3. Causes</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-steps" type="button">4. Steps</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-solution" type="button">5. Solution</button>
          </li>
        </ul>

        <div class="tab-content p-4" id="probTabsContent">
          
          <!-- TAB 1: BASIC INFO -->
          <div class="tab-pane fade show active" id="tab-basic">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Problem Name *</label>
              <input type="text" name="problem_name" id="pName" class="form-control ts-form-control" placeholder="e.g. No Display on Monitor" required>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Category *</label>
                <select name="category_id" id="pCatId" class="form-select ts-form-control" required onchange="loadCategorySymptoms(this.value)">
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['category_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Difficulty Level</label>
                <select name="difficulty" id="pDiff" class="form-select ts-form-control">
                  <option value="Beginner">Beginner</option>
                  <option value="Intermediate">Intermediate</option>
                  <option value="Advanced">Advanced</option>
                </select>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Estimated Resolution Time</label>
                <input type="text" name="estimated_time" id="pEstTime" class="form-control ts-form-control" placeholder="e.g. 10-20 minutes">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" id="pStatus" class="form-select ts-form-control">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Problem Description</label>
              <textarea name="description" id="pDesc" class="form-control ts-form-control" rows="3" placeholder="Brief technical overview of what occurs"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-danger">Safety Warning Notice (Optional)</label>
              <textarea name="warning" id="pWarning" class="form-control ts-form-control" rows="2" placeholder="e.g. Disconnect power cable before opening case"></textarea>
            </div>
          </div>

          <!-- TAB 2: SYMPTOMS (Multi-select Checklist) -->
          <div class="tab-pane fade" id="tab-symptoms">
            <p class="text-muted small mb-3">Check all symptoms that correlate with this problem. (Select a category on Tab 1 first to load relevant symptoms).</p>
            <div id="modalSymptomsList" class="d-flex flex-wrap gap-2 py-2">
              <p class="text-muted small">Please choose a category on the Basic Info tab.</p>
            </div>
          </div>

          <!-- TAB 3: CAUSES (Dynamic rows) -->
          <div class="tab-pane fade" id="tab-causes">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted small">List probable root causes for this issue:</span>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCauseRow()">
                <i class="bi bi-plus-lg"></i> Add Cause
              </button>
            </div>
            <div id="causesContainer" class="d-flex flex-column gap-2"></div>
          </div>

          <!-- TAB 4: STEPS (Dynamic numbered rows) -->
          <div class="tab-pane fade" id="tab-steps">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted small">Define ordered troubleshooting instructions:</span>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStepRow()">
                <i class="bi bi-plus-lg"></i> Add Step
              </button>
            </div>
            <div id="stepsContainer" class="d-flex flex-column gap-3"></div>
          </div>

          <!-- TAB 5: SOLUTION (Recommended resolution summary) -->
          <div class="tab-pane fade" id="tab-solution">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Recommended Final Solution</label>
              <textarea name="solution" id="pSolution" class="form-control ts-form-control" rows="6" placeholder="Summarize the definitive fix or primary hardware/software remedy..."></textarea>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-ts-primary px-4">
          <i class="bi bi-save me-1"></i> Save Problem Guide
        </button>
      </div>

    </form>
  </div>
</div>

<script>
let currentSelectedSymptoms = [];

function openCreateProblemModal() {
  document.getElementById('probModalId').value    = 0;
  document.getElementById('probModalTitle').textContent = 'Add Troubleshooting Problem';
  document.getElementById('pName').value          = '';
  document.getElementById('pCatId').value        = '';
  document.getElementById('pDiff').value         = 'Beginner';
  document.getElementById('pEstTime').value      = '10-20 minutes';
  document.getElementById('pStatus').value       = 'active';
  document.getElementById('pDesc').value         = '';
  document.getElementById('pWarning').value      = '';
  document.getElementById('pSolution').value     = '';
  document.getElementById('causesContainer').innerHTML = '';
  document.getElementById('stepsContainer').innerHTML  = '';
  document.getElementById('modalSymptomsList').innerHTML = '<p class="text-muted small">Select a category on Tab 1.</p>';
  currentSelectedSymptoms = [];

  // Add 2 initial rows
  addCauseRow('');
  addStepRow('');

  new bootstrap.Tab(document.querySelector('#probTabs button')).show();
  new bootstrap.Modal(document.getElementById('problemModal')).show();
}

function openEditProblem(problemId) {
  fetch(`/techsense/api/admin-get-problem.php?problem_id=${problemId}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) { alert(data.error); return; }

      document.getElementById('probModalId').value    = data.problem.id;
      document.getElementById('probModalTitle').textContent = 'Edit Problem: ' + data.problem.problem_name;
      document.getElementById('pName').value          = data.problem.problem_name;
      document.getElementById('pCatId').value        = data.problem.category_id;
      document.getElementById('pDiff').value         = data.problem.difficulty;
      document.getElementById('pEstTime').value      = data.problem.estimated_time;
      document.getElementById('pStatus').value       = data.problem.status;
      document.getElementById('pDesc').value         = data.problem.description;
      document.getElementById('pWarning').value      = data.problem.warning || '';
      document.getElementById('pSolution').value     = data.solution;

      currentSelectedSymptoms = data.symptom_ids.map(Number);
      loadCategorySymptoms(data.problem.category_id, currentSelectedSymptoms);

      // Causes
      const cBox = document.getElementById('causesContainer');
      cBox.innerHTML = '';
      if (data.causes && data.causes.length > 0) {
        data.causes.forEach(c => addCauseRow(c));
      } else {
        addCauseRow('');
      }

      // Steps
      const sBox = document.getElementById('stepsContainer');
      sBox.innerHTML = '';
      if (data.steps && data.steps.length > 0) {
        data.steps.forEach(st => addStepRow(st.instruction));
      } else {
        addStepRow('');
      }

      new bootstrap.Tab(document.querySelector('#probTabs button')).show();
      new bootstrap.Modal(document.getElementById('problemModal')).show();
    })
    .catch(() => alert('Failed to fetch problem data.'));
}

function loadCategorySymptoms(catId, selectedIds = []) {
  if (!catId) return;
  const list = document.getElementById('modalSymptomsList');
  list.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Loading symptoms...';

  fetch(`/techsense/api/admin-get-symptoms.php?category_id=${catId}&selected_ids=${selectedIds.join(',')}`)
    .then(r => r.json())
    .then(d => {
      if (!d.symptoms || d.symptoms.length === 0) {
        list.innerHTML = '<p class="text-muted small">No symptoms created for this category yet.</p>';
        return;
      }
      list.innerHTML = d.symptoms.map(s => `
        <div class="form-check form-check-inline bg-white p-2 px-3 rounded border">
          <input class="form-check-input" type="checkbox" name="symptoms[]" value="${s.id}" id="msym_${s.id}" ${s.selected ? 'checked' : ''}>
          <label class="form-check-label small" for="msym_${s.id}">${s.symptom_name}</label>
        </div>
      `).join('');
    });
}

function addCauseRow(val = '') {
  const container = document.getElementById('causesContainer');
  const div = document.createElement('div');
  div.className = 'input-group input-group-sm';
  div.innerHTML = `
    <span class="input-group-text bg-light"><i class="bi bi-dash"></i></span>
    <input type="text" name="causes[]" class="form-control" placeholder="e.g. Loose power connector" value="${escapeHtml(val)}" required>
    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
  `;
  container.appendChild(div);
}

function addStepRow(val = '') {
  const container = document.getElementById('stepsContainer');
  const count = container.children.length + 1;
  const div = document.createElement('div');
  div.className = 'p-3 bg-light rounded border';
  div.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="badge bg-primary">Step ${count}</span>
      <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.p-3').remove()"><i class="bi bi-trash"></i> Remove</button>
    </div>
    <textarea name="steps[]" class="form-control ts-form-control" rows="2" placeholder="Step instruction details..." required>${escapeHtml(val)}</textarea>
  `;
  container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
