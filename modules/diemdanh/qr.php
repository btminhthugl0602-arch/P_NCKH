<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_phien = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;
$user_id  = (int)($_SESSION['user_id'] ?? 0);

if (!$id_phien) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

$is_btc = kiem_tra_quyen_he_thong($conn, $user_id, 'event.manage');
if (!$is_btc) {
    die('Không có quyền truy cập.');
}

$phien = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT pd.*, sk.tenSK, sk.idSK
     FROM phien_diemdanh pd
     JOIN sukien sk ON pd.idSK = sk.idSK
     WHERE pd.idPhienDD = $id_phien LIMIT 1"
));
if (!$phien) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

// Token 6 số cố định suốt phiên (seed: idPhienDD + thoiGianMo)
$now     = time();
$secret  = 'NCKH_DD_' . $phien['idSK'];
$moTime  = $phien['thoiGianMo'] ? strtotime($phien['thoiGianMo']) : $now;
$raw     = hash_hmac('sha256', $id_phien . '_' . $moTime, $secret);
$token   = str_pad((string)(hexdec(substr($raw, 0, 8)) % 1000000), 6, '0', STR_PAD_LEFT);

$checkinUrl = _HOST_URL . "/?module=diemdanh&action=checkin&lich=$id_phien";

// Số đã điểm danh
$daDiemDanh = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c FROM diemdanh WHERE idPhienDD=$id_phien AND hienDien=1"
))['c'] ?? 0);

// Tổng số người thuộc sự kiện
$tongNguoi = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT tv.idtk) AS c
     FROM thanhviennhom tv
     JOIN nhom n ON tv.idnhom = n.idnhom
     WHERE n.idSK = {$phien['idSK']} AND n.isActive=1 AND tv.trangthai=1"
))['c'] ?? 0);

$dongTime = $phien['thoiGianDong'] ? strtotime($phien['thoiGianDong']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điểm danh — <?= htmlspecialchars($phien['tenPhien']) ?></title>
    <link href="<?= _HOST_URL_TEMPLATES ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= _HOST_URL_TEMPLATES ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
    :root {
        --bg-deep: #0b1120;
        --bg-card: rgba(255, 255, 255, .05);
        --border: rgba(255, 255, 255, .10);
        --accent: #6366f1;
        --accent-lt: rgba(99, 102, 241, .18);
        --green: #22c55e;
        --green-lt: rgba(34, 197, 94, .15);
        --text: rgba(255, 255, 255, .88);
        --muted: rgba(255, 255, 255, .42);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: var(--bg-deep);
        color: var(--text);
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
    }

    .btn-back {
        position: fixed;
        top: 18px;
        left: 18px;
        padding: 7px 16px;
        border-radius: 50px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        color: var(--muted);
        text-decoration: none;
        font-size: .85rem;
        transition: all .2s;
        backdrop-filter: blur(8px);
    }

    .btn-back:hover {
        color: var(--text);
        border-color: rgba(255, 255, 255, .25);
    }

    .btn-fullscreen {
        position: fixed;
        top: 18px;
        right: 18px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--bg-card);
        border: 1px solid var(--border);
        color: var(--muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .2s;
        backdrop-filter: blur(8px);
    }

    .btn-fullscreen:hover {
        color: var(--text);
    }

    .wrap {
        max-width: 680px;
        width: 100%;
        text-align: center;
    }

    .live-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--green-lt);
        color: var(--green);
        border: 1px solid rgba(34, 197, 94, .3);
        padding: 5px 16px;
        border-radius: 50px;
        font-size: .82rem;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .live-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--green);
        animation: blink 1.4s ease-in-out infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .3
        }
    }

    .event-title {
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .event-sub {
        color: var(--muted);
        font-size: .95rem;
        margin-bottom: 28px;
    }

    .qr-card {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        display: inline-block;
        box-shadow: 0 0 60px rgba(99, 102, 241, .35), 0 0 0 1px var(--border);
        margin-bottom: 28px;
    }

    .token-wrap {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px 28px;
        margin-bottom: 28px;
        backdrop-filter: blur(8px);
    }

    .token-label {
        font-size: .78rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 10px;
    }

    .token-digits {
        font-size: 3.6rem;
        font-weight: 800;
        letter-spacing: .45em;
        background: linear-gradient(135deg, var(--accent), #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        user-select: all;
    }

    .token-sub {
        font-size: .78rem;
        color: var(--muted);
        margin-top: 8px;
    }

    .stats-row {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-bottom: 28px;
    }

    .stat-card {
        flex: 1;
        max-width: 160px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 12px;
        text-align: center;
        backdrop-filter: blur(8px);
    }

    .stat-num {
        font-size: 2.4rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-lbl {
        font-size: .75rem;
        color: var(--muted);
        margin-top: 4px;
    }

    .countdown-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 14px 24px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        backdrop-filter: blur(8px);
    }

    .countdown-label {
        font-size: .82rem;
        color: var(--muted);
    }

    .countdown-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--green);
        min-width: 90px;
    }

    .url-bar {
        font-size: .78rem;
        color: var(--muted);
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 8px 16px;
        display: inline-block;
        word-break: break-all;
    }
    </style>
