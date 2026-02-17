<?php

/**
 * Gửi thông báo (Chung hoặc Riêng)
 */
function gui_thong_bao($conn, $id_nguoi_gui, $id_sk, $tieu_de, $noi_dung, $loai_thong_bao = 'Chung', $is_public = 1, $ds_nguoi_nhan = []) {
    // 1. Tạo thông báo gốc
    // CSDL: thongbao(idSK, tieuDe, noiDung, loaiThongBao, nguoiGui, ngayGui, isPublic)
    $res_tb = _insert_info($conn, 'thongbao',
        ['idSK', 'tieuDe', 'noiDung', 'loaiThongBao', 'nguoiGui', 'ngayGui', 'isPublic'],
        [$id_sk, $tieu_de, $noi_dung, $loai_thong_bao, $id_nguoi_gui, date('Y-m-d H:i:s'), $is_public]
    );

    if (!$res_tb) return ['status' => false, 'message' => 'Lỗi tạo thông báo'];
    $id_thong_bao = mysqli_insert_id($conn);

    // 2. Nếu là thông báo riêng -> Gửi cho từng người
    if (!$is_public && !empty($ds_nguoi_nhan)) {
        foreach ($ds_nguoi_nhan as $id_tk_nhan) {
            // CSDL: thongbao_nguoinhan(idThongBao, idTK, daDoc, thoiGianDoc)
            // Lưu ý: thoiGianDoc tạm để NULL hoặc Time hiện tại (nếu coi như đã gửi)
            // Vì CSDL set NOT NULL ở thoiGianDoc, ta nên set mặc định hoặc cho phép NULL
            // Ở đây tôi set tạm thời gian gửi, khi nào đọc sẽ update lại
            _insert_info($conn, 'thongbao_nguoinhan',
                ['idThongBao', 'idTK', 'daDoc', 'thoiGianDoc'],
                [$id_thong_bao, $id_tk_nhan, 0, date('Y-m-d H:i:s')]
            );
        }
    }

    return ['status' => true, 'message' => 'Đã gửi thông báo'];
}

function danh_dau_da_doc($conn, $id_tk, $id_thong_bao) {
    $conditions = [
        'idThongBao' => ['=', $id_thong_bao, 'AND'],
        'idTK' => ['=', $id_tk, '']
    ];
    
    _update_info($conn, 'thongbao_nguoinhan', 
        ['daDoc', 'thoiGianDoc'], 
        [1, date('Y-m-d H:i:s')], 
        $conditions
    );
}
?>