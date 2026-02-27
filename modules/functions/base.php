<?php

/**
 * base.php — Thư viện hàm lõi của hệ thống
 *
 * THAY ĐỔI SO VỚI PHIÊN BẢN CŨ:
 * ─────────────────────────────────────────────────────────────
 * [FIX] _insert_info            : nối chuỗi + escape  →  Prepared Statement
 * [FIX] _update_info            : nối chuỗi + escape  →  Prepared Statement
 * [FIX] _delete_info            : nối chuỗi + escape  →  Prepared Statement
 * [GIỮ] _select_info            : đã đúng, chỉnh sửa nhỏ cho nhất quán
 * [FIX] anh_xa_ma_quyen         : escape + nối chuỗi  →  Prepared Statement
 * [FIX] kiem_tra_quyen_su_kien  : escape + nối chuỗi  →  Prepared Statement
 * [FIX] kiem_tra_co_nhom_active : nối chuỗi int       →  Prepared Statement
 * [FIX] kiem_tra_duoc_cham_bai  : nối chuỗi int       →  Prepared Statement
 *
 * KHÔNG thay đổi:
 * - Chữ ký tất cả hàm (tên + thứ tự tham số) → code gọi hiện tại KHÔNG cần sửa
 * - Tên biến $conn → vẫn là mysqli connection
 * - layout(), isGet(), isPost(), filter()
 * - chuan_hoa_chuoi_sql() giữ lại (deprecated) để tránh crash code cũ
 * ─────────────────────────────────────────────────────────────
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}


// ============================================================
// PHẦN 1: HÀM CRUD LÕI
// ============================================================

/**
 * [Internal] Trả về ký tự kiểu bind_param cho 1 giá trị
 * i = int | d = float | s = string/null/bool
 */
function _get_bind_type($value): string
{
    if (is_int($value))   return 'i';
    if (is_float($value)) return 'd';
    return 's';
}

/**
 * [Internal] Build chuỗi types cho bind_param từ array values
 * VD: ['Nguyen', 1, 2.5] → "sis" ... thực ra "sid"
 */
function _build_types(array $values): string
{
    return implode('', array_map('_get_bind_type', $values));
}

/**
 * INSERT một bản ghi
 *
 * Cách dùng (không đổi so với cũ):
 *   _insert_info($conn, 'taikhoan', ['tenTK', 'matKhau', 'idLoaiTK'], ['admin', '123', 1]);
 *
 * @return bool
 */
function _insert_info($conn, string $table, array $fields = [], array $values = []): bool
{
    if (empty($fields) || empty($values)) {
        error_log("_insert_info: fields hoặc values rỗng");
        return false;
    }
    if (count($fields) !== count($values)) {
        error_log("_insert_info: số fields và values không khớp [{$table}]");
        return false;
    }

    $field_list   = implode(', ', $fields);
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $sql          = "INSERT INTO {$table} ({$field_list}) VALUES ({$placeholders})";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("_insert_info prepare thất bại [{$table}]: " . mysqli_error($conn));
        return false;
    }

    $types = _build_types($values);
    mysqli_stmt_bind_param($stmt, $types, ...$values);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("_insert_info execute thất bại [{$table}]: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    mysqli_stmt_close($stmt);
    return true;
}

/**
 * UPDATE bản ghi theo điều kiện
 *
 * Cấu trúc $conditions (không đổi so với cũ):
 *   ['tên_cột' => ['toán_tử', giá_trị, 'AND|OR|']]
 *
 * Ví dụ:
 *   _update_info($conn, 'taikhoan',
 *       ['isActive'], [0],
 *       ['idTK' => ['=', 5, '']]
 *   );
 *
 * @return bool
 */
function _update_info($conn, string $table, array $fields = [], array $values = [], array $conditions = []): bool
{
    if (count($fields) !== count($values)) {
        error_log("_update_info: số fields và values không khớp [{$table}]");
        return false;
    }

    // SET clause
    $set_parts = array_map(fn($f) => "{$f} = ?", $fields);
    $set_sql   = implode(', ', $set_parts);

    // WHERE clause
    $where_parts  = [];
    $where_values = [];

    foreach ($conditions as $col => $condition) {
        $operator = $condition[0];
        $val      = $condition[1];
        $logic    = trim($condition[2] ?? '');

        $part = "{$col} {$operator} ?";
        if ($logic !== '') $part .= " {$logic}";

        $where_parts[]  = $part;
        $where_values[] = $val;
    }

    $where_sql = !empty($where_parts) ? 'WHERE ' . implode(' ', $where_parts) : '';
    $sql       = "UPDATE {$table} SET {$set_sql} {$where_sql}";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("_update_info prepare thất bại [{$table}]: " . mysqli_error($conn));
        return false;
    }

    // Gộp params: SET values trước, WHERE values sau
    $all_values = array_merge($values, $where_values);
    $types      = _build_types($all_values);

    mysqli_stmt_bind_param($stmt, $types, ...$all_values);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("_update_info execute thất bại [{$table}]: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    mysqli_stmt_close($stmt);
    return true;
}

