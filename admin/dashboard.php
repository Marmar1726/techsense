<?php
// ============================================================
// TechSense — Administrator Dashboard
// ============================================================
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$adminUser = currentUser();

// Metrics
$totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalProblems  = (int)$pdo->query("SELECT COUNT(*) FROM problems WHERE status = 'active'")->fetchColumn();
$totalSessions  = (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions")->fetchColumn();
$resolvedCount  = (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE result = 'resolved'")->fetchColumn();
$unresolvedCount= (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE result = 'unresolved'")->fetchColumn();
$inProgressCount= (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE result = 'in_progress'")->fetchColumn();
$resolutionRate = $totalSessions > 0 ? round(($resolvedCount / $totalSessions) * 100) : 0;

// Most Common Problem
$mcpStmt = $pdo->query("SELECT p.problem_name, COUNT(s.id) as cnt 
                        FROM troubleshooting_sessions s 
                        JOIN problems p ON s.problem_id = p.id 
                        GROUP BY s.problem_id 
                        ORDER BY cnt DESC LIMIT 1");
$mcp = $mcpStmt->fetch();
$mostCommonProblem = $mcp['problem_name'] ?? 'None recorded';

// Most Used Category
$mucStmt = $pdo->query("SELECT c.category_name, COUNT(s.id) as cnt 
                        FROM troubleshooting_sessions s 
                        JOIN categories c ON s.category_id = c.id 
                        GROUP BY s.category_id 
                        ORDER BY cnt DESC LIMIT 1");
$muc = $mucStmt->fetch();
$mostUsedCategory = $muc['category_name'] ?? 'None recorded';

// Chart Data: Problems by Category
$catChartData = $pdo->query("SELECT c.category_name, COUNT(p.id) as prob_count 
                             FROM categories c 
                             LEFT JOIN problems p ON c.id = p.category_id AND p.status = 'active'
                             GROUP BY c.id 
                             ORDER BY prob_count DESC")->fetchAll();
$catLabels = array_column($catChartData, 'category_name');
$catCounts = array_map('intval', array_column($catChartData, 'prob_count'));

// Chart Data: Top Reported Problems (Top 5)
$topProbsData = $pdo->query("SELECT p.problem_name, COUNT(s.id) as rep_count 
                             FROM problems p 
                             JOIN troubleshooting_sessions s ON p.id = s.problem_id 
                             GROUP BY p.id 
                             ORDER BY rep_count DESC LIMIT 5")->fetchAll();
$topProbLabels = array_column($topProbsData, 'problem_name');
$topProbCounts = array_map('intval', array_column($topProbsData, 'rep_count'));

// If empty fallback for demo chart
if (empty($topProbLabels)) {
    $topProbLabels = ['No Display', 'Wi-Fi Disconnects', 'Windows Slow', 'Printer Offline', 'Overheating'];
    $topProbCounts = [12, 9, 8, 6, 5];
}

// Chart Data: Sessions Over Last 7 Days
$last7Days = [];
$sessionsPerDay = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $last7Days[] = date('M d', strtotime($d));
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE DATE(created_at) = '$d'")->fetchColumn();
    $sessionsPerDay[] = $cnt;
}

// Recent Feedback
$fbStmt = $pdo->query("SELECT f.*, u.full_name, p.problem_name 
                      FROM feedback f 
                      JOIN users u ON f.user_id = u.id 
                      JOIN troubleshooting_sessions s ON f.session_id = s.id 
                      LEFT JOIN problems p ON s.problem_id = p.id 
                      ORDER BY f.created_at DESC LIMIT 5");
$recentFeedback = $fbStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Administrative Overview</h1>
        <p class="text-muted small mb-0">System performance, knowledge metrics, and troubleshooting analytics.</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/problems.php" class="btn btn-ts-primary btn-sm px-3 shadow-sm">
          <i class="bi bi-plus-circle me-1"></i> Add Troubleshooting Problem
        </a>
      </div>
    </div>

    <!-- 6 KPI Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-navy fs-3"><?= $totalUsers ?></div>
          <div class="ts-stat-label">Total Users</div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-primary fs-3"><?= $totalProblems ?></div>
          <div class="ts-stat-label">Active Problems</div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-navy fs-3"><?= $totalSessions ?></div>
          <div class="ts-stat-label">Total Sessions</div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-success fs-3"><?= $resolvedCount ?></div>
          <div class="ts-stat-label">Resolved</div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-danger fs-3"><?= $unresolvedCount ?></div>
          <div class="ts-stat-label">Unresolved</div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-ts-accent fs-3"><?= $resolutionRate ?>%</div>
          <div class="ts-stat-label">Resolution Rate</div>
        </div>
      </div>
    </div>

    <!-- Secondary Meta Row -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="p-3 bg-white rounded-ts border d-flex align-items-center gap-3 shadow-sm">
          <div class="ts-stat-icon bg-primary-subtle text-primary">
            <i class="bi bi-fire"></i>
          </div>
          <div>
            <div class="text-muted small">Most Frequently Encountered Problem</div>
            <div class="fw-bold text-navy fs-6"><?= e($mostCommonProblem) ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-3 bg-white rounded-ts border d-flex align-items-center gap-3 shadow-sm">
          <div class="ts-stat-icon bg-warning-subtle text-warning">
            <i class="bi bi-tag-fill"></i>
          </div>
          <div>
            <div class="text-muted small">Most Active IT Category</div>
            <div class="fw-bold text-navy fs-6"><?= e($mostUsedCategory) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 1: Activity Line & Resolution Doughnut -->
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-graph-up text-primary"></i>
            <span>Troubleshooting Sessions Over Time (Last 7 Days)</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="sessionsChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-pie-chart text-primary"></i>
            <span>Resolved vs Unresolved Problems</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="resolutionChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 2: Categories Bar & Top Problems Bar -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-bar-chart-fill text-primary"></i>
            <span>Knowledge Base Distribution by Category</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="categoryChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-activity text-primary"></i>
            <span>Most Frequently Reported Problems</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="topProblemsChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Feedback Table -->
    <div class="ts-card">
      <div class="ts-card-header d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-chat-square-quote-fill text-primary"></i>
          <span>Recent User Feedback</span>
        </div>
        <a href="<?= BASE_URL ?>/admin/feedback.php" class="small text-decoration-none">View All Feedback &rarr;</a>
      </div>
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Problem</th>
                <th>Rating</th>
                <th>Feedback Comments</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recentFeedback)): ?>
                <?php foreach ($recentFeedback as $fb): ?>
                  <tr>
                    <td class="fw-semibold text-navy"><?= e($fb['full_name']) ?></td>
                    <td class="text-navy"><?= e($fb['problem_name'] ?: 'Custom Problem') ?></td>
                    <td>
                      <span class="badge <?= $fb['rating'] === 'helpful' ? 'bg-success' : ($fb['rating'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                        <?= ucfirst($fb['rating']) ?>
                      </span>
                    </td>
                    <td class="text-muted small">&ldquo;<?= e(substr($fb['comments'], 0, 80)) ?><?= strlen($fb['comments']) > 80 ? '...' : '' ?>&rdquo;</td>
                    <td class="text-muted small"><?= formatDate($fb['created_at'], 'M d, Y') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No user feedback submitted yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Line Chart: Sessions Over Time
  const sCtx = document.getElementById('sessionsChart');
  if (sCtx) {
    new Chart(sCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($last7Days) ?>,
        datasets: [{
          label: 'Troubleshooting Sessions',
          data: <?= json_encode($sessionsPerDay) ?>,
          borderColor: '#1a6eb5',
          backgroundColor: 'rgba(26, 110, 181, 0.1)',
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: '#1a6eb5'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  // Doughnut Chart: Resolved vs Unresolved
  const rCtx = document.getElementById('resolutionChart');
  if (rCtx) {
    new Chart(rCtx, {
      type: 'doughnut',
      data: {
        labels: ['Resolved', 'Unresolved', 'In Progress'],
        datasets: [{
          data: [<?= $resolvedCount ?>, <?= $unresolvedCount ?>, <?= $inProgressCount ?>],
          backgroundColor: ['#27ae60', '#e74c3c', '#f39c12'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        cutout: '65%'
      }
    });
  }

  // Bar Chart: Category Distribution
  const cCtx = document.getElementById('categoryChart');
  if (cCtx) {
    new Chart(cCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
          label: 'Problems Count',
          data: <?= json_encode($catCounts) ?>,
          backgroundColor: '#00d4aa',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  // Horizontal Bar: Top Problems
  const tpCtx = document.getElementById('topProblemsChart');
  if (tpCtx) {
    new Chart(tpCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($topProbLabels) ?>,
        datasets: [{
          label: 'Reports',
          data: <?= json_encode($topProbCounts) ?>,
          backgroundColor: '#34495e',
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
