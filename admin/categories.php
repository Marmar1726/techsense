<?php
$pageTitle = 'Category Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Security validation failed.');
    redirect(BASE_URL . '/admin/categories.php');
  }

  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $name = sanitize($_POST['category_name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? 'bi-tools');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name)) {
      setFlash('danger', 'Category name is required.');
    } else {
      $ins = $pdo->prepare("INSERT INTO categories (category_name, description, icon, status) VALUES (?, ?, ?, ?)");
      $ins->execute([$name, $desc, $icon, $status]);
      setFlash('success', 'Category added successfully.');
    }
  } elseif ($action === 'edit') {
    $id = (int) $_POST['category_id'];
    $name = sanitize($_POST['category_name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? 'bi-tools');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if ($id <= 0 || empty($name)) {
      setFlash('danger', 'Invalid category parameters.');
    } else {
      $upd = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, icon = ?, status = ? WHERE id = ?");
      $upd->execute([$name, $desc, $icon, $status, $id]);
      setFlash('success', 'Category updated successfully.');
    }
  } elseif ($action === 'toggle_status') {
    $id = (int) $_POST['category_id'];
    $c = $pdo->query("SELECT status FROM categories WHERE id = $id")->fetch();
    if ($c) {
      $newStatus = $c['status'] === 'active' ? 'inactive' : 'active';
      $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
      setFlash('success', "Category status updated to {$newStatus}.");
    }
  } elseif ($action === 'delete') {
    $id = (int) $_POST['category_id'];

    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM problems WHERE category_id = $id")->fetchColumn();
    if ($cnt > 0) {
      setFlash('danger', "Cannot delete this category because it contains {$cnt} troubleshooting problem(s). Reassign or delete them first.");
    } else {
      $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
      $del->execute([$id]);
      setFlash('success', 'Category deleted.');
    }
  }

  redirect(BASE_URL . '/admin/categories.php');
}


$categories = $pdo->query("SELECT c.*, 
                           (SELECT COUNT(*) FROM problems p WHERE p.category_id = c.id) as problem_count,
                           (SELECT COUNT(*) FROM symptoms s WHERE s.category_id = c.id) as symptom_count 
                           FROM categories c 
                           ORDER BY c.id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Category Management</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Problem Categories</span>
        </div>
      </div>
      <div>
        <button class="btn btn-ts-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
          <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
      </div>
    </div>


    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Icon</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Problems</th>
                <th>Symptoms</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td>
                    <div class="ts-logo-icon bg-light text-navy border" style="width: 36px; height: 36px;">
                      <i class="bi <?= e($cat['icon'] ?: 'bi-tools') ?>"></i>
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold text-navy"><?= e($cat['category_name']) ?></div>
                    <code class="text-muted small">Icon: <?= e($cat['icon']) ?></code>
                  </td>
                  <td class="small text-muted" style="max-width: 300px;">
                    <?= e($cat['description']) ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/admin/problems.php?category=<?= $cat['id'] ?>"
                      class="badge bg-primary-subtle text-primary border text-decoration-none">
                      <?= $cat['problem_count'] ?> Problems
                    </a>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/admin/symptoms.php?category=<?= $cat['id'] ?>"
                      class="badge bg-light text-secondary border text-decoration-none">
                      <?= $cat['symptom_count'] ?> Symptoms
                    </a>
                  </td>
                  <td>
                    <span class="badge <?= $cat['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                      <?= ucfirst($cat['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-light border"
                        onclick="openEditCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" title="Edit Category">
                        <i class="bi bi-pencil"></i>
                      </button>

                      <form method="POST" action="<?= BASE_URL ?>/admin/categories.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-light border" title="Toggle Status">
                          <i
                            class="bi <?= $cat['status'] === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                        </button>
                      </form>

                      <?php if ($cat['problem_count'] == 0): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/categories.php"
                          onsubmit="return confirm('Delete this category?');" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                          <button type="submit" class="btn btn-light border text-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/categories.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="add">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Add IT Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category Name</label>
          <input type="text" name="category_name" class="form-control ts-form-control" placeholder="e.g. Cloud & Server"
            required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
          <input type="text" name="icon" class="form-control ts-form-control"
            placeholder="bi-cpu-fill, bi-wifi, bi-cloud-fill" value="bi-tools">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea name="description" class="form-control ts-form-control" rows="3"
            placeholder="Scope of problems covered by this category"></textarea>
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
        <button type="submit" class="btn btn-ts-primary">Create Category</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/categories.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="category_id" id="editCatId">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Edit IT Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category Name</label>
          <input type="text" name="category_name" id="editCatName" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
          <input type="text" name="icon" id="editCatIcon" class="form-control ts-form-control">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea name="description" id="editCatDesc" class="form-control ts-form-control" rows="3"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Status</label>
          <select name="status" id="editCatStatus" class="form-select ts-form-control">
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
  function openEditCategory(cat) {
    document.getElementById('editCatId').value = cat.id;
    document.getElementById('editCatName').value = cat.category_name;
    document.getElementById('editCatIcon').value = cat.icon;
    document.getElementById('editCatDesc').value = cat.description;
    document.getElementById('editCatStatus').value = cat.status;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>