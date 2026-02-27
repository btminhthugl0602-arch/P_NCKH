<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

layout('header');
layout('navbar');
page('event/config_schedule', compact('id_su_kien'));
layout('footer');