/**
 * SELECT nhiều bản ghi
 *
 * Cấu trúc $conditions hỗ trợ: WHERE, ORDER BY, LIMIT
 * (giữ nguyên hoàn toàn như phiên bản cũ)
 *
 * @return array|false
 */
function _select_info($conn, string $table, array $fields = [], array $conditions = [])
{
    $field_list = !empty($fields) ? implode(', ', $fields) : '*';

    $clause = '';
    $params = [];
    $types  = '';

    foreach ($conditions as $key => $condition) {
        $key_upper = strtoupper(trim($key));
        $clause   .= " {$key_upper} ";

        $i = 0;
        while ($i < count($condition)) {
            $col_name = $condition[$i]     ?? '';
            $operator = $condition[$i + 1] ?? '';
            $value    = $condition[$i + 2] ?? '';
            $logic    = $condition[$i + 3] ?? '';

            if (($col_name === '' || $col_name === null) && $key_upper !== 'LIMIT') {
                $i += 4;
                continue;
            }

            if ($key_upper === 'ORDER BY') {
                $clause .= "{$col_name} {$operator} {$logic} ";
            } elseif ($key_upper === 'LIMIT') {
                $clause .= "{$col_name} ";
            } else {
                $clause  .= "{$col_name} {$operator} ? {$logic} ";
                $params[] = $value;
                $types   .= _get_bind_type($value);
            }
            $i += 4;
        }
    }

    $sql  = "SELECT {$field_list} FROM {$table} {$clause}";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log("_select_info prepare thất bại [{$table}]: " . mysqli_error($conn));
        return false;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("_select_info execute thất bại [{$table}]: " . mysqli_stmt_error($stmt));
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

/**
 * DELETE bản ghi theo điều kiện
 *
 * Bắt buộc phải có $conditions — không cho phép DELETE không điều kiện
 *
 * Ví dụ:
 *   _delete_info($conn, 'taikhoan_quyen', [
 *       'idTK'    => ['=', 5,  'AND'],
 *       'idQuyen' => ['=', 12, ''],
 *   ]);
 *
 * @return bool
 */
function _delete_info($conn, string $table, array $conditions = []): bool
{
    if (empty($conditions)) {
        error_log("_delete_info: conditions rỗng — từ chối DELETE không điều kiện [{$table}]");
        return false;
    }

    $where_parts  = [];
    $where_values = [];

    foreach ($conditions as $col => $condition) {
        $operator = $condition[0];
        $val      = $condition[1];
        $logic    = trim($condition[2] ?? '');

        $part = "{$col} {$operator} ?";
        if ($logic !== '') $part .= " {$logic}";

        $where_parts[]  = $part;
        $where_values[] = $val;
    }

    $sql  = "DELETE FROM {$table} WHERE " . implode(' ', $where_parts);
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log("_delete_info prepare thất bại [{$table}]: " . mysqli_error($conn));
        return false;
    }

    $types = _build_types($where_values);
    mysqli_stmt_bind_param($stmt, $types, ...$where_values);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("_delete_info execute thất bại [{$table}]: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    mysqli_stmt_close($stmt);
    return true;
}

/**
 * Kiểm tra tồn tại bản ghi theo 1 cột
 *
 * @return bool
 */
function _is_exist($conn, string $table, string $field, $value): bool
{
    $sql  = "SELECT 1 FROM {$table} WHERE {$field} = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    $type = _get_bind_type($value);
    mysqli_stmt_bind_param($stmt, $type, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $exists;
}


// ============================================================
// PHẦN 2: HÀM HELPER TRUY VẤN
// ============================================================

/**
 * Kiểm tra tồn tại bản ghi — wrapper của _is_exist
 */
function kiem_tra_ton_tai_ban_ghi($conn, string $bang, string $cot, $gia_tri): bool
{
    return _is_exist($conn, $bang, $cot, $gia_tri);
}

/**
 * Lấy 1 bản ghi theo khoá
 *
 * @return array|null
 */
function truy_van_mot_ban_ghi($conn, string $bang, string $cot_khoa, $gia_tri_khoa): ?array
{
    $conditions = [
        'WHERE' => [$cot_khoa, '=', $gia_tri_khoa, ''],
        'LIMIT' => [1, '', '', ''],
    ];

    $data = _select_info($conn, $bang, [], $conditions);
    return (!empty($data) && is_array($data)) ? $data[0] : null;
}

/**
 * Ánh xạ maQuyen_code → idQuyen
 *
 * [FIX] Thay escape + nối chuỗi → Prepared Statement
 *
 * @return int|null
 */
function anh_xa_ma_quyen($conn, $ma_quyen_code): ?int
{
    $code = trim((string)$ma_quyen_code);
    if ($code === '') return null;

    // Ưu tiên maQuyen_code
    $stmt = mysqli_prepare($conn, "SELECT idQuyen FROM quyen WHERE maQuyen_code = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $code);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            mysqli_stmt_close($stmt);
            return (int)$row['idQuyen'];
        }
        mysqli_stmt_close($stmt);
    }

    // Fallback maQuyen (tương thích ngược)
    $stmt2 = mysqli_prepare($conn, "SELECT idQuyen FROM quyen WHERE maQuyen = ? LIMIT 1");
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 's', $code);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        if ($res2 && $row2 = mysqli_fetch_assoc($res2)) {
            mysqli_stmt_close($stmt2);
            return (int)$row2['idQuyen'];
        }
        mysqli_stmt_close($stmt2);
    }

    return null;
}

