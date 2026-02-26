<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

class SecurityHelper
{
    // ============================================================
    // CSRF Token
    // ============================================================

    /**
     * Tạo CSRF token, lưu vào session
     */
    public static function generateCSRF(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token từ form POST
     */
    public static function validateCSRF(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * In ra hidden input CSRF cho form
     * Dùng trong view: <?= SecurityHelper::csrfInput() ?>
     */
    public static function csrfInput(): string
    {
        $token = self::generateCSRF();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Chặn request nếu CSRF không hợp lệ
     */
    public static function requireCSRF(): void
    {
        if (!self::validateCSRF()) {
            http_response_code(403);
            die('Yêu cầu không hợp lệ. Vui lòng thử lại.');
        }
    }

    // ============================================================
    // XSS Protection
    // ============================================================

    /**
     * Escape output an toàn — dùng thay cho <?= $var ?>
     * Dùng trong view: <?= e($var) ?>
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // ============================================================
    // Rate Limiting — chống brute force login
    // ============================================================

    /**
     * Kiểm tra IP có bị block không
     */
    public static function isBlocked(string $ip): bool
    {
        $key     = 'login_fail_' . md5($ip);
        $count   = $_SESSION[$key . '_count'] ?? 0;
        $lockout = $_SESSION[$key . '_lockout'] ?? 0;

        if ($lockout && time() < $lockout) {
            return true; // Đang bị lock
        }

        if ($lockout && time() >= $lockout) {
            // Hết thời gian lock → reset
            unset($_SESSION[$key . '_count'], $_SESSION[$key . '_lockout']);
        }

        return false;
    }

    /**
     * Ghi nhận login thất bại
     * Sau 5 lần sai → lock 15 phút
     */
    public static function recordLoginFail(string $ip): void
    {
        $key   = 'login_fail_' . md5($ip);
        $count = ($_SESSION[$key . '_count'] ?? 0) + 1;
        $_SESSION[$key . '_count'] = $count;

        if ($count >= 5) {
            $_SESSION[$key . '_lockout'] = time() + (15 * 60); // 15 phút
            $_SESSION[$key . '_count']   = 0;
        }
    }

    /**
     * Reset login fail khi đăng nhập thành công
     */
    public static function resetLoginFail(string $ip): void
    {
        $key = 'login_fail_' . md5($ip);
        unset($_SESSION[$key . '_count'], $_SESSION[$key . '_lockout']);
    }

    /**
     * Lấy thời gian còn lại bị block (giây)
     */
    public static function getBlockedTime(string $ip): int
    {
        $key     = 'login_fail_' . md5($ip);
        $lockout = $_SESSION[$key . '_lockout'] ?? 0;
        return $lockout ? max(0, $lockout - time()) : 0;
    }
}
