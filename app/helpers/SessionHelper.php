<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

class SessionHelper
{
    /**
     * Kiểm tra đã đăng nhập chưa
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']) && $_SESSION['user_id'] !== 0;
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public static function currentUser(): array
    {
        return [
            'id'   => $_SESSION['user_id']   ?? 0,
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['role']      ?? 'guest',
        ];
    }

    /**
     * Yêu cầu đăng nhập — redirect nếu chưa login
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: ' . _HOST_URL . '/?module=auth&action=login&redirect=' . $redirect);
            exit;
        }
    }

    /**
     * Flash message — hiện 1 lần rồi mất
     */
    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
