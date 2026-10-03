<?php
// ============================================================
// TechSense — Admin Reports & Analytics
// ============================================================
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$preset = $_GET['preset'] ?? '30days';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

// Date Range calculations
if ($preset === 'daily') {
    $startDate = date('Y-m-d 00:00:00');
    $endDate   = date('Y-m-d 23:59:59');
} elseif ($preset === 'weekly') {
    $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
    $endDate   = date('Y-m-d 23:59:59');
} elseif ($preset === 'monthly') {
    $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
    $endDate   = date('Y-m-d 23:59:59');
} elseif ($preset === 'custom' && !empty($from) && !empty($to)) {
    $startDate = date('Y-m-d 00:00:00', strtotime($from));
    $endDate   = date('Y-m-d 23:59:59', strtotime($to));
} else {
    // Default 30 days
    $preset = '30days';
    $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
    $endDate   = date('Y-m-d 23:59:59');
}

// 1. Core KPIs for Date Range
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN result = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN result = 'unresolved' THEN 1 ELSE 0 END) as unresolved,
    AVG(match_score) as avg_score
    FROM troubleshooting_sessions 
    WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$reportStats = $stmt->fetch();

$totalPeriodSessions = (int)($reportStats['total'] ?? 0);
$resolvedPeriod      = (int)($reportStats['resolved'] ?? 0);
$unresolvedPeriod    = (int)($reportStats['unresolved'] ?? 0);
$avgMatchScore       = round((float)($reportStats['avg_score'] ?? 0));
$periodResolutionRate= $totalPeriodSessions > 0 ? round(($resolvedPeriod / $totalPeriodSessions) * 100) : 0;

