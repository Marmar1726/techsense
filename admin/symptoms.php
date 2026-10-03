<?php
// ============================================================
// TechSense — Admin Symptom Management
// ============================================================
$pageTitle = 'Symptom Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Handle Form Submissions (Add, Edit, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed.');
        redirect(BASE_URL . '/admin/symptoms.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name   = sanitize($_POST['symptom_name'] ?? '');
        $catId  = (int)($_POST['category_id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($name) || $catId <= 0) {
            setFlash('danger', 'Symptom name and category are required.');
        } else {
            $ins = $pdo->prepare("INSERT INTO symptoms (symptom_name, category_id, status) VALUES (?, ?, ?)");
            $ins->execute([$name, $catId, $status]);
            setFlash('success', 'Symptom registered successfully.');
        }
    }

    elseif ($action === 'edit') {
        $id     = (int)$_POST['symptom_id'];
        $name   = sanitize($_POST['symptom_name'] ?? '');
        $catId  = (int)($_POST['category_id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id <= 0 || empty($name) || $catId <= 0) {
            setFlash('danger', 'Invalid symptom inputs.');
        } else {
            $upd = $pdo->prepare("UPDATE symptoms SET symptom_name = ?, category_id = ?, status = ? WHERE id = ?");
            $upd->execute([$name, $catId, $status, $id]);
            setFlash('success', 'Symptom updated.');
        }
    }

    elseif ($action === 'toggle_status') {
        $id = (int)$_POST['symptom_id'];
        $s = $pdo->query("SELECT status FROM symptoms WHERE id = $id")->fetch();
        if ($s) {
            $newStatus = $s['status'] === 'active' ? 'inactive' : 'active';
            $pdo->prepare("UPDATE symptoms SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            setFlash('success', "Symptom status changed to {$newStatus}.");
        }
    }

    elseif ($action === 'delete') {
        $id = (int)$_POST['symptom_id'];
        $del = $pdo->prepare("DELETE FROM symptoms WHERE id = ?");
        $del->execute([$id]);
        setFlash('success', 'Symptom deleted.');
    }

    redirect(BASE_URL . '/admin/symptoms.php');
}

// Filters & Pagination
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$q              = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage        = 15;

$where = ["1=1"];
$params = [];

if ($categoryFilter > 0) {
    $where[] = "s.category_id = ?";
    $params[] = $categoryFilter;
}
if (!empty($q)) {
    $where[] = "s.symptom_name LIKE ?";
    $params[] = "%$q%";
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM symptoms s WHERE $whereSql";
$dataSql  = "SELECT s.*, c.category_name, 
             (SELECT COUNT(*) FROM problem_symptoms ps WHERE ps.symptom_id = s.id) as problem_count 
             FROM symptoms s 
             JOIN categories c ON s.category_id = c.id 
             WHERE $whereSql 
             ORDER BY c.category_name ASC, s.symptom_name ASC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$symptoms = $pagination['data'];

$categories = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Symptom Directory</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Manage Symptoms</span>
        </div>
      </div>
      <div>
        <button class="btn btn-ts-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addSymptomModal">
          <i class="bi bi-plus-circle me-1"></i> Add Symptom
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/symptoms.php" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control ts-form-control border-start-0" placeholder="Search symptom..." value="<?= e($q) ?>">
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

    <!-- Symptoms Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Symptom Name</th>
                <th>Category</th>
                <th>Linked Problems</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($symptoms)): ?>
                <?php foreach ($symptoms as $sym): ?>
                  <tr>
                    <td class="text-muted small">#<?= $sym['id'] ?></td>
                    <td class="fw-semibold text-navy"><?= e($sym['symptom_name']) ?></td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= e($sym['category_name']) ?></span>
                    </td>
                    <td>
                      <span class="badge bg-primary-subtle text-primary border"><?= $sym['problem_count'] ?> problems</span>
                    </td>
                    <td>
                      <span class="badge <?= $sym['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($sym['status']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-light border" 
                                onclick="openEditSymptom(<?= htmlspecialchars(json_encode($sym)) ?>)" 
                                title="Edit Symptom">
                          <i class="bi bi-pencil"></i>
                        </button>

                        <form method="POST" action="<?= BASE_URL ?>/admin/symptoms.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="symptom_id" value="<?= $sym['id'] ?>">
                          <button type="submit" class="btn btn-light border" title="Toggle Status">
                            <i class="bi <?= $sym['status'] === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                          </button>
                        </form>

                        <form method="POST" action="<?= BASE_URL ?>/admin/symptoms.php" onsubmit="return confirm('Delete this symptom?');" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="symptom_id" value="<?= $sym['id'] ?>">
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
                  <td colspan="6" class="text-center py-5 text-muted">No symptoms found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/admin/symptoms.php?category=' . $categoryFilter . '&q=' . urlencode($q)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<!-- ============================================================
     ADD SYMPTOM MODAL
============================================================ -->
<div class="modal fade" id="addSymptomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/symptoms.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="add">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Add Diagnostic Symptom</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Symptom Description</label>
          <input type="text" name="symptom_name" class="form-control ts-form-control" placeholder="e.g. Strange clicking noises from case" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category</label>
          <select name="category_id" class="form-select ts-form-control" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Status</label>
          <select name="status" class="form-select ts-form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-ts-primary">Create Symptom</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     EDIT SYMPTOM MODAL
============================================================ -->
<div class="modal fade" id="editSymptomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/symptoms.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="symptom_id" id="editSymId">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Edit Diagnostic Symptom</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Symptom Description</label>
          <input type="text" name="symptom_name" id="editSymName" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category</label>
          <select name="category_id" id="editSymCatId" class="form-select ts-form-control" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Status</label>
          <select name="status" id="editSymStatus" class="form-select ts-form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditSymptom(sym) {
  document.getElementById('editSymId').value     = sym.id;
  document.getElementById('editSymName').value   = sym.symptom_name;
  document.getElementById('editSymCatId').value  = sym.category_id;
  document.getElementById('editSymStatus').value = sym.status;
  new bootstrap.Modal(document.getElementById('editSymptomModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
