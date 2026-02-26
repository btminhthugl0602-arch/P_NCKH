<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

//Set session (session sử dụng trong tgian dài)
function setSession($key, $value)
{
    if (!empty(session_id())) {
        $_SESSION[$key] = $value;
        return true;
    }
    return false;
}


//lấy dữ liệu từ session
function getSession($key)
{
    if (!empty(session_id()) && isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
    return null;
}

//Xóa session
function removeSession($key)
{
    if (empty($key)) {
        session_destroy();
        return true;
    } else {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return true;
    }
}

//Tạo Session Flash (Sử dụng một lần)
function setSessionFlash($key, $value)
{
    $key = 'flash_' . $key;

    return setSession($key, $value);
}

//Lấy Session Flash
function getSessionFlash($key)
{
    $key = 'flash_' . $key;
    $value = getSession($key);
    removeSession($key);
    return $value;
}

function generateCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token từ form POST
function validateCSRF(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// In ra hidden input CSRF cho form
// Dùng trong view: <?= csrfInput() 

function csrfInput(): string
{
    $token = generateCSRF();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Chặn request nếu CSRF không hợp lệ
function requireCSRF(): void
{
    if (!validateCSRF()) {
        http_response_code(403);
        die('Yêu cầu không hợp lệ. Vui lòng thử lại.');
    }
}