// 2. Most Common Problems in Range
$mcStmt = $pdo->prepare("SELECT p.problem_name, c.category_name, COUNT(s.id) as rep_count,
                         SUM(CASE WHEN s.result = 'resolved' THEN 1 ELSE 0 END) as resolved_count
                         FROM troubleshooting_sessions s 
                         JOIN problems p ON s.problem_id = p.id 
                         JOIN categories c ON s.category_id = c.id 
                         WHERE s.created_at BETWEEN ? AND ? 
                         GROUP BY p.id 
                         ORDER BY rep_count DESC LIMIT 10");
$mcStmt->execute([$startDate, $endDate]);
$commonProblems = $mcStmt->fetchAll();

// 3. Most Common Categories
$catStmt = $pdo->prepare("SELECT c.category_name, COUNT(s.id) as session_count 
                          FROM troubleshooting_sessions s 
                          JOIN categories c ON s.category_id = c.id 
                          WHERE s.created_at BETWEEN ? AND ? 
                          GROUP BY c.id 
                          ORDER BY session_count DESC");
$catStmt->execute([$startDate, $endDate]);
$categoryBreakdown = $catStmt->fetchAll();

$catReportLabels = array_column($categoryBreakdown, 'category_name');
$catReportCounts = array_map('intval', array_column($categoryBreakdown, 'session_count'));

// 4. Most Frequently Recommended Solutions
$solStmt = $pdo->prepare("SELECT sol.solution_description, p.problem_name, COUNT(s.id) as times_recommended 
                          FROM troubleshooting_sessions s 
                          JOIN problems p ON s.problem_id = p.id 
                          JOIN solutions sol ON p.id = sol.problem_id 
                          WHERE s.created_at BETWEEN ? AND ? 
                          GROUP BY sol.id 
                          ORDER BY times_recommended DESC LIMIT 5");
$solStmt->execute([$startDate, $endDate]);
$topSolutions = $solStmt->fetchAll();

// 5. Unresolved Problems List
$unresStmt = $pdo->prepare("SELECT s.*, p.problem_name, u.full_name, c.category_name 
                            FROM troubleshooting_sessions s 
                            JOIN problems p ON s.problem_id = p.id 
                            JOIN users u ON s.user_id = u.id 
                            JOIN categories c ON s.category_id = c.id 
                            WHERE s.result = 'unresolved' AND s.created_at BETWEEN ? AND ? 
                            ORDER BY s.created_at DESC LIMIT 10");
$unresStmt->execute([$startDate, $endDate]);
$unresolvedList = $unresStmt->fetchAll();

// 6. User Feedback Breakdown in Range
$fbStmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN f.rating = 'helpful' THEN 1 ELSE 0 END) as helpful,
    SUM(CASE WHEN f.rating = 'partial' THEN 1 ELSE 0 END) as partial,
    SUM(CASE WHEN f.rating = 'not_helpful' THEN 1 ELSE 0 END) as not_helpful
    FROM feedback f 
    WHERE f.created_at BETWEEN ? AND ?");
$fbStmt->execute([$startDate, $endDate]);
$fbStats = $fbStmt->fetch();

$fbHelpful    = (int)($fbStats['helpful'] ?? 0);
$fbPartial    = (int)($fbStats['partial'] ?? 0);
$fbNotHelpful = (int)($fbStats['not_helpful'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Diagnostic Reports &amp; Analytics</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Reports &amp; Historical Performance</span>
        </div>
      </div>
      <div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> Print / Export Report
        </button>
      </div>
    </div>

    <!-- Date Range Filter Bar -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/reports.php" class="row g-2 align-items-center">
        <div class="col-sm-6 col-md-3">
          <select name="preset" class="form-select ts-form-control" onchange="toggleCustomDates(this.value)">
            <option value="daily" <?= $preset === 'daily' ? 'selected' : '' ?>>Today (Daily)</option>
            <option value="weekly" <?= $preset === 'weekly' ? 'selected' : '' ?>>Past 7 Days (Weekly)</option>
            <option value="monthly" <?= $preset === 'monthly' ? 'selected' : '' ?>>Past 30 Days (Monthly)</option>
            <option value="custom" <?= $preset === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
          </select>
        </div>
        <div class="col-sm-6 col-md-3 custom-date-col <?= $preset !== 'custom' ? 'd-none' : '' ?>">
          <input type="date" name="from" class="form-control ts-form-control" value="<?= e($from ?: date('Y-m-d', strtotime('-30 days'))) ?>">
        </div>
        <div class="col-sm-6 col-md-3 custom-date-col <?= $preset !== 'custom' ? 'd-none' : '' ?>">
          <input type="date" name="to" class="form-control ts-form-control" value="<?= e($to ?: date('Y-m-d')) ?>">
        </div>
        <div class="col-sm-6 col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar-check"></i> Apply Filter</button>
        </div>
      </form>
    </div>

    <!-- Period KPI Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-navy"><?= $totalPeriodSessions ?></div>
          <div class="ts-stat-label">Sessions in Range</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-success"><?= $periodResolutionRate ?>%</div>
          <div class="ts-stat-label">Resolution Rate</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-danger"><?= $unresolvedPeriod ?></div>
          <div class="ts-stat-label">Unresolved Problems</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-card p-3 text-center">
          <div class="ts-stat-value text-ts-accent"><?= $avgMatchScore ?>%</div>
          <div class="ts-stat-label">Avg. Match Score</div>
        </div>
      </div>
    </div>

    <!-- Charts Row: Categories & Feedback -->
    <div class="row g-4 mb-4">
      <div class="col-lg-7">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-bar-chart-fill text-primary"></i>
            <span>Sessions by IT Category</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="reportCatChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="ts-card h-100">
          <div class="ts-card-header">
            <i class="bi bi-pie-chart-fill text-primary"></i>
            <span>User Feedback Rating Breakdown</span>
          </div>
          <div class="ts-card-body">
            <div class="ts-chart-container">
              <canvas id="reportFbChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table 1: Most Common Problems & Resolution Ratios -->
    <div class="ts-card mb-4">
      <div class="ts-card-header">
        <i class="bi bi-trophy-fill text-warning"></i>
        <span>Top 10 Most Frequently Reported IT Problems</span>
      </div>
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Problem Name</th>
                <th>Category</th>
                <th>Session Reports</th>
                <th>Resolved Count</th>
                <th>Success Rate</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($commonProblems)): ?>
                <?php foreach ($commonProblems as $idx => $cp): ?>
                  <?php $rate = $cp['rep_count'] > 0 ? round(($cp['resolved_count'] / $cp['rep_count']) * 100) : 0; ?>
                  <tr>
                    <td class="fw-bold text-muted">#<?= $idx + 1 ?></td>
                    <td class="fw-semibold text-navy"><?= e($cp['problem_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($cp['category_name']) ?></span></td>
                    <td class="fw-bold text-navy"><?= $cp['rep_count'] ?></td>
                    <td class="text-success fw-semibold"><?= $cp['resolved_count'] ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                          <div class="progress-bar bg-success" style="width: <?= $rate ?>%;"></div>
                        </div>
                        <span class="small fw-semibold"><?= $rate ?>%</span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No problem sessions recorded in this date window.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Table 2: Most Frequently Recommended Solutions -->
    <div class="ts-card mb-4">
      <div class="ts-card-header">
        <i class="bi bi-lightbulb-fill text-success"></i>
        <span>Most Frequently Recommended Solutions</span>
      </div>
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Associated Problem</th>
                <th>Prescribed Solution Summary</th>
                <th class="text-end">Times Recommended</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($topSolutions)): ?>
                <?php foreach ($topSolutions as $ts): ?>
                  <tr>
                    <td class="fw-semibold text-navy" style="white-space: nowrap;"><?= e($ts['problem_name']) ?></td>
                    <td class="small text-muted">&ldquo;<?= e($ts['solution_description']) ?>&rdquo;</td>
                    <td class="text-end fw-bold text-navy"><?= $ts['times_recommended'] ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center py-4 text-muted">No solution recommendation data available.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Table 3: Unresolved Problems Requiring Technical Attention -->
    <div class="ts-card">
      <div class="ts-card-header text-danger">
        <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
        <span>Unresolved Diagnostic Sessions (Candidate for Procedure Revision)</span>
      </div>
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Session ID</th>
                <th>User</th>
                <th>Problem</th>
                <th>Category</th>
                <th>User Notes</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($unresolvedList)): ?>
                <?php foreach ($unresolvedList as $unres): ?>
                  <tr>
                    <td class="small text-muted">#<?= $unres['id'] ?></td>
                    <td class="fw-semibold text-navy"><?= e($unres['full_name']) ?></td>
                    <td class="text-navy"><?= e($unres['problem_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($unres['category_name']) ?></span></td>
                    <td class="small text-muted text-truncate" style="max-width: 250px;">&ldquo;<?= e($unres['user_description']) ?>&rdquo;</td>
                    <td class="small text-muted"><?= formatDate($unres['created_at'], 'M d, Y') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Zero unresolved problems in this date window! Great performance.</td>
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
function toggleCustomDates(val) {
  document.querySelectorAll('.custom-date-col').forEach(el => {
    if (val === 'custom') {
      el.classList.remove('d-none');
    } else {
      el.classList.add('d-none');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  // Category Bar Chart
  const cCtx = document.getElementById('reportCatChart');
  if (cCtx) {
    new Chart(cCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($catReportLabels) ?>,
        datasets: [{
          label: 'Troubleshooting Sessions',
          data: <?= json_encode($catReportCounts) ?>,
          backgroundColor: '#1a6eb5',
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

  // Feedback Pie Chart
  const fCtx = document.getElementById('reportFbChart');
  if (fCtx) {
    new Chart(fCtx, {
      type: 'doughnut',
      data: {
        labels: ['Helpful', 'Partially', 'Not Helpful'],
        datasets: [{
          data: [<?= $fbHelpful ?>, <?= $fbPartial ?>, <?= $fbNotHelpful ?>],
          backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        cutout: '60%'
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
