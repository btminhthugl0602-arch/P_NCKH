<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
/**
 * Hàm chèn dữ liệu
 */
function _insert_info($conn, $table, $fields = [], $values = [])
{
    $field_list = implode(", ", $fields);

    foreach ($values as $key => $value) {
        if (is_string($value)) {
            $values[$key] = "'" . mysqli_real_escape_string($conn, $value) . "'";
        } elseif (is_null($value)) {
            $values[$key] = "NULL";
        }
    }

    $values_list = implode(", ", $values);

    $sql = "INSERT INTO $table ($field_list) VALUES ($values_list)";
    if (!mysqli_query($conn, $sql)) {
        error_log("SQL Error in _insert_info: " . mysqli_error($conn));
        return false;
    }
    return true;
}

/**
 * Hàm cập nhật dữ liệu (Logic cũ - Nối chuỗi)
 */
function _update_info($conn, $table, $fields = [], $values = [], $conditions = [])
{
    if (count($fields) != count($values)) {
        error_log("Lỗi _update_info: Số lượng trường và giá trị không khớp!");
        return false;
    }

    $clause = "";
    $set_clause = "";

    // Câu lệnh điều kiện
    foreach ($conditions as $key => $condition) {
        $operator = $condition[0];
        if (is_string($condition[1])) {
            $condition_value = "'" . mysqli_real_escape_string($conn, $condition[1]) . "'";
        } else {
            $condition_value = $condition[1];
        }
        $logic = $condition[2] ?? '';
        $clause .= "$key $operator $condition_value $logic ";
    }

    // Câu lệnh SET
    for ($i = 0; $i < count($fields); $i++) {
        if (is_string($values[$i])) {
            $values[$i] = "'" . mysqli_real_escape_string($conn, $values[$i]) . "'";
        }
        $set_clause .= $fields[$i] . " = " . $values[$i];
        if ($i < count($fields) - 1) {
            $set_clause .= ", ";
        }
    }

    if (!empty($clause)) {
        $clause = "WHERE " . $clause;
    }

    $sql = "UPDATE $table SET $set_clause " . $clause;
    if (!mysqli_query($conn, $sql)) {
        error_log("SQL Error in _update_info: " . mysqli_error($conn));
        return false;
    }
    return true;
}

/**
 * Hàm lấy dữ liệu (Prepared Statements - Hỗ trợ ORDER BY, LIMIT)
 */
function _select_info($conn, $table, $fields = [], $conditions = [])
{
    $field_list = implode(", ", $fields);
    if (empty($field_list)) $field_list = "*";

    $clause = "";
    $params = [];
    $types = "";

    foreach ($conditions as $key => $condition) {
        $key_upper = strtoupper(trim($key));
        $clause .= " $key_upper ";

        $i = 0;
        while ($i < count($condition)) {
            $col_name = $condition[$i] ?? '';
            $operator = $condition[$i + 1] ?? '';
            $value    = $condition[$i + 2] ?? '';
            $logic    = $condition[$i + 3] ?? '';

            // Bỏ qua nếu tên cột rỗng (trừ trường hợp LIMIT)
            if (($col_name === '' || $col_name === null) && $key_upper !== 'LIMIT') {
                $i += 4;
                continue;
            }

            if ($key_upper === 'ORDER BY') {
                $clause .= "$col_name $operator $logic ";
            } elseif ($key_upper === 'LIMIT') {
                $clause .= "$col_name ";
            } else {
                $clause .= "$col_name $operator ? $logic ";
                $params[] = $value;

                if (is_int($value)) $types .= "i";
                else if (is_float($value)) $types .= "d";
                else $types .= "s";
            }
            $i += 4;
        }
    }

    $sql = "SELECT $field_list FROM $table " . $clause;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Lỗi chuẩn bị SQL (_select_info): " . mysqli_error($conn));
        return false;
    }

    if (count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Lỗi thực thi SQL (_select_info): " . mysqli_error($conn));
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $data;
}
function _delete_info($conn, $table, $conditions = [])
{
    if (empty($conditions)) {
        error_log("Lỗi _delete_info: Điều kiện xóa rỗng!");
        return false;
    }

    $clause = "";
    foreach ($conditions as $key => $condition) {
        $operator = $condition[0];
        if (is_string($condition[1])) {
            $condition_value = "'" . mysqli_real_escape_string($conn, $condition[1]) . "'";
        } else {
            $condition_value = $condition[1];
        }
        $logic = $condition[2] ?? '';
        $clause .= "$key $operator $condition_value $logic ";
    }

    $sql = "DELETE FROM $table WHERE $clause";
    if (!mysqli_query($conn, $sql)) {
        error_log("SQL Error in _delete_info: " . mysqli_error($conn));
        return false;
    }
    return true;
}
/**
 * Hàm kiểm tra tồn tại (Sử dụng Prepared Statement)
 */
