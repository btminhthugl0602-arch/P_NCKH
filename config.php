<?php
const _AUTHEN = true;

// Bật chế độ bỏ qua kiểm tra quyền (DEV)
// Khi deploy thật thì set false
const _BYPASS_AUTH = true;

const _MODULES = 'home';
const _ACTION = 'index';

//Khai báo database
const _HOST = 'localhost';
const _DB = 'nckh';
const _USER = 'root';
const _PASS = '';
const _DRIVER = 'mysql';

//debug
const _DEBUG = true;

//  Thiết lập host - CÁCH MỚI: Tự động detect base path
// Lấy đường dẫn thực tế của script
$script_name = dirname($_SERVER['SCRIPT_NAME']);
// Loại bỏ dấu / ở cuối nếu có
$script_name = rtrim($script_name, '/');

define('_HOST_URL', 'http://' . $_SERVER['HTTP_HOST'] . $script_name);
define('_HOST_URL_TEMPLATES', _HOST_URL . '/template');

//  Thiết lập path
define('_PATH_URL', __DIR__);
define('_PATH_URL_TEMPLATES', _PATH_URL . '/template');