</head>

<body>

    <a href="<?= _HOST_URL ?>/?module=diemdanh&action=index&id=<?= $phien['idSK'] ?>&lich=<?= $id_phien ?>"
        class="btn-back">
        <i class="bi bi-arrow-left me-1"></i>Bảng điều khiển
    </a>

    <button class="btn-fullscreen" onclick="toggleFS()" title="Toàn màn hình">
        <i class="bi bi-fullscreen" id="fsIcon"></i>
    </button>

    <div class="wrap">

        <div class="live-pill">
            <span class="live-dot"></span>
            ĐIỂM DANH ĐANG MỞ
        </div>

        <h1 class="event-title"><?= htmlspecialchars($phien['tenPhien']) ?></h1>
        <p class="event-sub"><?= htmlspecialchars($phien['tenSK']) ?></p>

        <!-- QR Code -->
        <div class="qr-card">
            <div id="qrcode"></div>
        </div>

        <!-- Token 6 số -->
        <div class="token-wrap">
            <div class="token-label"><i class="bi bi-keyboard me-1"></i>Hoặc nhập mã thủ công</div>
            <div class="token-digits"><?= $token ?></div>
            <div class="token-sub">Mã cố định trong suốt phiên — không thay đổi</div>
        </div>

        <!-- Thống kê realtime -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-num" id="soDaDiemDanh" style="color:var(--green)"><?= $daDiemDanh ?></div>
                <div class="stat-lbl">Đã điểm danh</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" style="color:#f59e0b"><?= $tongNguoi ?></div>
                <div class="stat-lbl">Tổng số</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" id="soConLai" style="color:#94a3b8"><?= max(0, $tongNguoi - $daDiemDanh) ?></div>
                <div class="stat-lbl">Chưa điểm danh</div>
            </div>
        </div>

        <?php if ($dongTime): ?>
        <div class="countdown-card">
            <i class="bi bi-clock" style="color:var(--muted);font-size:1.1rem"></i>
            <div>
                <div class="countdown-label">Đóng điểm danh lúc <?= date('H:i', $dongTime) ?></div>
            </div>
            <div class="countdown-value" id="countdownDong">—</div>
        </div>
        <?php endif; ?>

        <div class="url-bar">
            <i class="bi bi-link-45deg me-1"></i><?= $checkinUrl ?>
        </div>

    </div>

    <script>
    const CHECKIN_URL = <?= json_encode($checkinUrl) ?>;
    const TOKEN = <?= json_encode($token) ?>;
    const ID_PHIEN = <?= $id_phien ?>;
    const TONG_NGUOI = <?= $tongNguoi ?>;
    <?php if ($dongTime): ?>const DONG_TIME = <?= $dongTime ?> * 1000;
    <?php endif; ?>

    new QRCode(document.getElementById('qrcode'), {
        text: CHECKIN_URL + '&t=' + TOKEN,
        width: 220,
        height: 220,
        colorDark: '#0b1120',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    <?php if ($dongTime): ?>

    function tickDong() {
        const d = Math.max(0, DONG_TIME - Date.now());
        const el = document.getElementById('countdownDong');
        if (!el) return;
        if (d <= 0) {
            el.textContent = 'Đã đóng!';
            el.style.color = '#ef4444';
            setTimeout(() => location.reload(), 1500);
            return;
        }
        const m = Math.floor(d / 60000),
            s = Math.floor((d % 60000) / 1000);
        el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        el.style.color = d < 60000 ? '#ef4444' : d < 300000 ? '#f59e0b' : '#22c55e';
    }
    tickDong();
    setInterval(tickDong, 1000);
    <?php endif; ?>

    // Realtime count mỗi 8 giây
    setInterval(function() {
        fetch('<?= _HOST_URL ?>/?module=diemdanh&action=count_ajax&lich=' + ID_PHIEN)
            .then(r => r.json())
            .then(d => {
                if (d.count !== undefined) {
                    document.getElementById('soDaDiemDanh').textContent = d.count;
                    document.getElementById('soConLai').textContent = Math.max(0, TONG_NGUOI - d.count);
                }
            }).catch(() => {});
    }, 8000);

    function toggleFS() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            document.getElementById('fsIcon').className = 'bi bi-fullscreen-exit';
        } else {
            document.exitFullscreen();
            document.getElementById('fsIcon').className = 'bi bi-fullscreen';
        }
    }
    </script>

</body>

</html>