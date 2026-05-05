<?php
/* ===== COMII LEX — CONFIG.PHP ===== */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'comii_lex');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

session_start();

/* ─── Helper: format price ─── */
function price($val) {
    return '$' . number_format((float)$val, 2);
}

/* ─── Helper: sanitize output ─── */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ─── Helper: get cart count ─── */
function cartCount() {
    $db = getDB();
    $sid = session_id();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
    $stmt->bind_param('s', $sid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

/* ─── Helper: placeholder image ─── */
function productImage($img) {
    if ($img && file_exists(__DIR__ . '/images/' . $img)) {
        return 'images/' . $img;
    }
    // Return a placeholder data URL with gradient
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%231c1c28'/%3E%3Cstop offset='1' stop-color='%23111118'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g)' width='400' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%236366f1' font-size='48' font-family='serif'%3E📦%3C/text%3E%3C/svg%3E";
}
?>
