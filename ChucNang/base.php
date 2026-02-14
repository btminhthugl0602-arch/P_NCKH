<?php
    function _insert_info($conn, $table, $fields = [], $values = []){
        $field_list = implode(", ", $fields);

        foreach ($values as $key => $value){
            if (is_string($value)){
                $values[$key] = "'" . mysqli_real_escape_string($conn, $value) . "'";
            } elseif (is_null($value)) {
                $values[$key] = "NULL";
            }
        }

        $values_list = implode(", ", $values);

        $sql = "INSERT INTO $table ($field_list) VALUES ($values_list)";
        if (!mysqli_query($conn, $sql)){
            error_log("SQL Error in _insert_info: " . mysqli_error($conn));
            return false;
        }
        return true;
    }

    function _update_info($conn, $table, $fields = [], $values = [], $conditions = []){
        if (count($fields) != count($values)){
            error_log("Lỗi _update_info: Số lượng trường và giá trị không khớp!");
            return false;
        }

        $clause = "";
        $set_clause = "";

        foreach ($conditions as $key => $condition){
            $operator = $condition[0];
            if (is_string($condition[1])){
                $condition_value = "'" . mysqli_real_escape_string($conn, $condition[1]) . "'";
            } else {
                $condition_value = $condition[1];
            }
            $logic = $condition[2] ?? '';
            $clause .= "$key $operator $condition_value $logic ";
        }
        
        for ($i = 0; $i < count($fields); $i++){
            if (is_string($values[$i])){
                $values[$i] = "'" . mysqli_real_escape_string($conn, $values[$i]) . "'";
            }
            $set_clause .= $fields[$i] . " = " . $values[$i];
            if ($i < count($fields) - 1){
                $set_clause .= ", ";
            }
        }
        
        if (!empty($clause)){
            $clause = "WHERE " . $clause;
        }

        $sql = "UPDATE $table SET $set_clause " . $clause;
        if (!mysqli_query($conn, $sql)){
            error_log("SQL Error in _update_info: " . mysqli_error($conn));
            return false;
        }
        return true;
    }

    function _select_info($conn, $table, $fields = [], $conditions = []){
        $field_list = implode(", ", $fields);
        if (empty($field_list)) $field_list = "*";

        $clause = "";
        $params = [];
        $types = "";
        
        foreach($conditions as $key => $condition){
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
                    $i += 4; continue;
                }
                
                if ($key_upper === 'ORDER BY') {
                    $clause .= "$col_name $operator $logic ";
                } 
                elseif ($key_upper === 'LIMIT') {
                    $clause .= "$col_name ";
                } 
                else {
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
        if (!$result){
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        return $data;
    }

    /**
     * Hàm kiểm tra tồn tại (Sử dụng Prepared Statement)
     */
    function _is_exist($conn, $table, $field, $value){
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

    function chuan_hoa_chuoi_sql($conn, $str) {
        return mysqli_real_escape_string($conn, trim($str));
    }

    function kiem_tra_ton_tai_ban_ghi($conn, $bang, $cot, $gia_tri) {
        return _is_exist($conn, $bang, $cot, $gia_tri);
    }

    function truy_van_mot_ban_ghi($conn, $bang, $cot_khoa, $gia_tri_khoa) {
        $conditions = [
            'WHERE' => [
                $cot_khoa, '=', $gia_tri_khoa, ''
            ],
            'LIMIT' => [ 1, '', '', '' ]
        ];

        $data = _select_info($conn, $bang, [], $conditions);
        return !empty($data) ? $data[0] : null;
    }

    function anh_xa_ma_quyen($conn, $ma_quyen) {
        $quyen = truy_van_mot_ban_ghi($conn, 'QUYEN', 'maQuyen', $ma_quyen);
        return $quyen ? $quyen['idQuyen'] : null;
    }

    function kiem_tra_quyen_he_thong($conn, $id_tai_khoan, $ma_quyen) {
        $user = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $id_tai_khoan);
        if (!$user) return false;

        if ($user['idLoaiTK'] == 1) return true;

        $id_quyen = anh_xa_ma_quyen($conn, $ma_quyen);
        if (!$id_quyen) return false;

        $conditions = [
            'WHERE' => [
                'idTK', '=', $id_tai_khoan, 'AND',
                'idQuyen', '=', $id_quyen, 'AND',
                'isActive', '=', 1, ''
            ],
            'LIMIT' => [ 1, '', '', '' ]
        ];

        $result = _select_info($conn, 'TAIKHOAN_QUYEN', ['thoiGianBatDau', 'thoiGianKetThuc'], $conditions);
        
        if (empty($result)) return false; 

        $quyen_tk = $result[0];
        $now = time();
        $start = strtotime($quyen_tk['thoiGianBatDau']);
        $end = !empty($quyen_tk['thoiGianKetThuc']) ? strtotime($quyen_tk['thoiGianKetThuc']) : null;

        if ($start <= $now && ($end === null || $end >= $now)) {
            return true;
        }

        return false;
    }
?>