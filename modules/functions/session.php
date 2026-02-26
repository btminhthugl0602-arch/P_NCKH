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
