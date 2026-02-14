<?php
const _AUTHEN = true;

const _MODULES = 'dashboard';
const _ACTION = 'index';

//Khai báo database
const _HOST = 'localhost';
const _DB = 'nckh';
const _USER = 'root';
const _PASS = '';
const _DRIVER = 'mysql';

//debug
const _DEBUG = true;

//  Thiết lập host
define('_HOST_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/NCKH2');
define('_HOST_URL_TEMPLATES', 'http://' . $_SERVER['HTTP_HOST'] . '/NCKH2/template');

//  Thiết lập path
define('_PATH_URL', __DIR__);
define('_PATH_URL_TEMPLATES', _PATH_URL . '/template');
