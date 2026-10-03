<?php
// ============================================================
// TechSense — Admin User Management
// ============================================================
$pageTitle = 'User Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentAdminId = (int)$_SESSION['user_id'];

// Handle Actions (Add, Edit, Toggle Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($token)) {
        setFlash('danger', 'Security validation failed.');
        redirect(BASE_URL . '/admin/users.php');
    }

    $action = $_POST['action'] ?? '';

    // ADD USER
    if ($action === 'add') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
        $password = $_POST['password'] ?? '';

        if (empty($fullName) || !validateEmail($email) || empty($username) || strlen($password) < 6) {
            setFlash('danger', 'Please fill in all fields with valid details (password min. 6 chars).');
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $chk->execute([$email, $username]);
            if ($chk->fetch()) {
                setFlash('danger', 'Email or username already in use.');
            } else {
                $hash = hashPassword($password);
                $ins = $pdo->prepare("INSERT INTO users (full_name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $ins->execute([$fullName, $email, $username, $hash, $role]);
                setFlash('success', 'User account created successfully.');
            }
        }
    }

    // EDIT USER
    elseif ($action === 'edit') {
        $userId   = (int)$_POST['user_id'];
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
        $status   = in_array($_POST['status'] ?? '', ['active', 'disabled']) ? $_POST['status'] : 'active';

        if ($userId <= 0 || empty($fullName) || !validateEmail($email) || empty($username)) {
            setFlash('danger', 'Invalid user parameters provided.');
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $chk->execute([$email, $username, $userId]);
            if ($chk->fetch()) {
                setFlash('danger', 'Email or username already used by another account.');
            } else {
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, role = ?, status = ? WHERE id = ?");
                $upd->execute([$fullName, $email, $username, $role, $status, $userId]);
                setFlash('success', 'User account updated.');
            }
        }
    }

    // TOGGLE STATUS
    elseif ($action === 'toggle_status') {
        $userId = (int)$_POST['user_id'];
        if ($userId === $currentAdminId) {
            setFlash('warning', 'You cannot disable your own active administrator account.');
        } else {
            $u = $pdo->query("SELECT status FROM users WHERE id = $userId")->fetch();
            if ($u) {
                $newStatus = $u['status'] === 'active' ? 'disabled' : 'active';
                $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
                setFlash('success', "Account status toggled to {$newStatus}.");
            }
        }
    }

    // DELETE USER
    elseif ($action === 'delete') {
        $userId = (int)$_POST['user_id'];
        if ($userId === $currentAdminId || $userId === 1) {
            setFlash('danger', 'Primary administrator cannot be deleted.');
        } else {
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$userId]);
            setFlash('success', 'User removed permanently.');
        }
    }

    redirect(BASE_URL . '/admin/users.php');
}

// Fetch Paginated Users
$q    = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

$where = ["1=1"];
$params = [];

if (!empty($q)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
if (!empty($role) && in_array($role, ['admin', 'user'])) {
    $where[] = "role = ?";
    $params[] = $role;
}

$whereSql = implode(' AND ', $where);
$countSql = "SELECT COUNT(*) FROM users WHERE $whereSql";
$dataSql  = "SELECT *, (SELECT COUNT(*) FROM troubleshooting_sessions s WHERE s.user_id = users.id) as session_count 
             FROM users 
             WHERE $whereSql 
             ORDER BY created_at DESC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$users = $pagination['data'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">User Management</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Registered Users</span>
        </div>
      </div>
      <div>
        <button class="btn btn-ts-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="bi bi-person-plus-fill me-1"></i> Add New User
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/users.php" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control ts-form-control border-start-0" placeholder="Search by name, email, or username..." value="<?= e($q) ?>">
          </div>
        </div>
        <div class="col-sm-4 col-md-3">
          <select name="role" class="form-select ts-form-control">
            <option value="">All Roles</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Regular User</option>
          </select>
        </div>
        <div class="col-sm-3 col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Search</button>
        </div>
        <?php if (!empty($q) || !empty($role)): ?>
          <div class="col-auto">
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary">Clear</a>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- Users Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Full Name / Email</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Sessions</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($users)): ?>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td class="text-muted small">#<?= $u['id'] ?></td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($u['full_name']) ?></div>
                      <div class="text-muted small"><?= e($u['email']) ?></div>
                    </td>
                    <td class="small"><code><?= e($u['username']) ?></code></td>
                    <td>
                      <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                        <?= ucfirst($u['role']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($u['status']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= $u['session_count'] ?></span>
                    </td>
                    <td class="text-muted small"><?= formatDate($u['created_at'], 'M d, Y') ?></td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-light border" 
                                onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)" 
                                title="Edit User">
                          <i class="bi bi-pencil"></i>
                        </button>

                        <!-- Toggle Status -->
                        <form method="POST" action="<?= BASE_URL ?>/admin/users.php" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <button type="submit" class="btn btn-light border" title="<?= $u['status'] === 'active' ? 'Disable Account' : 'Activate Account' ?>">
                            <i class="bi <?= $u['status'] === 'active' ? 'bi-person-slash text-warning' : 'bi-person-check text-success' ?>"></i>
                          </button>
                        </form>

                        <!-- Delete (if not admin) -->
                        <?php if ($u['id'] != $currentAdminId && $u['id'] != 1): ?>
                          <form method="POST" action="<?= BASE_URL ?>/admin/users.php" onsubmit="return confirm('Permanently delete this user and all their session history?');" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-light border text-danger" title="Delete User">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">No users found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/admin/users.php?q=' . urlencode($q) . '&role=' . urlencode($role)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<!-- ============================================================
     ADD USER MODAL
============================================================ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/users.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="add">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Full Name</label>
          <input type="text" name="full_name" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Email Address</label>
          <input type="email" name="email" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Username</label>
          <input type="text" name="username" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Role</label>
          <select name="role" class="form-select ts-form-control">
            <option value="user">Regular User</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Password</label>
          <input type="password" name="password" class="form-control ts-form-control" placeholder="Min 6 characters" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-ts-primary">Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     EDIT USER MODAL
============================================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/admin/users.php" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" id="editUserId">

      <div class="modal-header">
        <h5 class="modal-title fw-bold text-navy">Edit User Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Full Name</label>
          <input type="text" name="full_name" id="editFullName" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Email Address</label>
          <input type="email" name="email" id="editEmail" class="form-control ts-form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Username</label>
          <input type="text" name="username" id="editUsername" class="form-control ts-form-control" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Role</label>
            <select name="role" id="editRole" class="form-select ts-form-control">
              <option value="user">Regular User</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" id="editStatus" class="form-select ts-form-control">
              <option value="active">Active</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
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
function openEditUser(user) {
  document.getElementById('editUserId').value   = user.id;
  document.getElementById('editFullName').value = user.full_name;
  document.getElementById('editEmail').value    = user.email;
  document.getElementById('editUsername').value = user.username;
  document.getElementById('editRole').value     = user.role;
  document.getElementById('editStatus').value   = user.status;
  new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
