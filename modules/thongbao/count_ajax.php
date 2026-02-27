<?php
if (!defined('_AUTHEN')) { echo '{"count":0}'; exit; }
header('Content-Type: application/json');
header('Cache-Control: no-store');

// FIX: dùng user_id thay vì idTK
$id_tk = (int)($_SESSION['user_id'] ?? 0);
if ($id_tk <= 0) { echo json_encode(['count' => 0]); exit; }

require_once _PATH_URL . '/modules/functions/quan_ly_thong_bao.php';
echo json_encode(['count' => dem_chua_doc($conn, $id_tk)]);
