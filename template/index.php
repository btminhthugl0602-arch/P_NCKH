<?php
if (!defined('_AUTHEN')) {
  die('Truy cập không hợp lệ');
}

// Xác định module đang active để highlight sidebar
$currentModule = isset($_GET['module']) ? $_GET['module'] : _MODULES;
$currentAction = isset($_GET['action']) ? $_GET['action'] : _ACTION;

function isActive($module)
{
  global $currentModule;
  return $currentModule === $module ? 'active bg-gradient-dark text-white' : 'text-dark';
}