function _is_exist($conn, $table, $field, $value)
{
    $sql = "SELECT $field FROM $table WHERE $field = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "s", $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $row_count = mysqli_stmt_num_rows($stmt);
    mysqli_stmt_close($stmt);
    return $row_count > 0;
}

// ==========================================
// CÁC HÀM HELPER (Auth & Logic)
// ==========================================

function chuan_hoa_chuoi_sql($conn, $str)
{
    return mysqli_real_escape_string($conn, trim($str));
}

function kiem_tra_ton_tai_ban_ghi($conn, $bang, $cot, $gia_tri)
{
    return _is_exist($conn, $bang, $cot, $gia_tri);
}

function truy_van_mot_ban_ghi($conn, $bang, $cot_khoa, $gia_tri_khoa)
{
    $conditions = [
        'WHERE' => [
            $cot_khoa,
            '=',
            $gia_tri_khoa,
            ''
        ],
        'LIMIT' => [1, '', '', '']
    ];

    $data = _select_info($conn, $bang, [], $conditions);
    return !empty($data) ? $data[0] : null;
}

/**
 * Ánh xạ mã quyền -> idQuyen
 * CSDL mới dùng maQuyen_code để code backend kiểm tra.
 * (Nếu cần tương thích cũ, vẫn fallback maQuyen)
 */
function anh_xa_ma_quyen($conn, $ma_quyen_code)
{
    $safe = mysqli_real_escape_string($conn, trim((string)$ma_quyen_code));
    if ($safe === '') return null;

    // Ưu tiên maQuyen_code
    $res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE maQuyen_code='$safe' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        return (int)$r['idQuyen'];
    }

    // Fallback: maQuyen (để không phá phần cũ nếu còn dùng)
    $res2 = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE maQuyen='$safe' LIMIT 1");
    if ($res2 && mysqli_num_rows($res2) > 0) {
        $r2 = mysqli_fetch_assoc($res2);
        return (int)$r2['idQuyen'];
    }

    return null;
}

/**
 * Kiểm tra quyền HỆ THỐNG (HE_THONG) theo bảng taikhoan_quyen.
 * Truyền vào maQuyen_code (vd: admin_events, admin_users)
 */
function kiem_tra_quyen_he_thong($conn, $id_tai_khoan, $ma_quyen_code)
{
    // DEV MODE: bypass quyền
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) {
        return true;
    }

    $user = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $id_tai_khoan);
    if (!$user) return false;

    // Admin hệ thống: full quyền
    if ((int)$user['idLoaiTK'] === 1) return true;

    $id_quyen = anh_xa_ma_quyen($conn, $ma_quyen_code);
    if (!$id_quyen) return false;

    $conditions = [
        'WHERE' => [
            'idTK',
            '=',
            (int)$id_tai_khoan,
            'AND',
            'idQuyen',
            '=',
            (int)$id_quyen,
            'AND',
            'isActive',
            '=',
            1,
            ''
        ],
        'LIMIT' => [1, '', '', '']
    ];

    $result = _select_info($conn, 'TAIKHOAN_QUYEN', ['thoiGianBatDau', 'thoiGianKetThuc'], $conditions);
    if (empty($result)) return false;

    $quyen_tk = $result[0];
    $now = time();
    $start = strtotime($quyen_tk['thoiGianBatDau']);
    $end = !empty($quyen_tk['thoiGianKetThuc']) ? strtotime($quyen_tk['thoiGianKetThuc']) : null;

    return ($start <= $now && ($end === null || $end >= $now));
}