/**
 * Kiểm tra quyền HỆ THỐNG của tài khoản
 */
function kiem_tra_quyen_he_thong($conn, $id_tai_khoan, string $ma_quyen_code): bool
{
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) return true;

    $user = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', (int)$id_tai_khoan);
    if (!$user) return false;

    // Admin hệ thống: full quyền không cần check bảng
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
            '',
        ],
        'LIMIT' => [1, '', '', ''],
    ];

    $result = _select_info($conn, 'taikhoan_quyen', ['thoiGianBatDau', 'thoiGianKetThuc'], $conditions);
    if (empty($result)) return false;

    $now   = time();
    $start = strtotime($result[0]['thoiGianBatDau']);
    $end   = !empty($result[0]['thoiGianKetThuc']) ? strtotime($result[0]['thoiGianKetThuc']) : null;

    return ($start <= $now && ($end === null || $end >= $now));
}

/**
 * Kiểm tra quyền THEO SỰ KIỆN của tài khoản
 *
 * [FIX] escape + nối chuỗi → Prepared Statement
 */
function kiem_tra_quyen_su_kien($conn, int $idTK, int $idSK, string $maQuyenCode): bool
{
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) return true;
    if ($idTK <= 0 || $idSK <= 0 || trim($maQuyenCode) === '') return false;

    // Admin hệ thống: full quyền
    $user = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    if ($user && (int)$user['idLoaiTK'] === 1) return true;

    $sql = "
        SELECT 1
        FROM taikhoan_vaitro_sukien tvs
        JOIN vaitro_quyen vq ON tvs.idVaiTro = vq.idVaiTro
        JOIN quyen q         ON vq.idQuyen   = q.idQuyen
        WHERE tvs.idTK       = ?
          AND tvs.idSK       = ?
          AND tvs.isActive   = 1
          AND q.phamVi       = 'SU_KIEN'
          AND q.maQuyen_code = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, 'iis', $idTK, $idSK, $maQuyenCode);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $found;
}

/**
 * Kiểm tra user có nhóm active trong sự kiện
 *
 * [FIX] nối chuỗi int → Prepared Statement
 */
function kiem_tra_co_nhom_active($conn, int $idTK, int $idSK): bool
{
    if ($idTK <= 0 || $idSK <= 0) return false;

    $sql = "
        SELECT 1
        FROM thanhviennhom tv
        JOIN nhom n ON tv.idnhom = n.idnhom
        WHERE tv.idtk      = ?
          AND n.idSK        = ?
          AND tv.trangthai  = 1
          AND n.isActive    = 1
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, 'ii', $idTK, $idSK);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $found;
}

/**
 * Kiểm tra user có ít nhất 1 quyền trong danh sách (SU_KIEN)
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

/**
 * Kiểm tra GV được phân công chấm bài trong vòng thi
 *
 * [FIX] nối chuỗi int → Prepared Statement
 */
