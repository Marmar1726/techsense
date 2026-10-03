<?php
// ============================================================
// TechSense — Utility Functions
// ============================================================

/**
 * Sanitize a string for safe HTML output.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize input and trim whitespace.
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Hash a password using bcrypt.
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against its hash.
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Validate password strength.
 * Must be at least 8 characters, 1 uppercase, 1 lowercase, 1 digit.
 */
function validatePassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Validate email format.
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format a MySQL datetime string to a readable format.
 */
function formatDate(string $datetime, string $format = 'M d, Y h:i A'): string {
    if (empty($datetime)) return '—';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Time elapsed since a date (e.g., "2 hours ago").
 */
function timeAgo(string $datetime): string {
    if (empty($datetime)) return '—';
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Redirect to a URL.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message in session.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear a flash message.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get the count of unread notifications for the current user.
 */
function getUnreadCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Difficulty badge HTML.
 */
function difficultyBadge(string $level): string {
    $map = [
        'Beginner'     => 'success',
        'Intermediate' => 'warning',
        'Advanced'     => 'danger',
    ];
    $color = $map[$level] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . e($level) . '</span>';
}

/**
 * Result badge HTML.
 */
function resultBadge(string $result): string {
    $map = [
        'resolved'    => ['bg-success',  'Resolved'],
        'unresolved'  => ['bg-danger',   'Unresolved'],
        'in_progress' => ['bg-warning text-dark', 'In Progress'],
    ];
    [$class, $label] = $map[$result] ?? ['bg-secondary', ucfirst($result)];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Paginate a query result.
 */
function paginate(PDO $pdo, string $countSql, string $dataSql, array $params, int $page, int $perPage = 10): array {
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $dataStmt = $pdo->prepare($dataSql . " LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params);
    $data = $dataStmt->fetchAll();

    return [
        'data'        => $data,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * Render Bootstrap pagination links.
 */
function renderPagination(int $page, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . ($page - 1) . '">‹ Prev</a></li>';
    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $html .= '<li class="page-item' . ($i === $page ? ' active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . ($page + 1) . '">Next ›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
