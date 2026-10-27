<?php
/**
 * UIU Friends Loan & Crowdfunding — shared helper functions
 */
session_start();
require_once __DIR__ . '/../config/db.php';

/* ---------------------------------------------------------- *
 *  AUTH HELPERS
 * ---------------------------------------------------------- */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array {
    global $pdo;
    if (!is_logged_in()) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cache = $stmt->fetch();
    return $cache;
}

function current_admin(): ?array {
    global $pdo;
    if (!isset($_SESSION['admin_id'])) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

/* ---------------------------------------------------------- *
 *  GENERAL HELPERS
 * ---------------------------------------------------------- */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function taka(float $amount): string {
    return '৳' . number_format($amount, $amount == floor($amount) ? 0 : 2);
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    return floor($diff / 86400) . 'd ago';
}

function truncate_text(string $text, int $len = 160, string $suffix = '…'): string {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . $suffix : $text;
    }
    return strlen($text) > $len ? substr($text, 0, $len) . $suffix : $text;
}

function gen_txn_ref(): string {
    return 'UIUF-TXN-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function flash(string $key, ?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        die('Invalid request (CSRF check failed). Please go back and try again.');
    }
}

/* ---------------------------------------------------------- *
 *  DOMAIN HELPERS
 * ---------------------------------------------------------- */
function user_trust_breakdown(array $user): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) c, AVG(rating) avg_rating FROM reviews WHERE reviewee_id = ?");
    $stmt->execute([$user['id']]);
    $r = $stmt->fetch();
    return [
        'repayment_reliability' => min(99, 70 + ($user['trust_score'] * 6)),
        'response_time'         => 'Excellent',
        'community_reviews'     => $r['avg_rating'] ? round($r['avg_rating'], 1) : $user['trust_score'],
        'review_count'          => (int)$r['c'],
    ];
}

function verification_badge(array $user): string {
    return $user['uiu_id_verified'] ? '<span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>' : '<span class="badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>';
}