function kiem_tra_duoc_cham_bai($conn, int $idGV, int $idSP, int $idVong): bool
{
    if ($idGV <= 0 || $idSP <= 0 || $idVong <= 0) return false;

    // UNION: kiểm tra cả 2 luồng phân công
    $sql = "
        SELECT 1 FROM phancong_doclap
        WHERE idGV = ? AND idSanPham = ? AND idVongThi = ?
        UNION
        SELECT 1
        FROM tieuban_giangvien tbg
        JOIN tieuban_sanpham tbs ON tbg.idTieuBan = tbs.idTieuBan
        JOIN tieuban tb          ON tbg.idTieuBan = tb.idTieuBan
        WHERE tbg.idGV     = ?
          AND tbs.idSanPham = ?
          AND tb.idVongThi  = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    // 6 params: 3 cho UNION phần 1, 3 cho phần 2
    mysqli_stmt_bind_param($stmt, 'iiiiii', $idGV, $idSP, $idVong, $idGV, $idSP, $idVong);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $found;
}


// ============================================================
// PHẦN 3: HÀM TIỆN ÍCH
// ============================================================

/**
 * Include layout template (header, footer, navbar...)
 */
function layout(string $layout_name, array $data = []): void
{
    $path = _PATH_URL_TEMPLATES . '/layouts/' . $layout_name . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}

/**
 * Include page view từ template/pages/
 * Controller truyền data xuống view qua $data array.
 *
 * Ví dụ:
 *   page('auth/login', ['tb_dang_nhap' => $msg, 'error_class' => 'danger']);
 *   page('event/view', ['event' => $event, 'nhom' => $nhom]);
 */
function page(string $page_name, array $data = []): void
{
    extract($data);
    $path = _PATH_URL_TEMPLATES . '/pages/' . $page_name . '.php';
    if (file_exists($path)) {
        require $path;
    } else {
        error_log("page() không tìm thấy view: {$path}");
    }
}

// ============================================================
// PHẦN 4: LAZY SINGLETON — VAI TRÒ TỪ DB
// ============================================================

/**
 * Lấy idvatro theo tenvaitro — query 1 lần, cache static.
 * Thay thế cho constants hardcode (VAITRO_BTC, VAITRO_THAM_GIA...)
 *
 * Ví dụ: lay_id_vaitro($conn, 'BTC')  →  1
 */
function lay_id_vaitro($conn, string $ten): int
{
    static $cache = [];
    if (empty($cache)) {
        $rows = mysqli_query($conn, "SELECT idvatro, tenvaitro FROM vaitro");
        if ($rows) {
            while ($r = mysqli_fetch_assoc($rows)) {
                $cache[$r['tenvaitro']] = (int)$r['idvatro'];
            }
        }
    }
    return $cache[$ten] ?? 0;
}

/**
 * Lấy id vaitronhom theo tên — query 1 lần, cache static.
 *
 * Ví dụ: lay_id_vaitronhom($conn, 'Trưởng nhóm')  →  1
 */
function lay_id_vaitronhom($conn, string $ten): int
{
    static $cache = [];
    if (empty($cache)) {
        $rows = mysqli_query($conn, "SELECT id, tenvaitronhom FROM vaitronhom");
        if ($rows) {
            while ($r = mysqli_fetch_assoc($rows)) {
                $cache[$r['tenvaitronhom']] = (int)$r['id'];
            }
        }
    }
    return $cache[$ten] ?? 0;
}

/** Kiểm tra request là GET */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/** Kiểm tra request là POST */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Lọc toàn bộ input GET/POST — loại bỏ ký tự HTML đặc biệt
 *
 * ⚠️  Kết quả của filter() KHÔNG được nối thẳng vào SQL.
 *     Mọi giá trị truyền vào DB phải qua Prepared Statement.
 */
function filter(): array
{
    $out = [];

    if (isGet()) {
        foreach ($_GET as $key => $value) {
            $out[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    if (isPost()) {
        foreach ($_POST as $key => $value) {
            $out[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    return $out;
}

/**
 * @deprecated Không dùng cho code mới.
 *
 * Giữ lại để tránh crash các file cũ đang gọi hàm này.
 * Trước đây escape trước khi nối chuỗi vào SQL — nay không cần
 * vì đã dùng Prepared Statement. Chỉ trả về trim($str).
 */
function chuan_hoa_chuoi_sql($conn, string $str): string
{
    return trim($str);
}