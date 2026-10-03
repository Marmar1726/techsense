<?php
// ============================================================
// TechSense — User Dashboard
// ============================================================
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$userId = (int)$user['id'];

// Fetch User Metrics
$totalSessions = 0;
$resolvedSessions = 0;
$unresolvedSessions = 0;
$inProgressSessions = 0;

try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN result = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN result = 'unresolved' THEN 1 ELSE 0 END) as unresolved,
        SUM(CASE WHEN result = 'in_progress' THEN 1 ELSE 0 END) as in_progress
        FROM troubleshooting_sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    $totalSessions      = (int)($stats['total'] ?? 0);
    $resolvedSessions   = (int)($stats['resolved'] ?? 0);
    $unresolvedSessions = (int)($stats['unresolved'] ?? 0);
    $inProgressSessions = (int)($stats['in_progress'] ?? 0);

    // Frequently encountered category
    $catStmt = $pdo->prepare("SELECT c.category_name, COUNT(s.id) as cnt 
                              FROM troubleshooting_sessions s 
                              JOIN categories c ON s.category_id = c.id 
                              WHERE s.user_id = ? 
                              GROUP BY s.category_id 
                              ORDER BY cnt DESC LIMIT 1");
    $catStmt->execute([$userId]);
    $favCat = $catStmt->fetch();
    $topCategoryName = $favCat['category_name'] ?? 'None recorded yet';

    // Recent 5 sessions
    $recStmt = $pdo->prepare("SELECT s.*, p.problem_name, c.category_name 
                              FROM troubleshooting_sessions s 
                              LEFT JOIN problems p ON s.problem_id = p.id 
                              LEFT JOIN categories c ON s.category_id = c.id 
                              WHERE s.user_id = ? 
                              ORDER BY s.created_at DESC LIMIT 5");
    $recStmt->execute([$userId]);
    $recentSessions = $recStmt->fetchAll();

} catch (Exception $e) {
    $recentSessions = [];
    $topCategoryName = 'N/A';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <!-- Header banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Welcome back, <?= e($user['full_name']) ?>! 👋</h1>
        <p class="text-muted small mb-0">Here is your IT diagnostic summary and troubleshooting activity.</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-ts-primary px-3 shadow-sm">
          <i class="bi bi-play-circle-fill me-1"></i> Start New Diagnosis
        </a>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="ts-stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="ts-stat-value text-navy"><?= $totalSessions ?></div>
              <div class="ts-stat-label">Total Sessions</div>
            </div>
            <div class="ts-stat-icon bg-primary-subtle text-primary">
              <i class="bi bi-journal-text"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="ts-stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="ts-stat-value text-success"><?= $resolvedSessions ?></div>
              <div class="ts-stat-label">Resolved Problems</div>
            </div>
            <div class="ts-stat-icon bg-success-subtle text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="ts-stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="ts-stat-value text-danger"><?= $unresolvedSessions ?></div>
              <div class="ts-stat-label">Unresolved Issues</div>
            </div>
            <div class="ts-stat-icon bg-danger-subtle text-danger">
              <i class="bi bi-x-circle-fill"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="ts-stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold text-truncate text-navy" style="font-size: 1.15rem; max-width: 140px;"><?= e($topCategoryName) ?></div>
              <div class="ts-stat-label">Frequent Category</div>
            </div>
            <div class="ts-stat-icon bg-warning-subtle text-warning">
              <i class="bi bi-tag-fill"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area: Recent Sessions & Chart -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="ts-card h-100">
          <div class="ts-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-clock-history text-primary"></i>
              <span>Recent Troubleshooting Sessions</span>
            </div>
            <a href="<?= BASE_URL ?>/user/history.php" class="small text-decoration-none">View All History &rarr;</a>
          </div>
          <div class="ts-card-body p-0">
            <div class="table-responsive">
              <table class="ts-table mb-0">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Identified Problem</th>
                    <th>Category</th>
                    <th>Match</th>
                    <th>Outcome</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recentSessions)): ?>
                    <?php foreach ($recentSessions as $sess): ?>
                      <tr>
                        <td class="text-muted small"><?= formatDate($sess['created_at'], 'M d, Y') ?></td>
                        <td class="fw-semibold text-navy">
                          <?= e($sess['problem_name'] ?: 'Custom Problem Search') ?>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark border"><?= e($sess['category_name'] ?: 'General') ?></span>
                        </td>
                        <td>
                          <span class="badge bg-info-subtle text-info fw-bold"><?= round($sess['match_score']) ?>%</span>
                        </td>
                        <td><?= resultBadge($sess['result']) ?></td>
                        <td class="text-end">
                          <a href="<?= BASE_URL ?>/user/history-detail.php?session_id=<?= $sess['id'] ?>" class="btn btn-sm btn-light border px-2 py-1" title="View Diagnostics">
                            <i class="bi bi-eye"></i> View
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                        You haven't run any troubleshooting sessions yet.
                        <div class="mt-2">
                          <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-sm btn-ts-primary">Start First Diagnosis</a>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Resolution Breakdown Chart & Safety Tips -->
      <div class="col-lg-4">
        <div class="ts-card mb-4">
          <div class="ts-card-header">
            <i class="bi bi-pie-chart-fill text-primary"></i>
            <span>Resolution Breakdown</span>
          </div>
          <div class="ts-card-body">
            <?php if ($totalSessions > 0): ?>
              <div style="height: 180px; position: relative;">
                <canvas id="userResolutionChart"></canvas>
              </div>
            <?php else: ?>
              <p class="text-muted text-center small my-4">Run diagnostic sessions to see your resolution metrics.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-shield-exclamation text-warning"></i>
            <span>Essential IT Safety Reminder</span>
          </div>
          <div class="ts-card-body small text-muted">
            <ul class="ps-3 mb-0">
              <li class="mb-2">Always unplug desktops from the AC outlet before touching motherboards or RAM.</li>
              <li class="mb-2">Touch an unpainted metal chassis surface first to ground static electricity.</li>
              <li>Never open power supply units (PSU) or printer laser mechanisms under any circumstance.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<?php if ($totalSessions > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('userResolutionChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Resolved', 'Unresolved', 'In Progress'],
        datasets: [{
          data: [<?= $resolvedSessions ?>, <?= $unresolvedSessions ?>, <?= $inProgressSessions ?>],
          backgroundColor: ['#27ae60', '#e74c3c', '#f39c12'],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12 } }
        },
        cutout: '70%'
      }
    });
  }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