/**
 * Kiểm tra quyền THEO SỰ KIỆN (SU_KIEN) dựa trên CSDL mới.
 * - Role của user trong sự kiện: taikhoan_vaitro_sukien (isActive=1)
 * - Role metadata: vaitro_sukien (isActive=1)
 *   + isSystem=1 => quyền lấy từ vaitro_quyen theo idVaiTroGoc
 *   + isSystem=0 => quyền lấy từ vaitro_quyen_sk theo idVaiTroSK
 * - Quyền match theo quyen.maQuyen_code (phamVi='SU_KIEN')
 */
function kiem_tra_quyen_su_kien($conn, int $idTK, int $idSK, string $maQuyenCode): bool
{
    // DEV MODE: bypass quyền
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) {
        return true;
    }

    if ($idTK <= 0 || $idSK <= 0 || trim($maQuyenCode) === '') return false;

    $idTK = (int)$idTK;
    $idSK = (int)$idSK;
    $code = mysqli_real_escape_string($conn, trim($maQuyenCode));

    // Admin hệ thống: full quyền
    $user = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $idTK);
    if ($user && (int)$user['idLoaiTK'] === 1) return true;

    $sql = "
        SELECT 1
        FROM taikhoan_vaitro_sukien tvs
        JOIN vaitro_sukien vts 
             ON vts.idVaiTroSK = tvs.idVaiTroSK 
            AND vts.idSK = tvs.idSK
            AND vts.isActive = 1
        LEFT JOIN vaitro_quyen vq 
               ON vts.isSystem = 1 
              AND vts.idVaiTroGoc IS NOT NULL
              AND vq.idVaiTro = vts.idVaiTroGoc
        LEFT JOIN vaitro_quyen_sk vqsk
               ON vts.isSystem = 0
              AND vqsk.idVaiTroSK = vts.idVaiTroSK
        JOIN quyen q 
          ON q.idQuyen = COALESCE(vq.idQuyen, vqsk.idQuyen)
        WHERE tvs.idTK = $idTK
          AND tvs.idSK = $idSK
          AND tvs.isActive = 1
          AND q.phamVi = 'SU_KIEN'
          AND q.maQuyen_code = '$code'
        LIMIT 1
    ";

    $res = mysqli_query($conn, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

/**
 * Check user có ít nhất 1 quyền trong list (SU_KIEN)
 */
function kiem_tra_bat_ky_quyen_su_kien($conn, int $idTK, int $idSK, array $codes): bool
{
    foreach ($codes as $c) {
        $c = trim((string)$c);
        if ($c !== '' && kiem_tra_quyen_su_kien($conn, $idTK, $idSK, $c)) {
            return true;
        }
    }
    return false;
}


//Hàm thêm layouts
function layout($layout_name, $data = [])
{
    if (file_exists(_PATH_URL_TEMPLATES . '/layouts/' . $layout_name . '.php')) {
        require_once(_PATH_URL_TEMPLATES . '/layouts/' . $layout_name . '.php');
    }
}

//Kiểm tra phương thức Get
function isGet()
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return true;
    }
    return false;
}

//Kiểm tra phương thức Post
function isPost()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return true;
    }
    return false;
}

//Hàm filter lọc dữ liệu
function filter()
{
    $filterArr = [];
    if (isGet()) {
        foreach ($_GET as $key => $value) {
            $filterArr[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }
    if (isPost()) {
        foreach ($_POST as $key => $value) {
            $filterArr[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }
    return $filterArr;
}