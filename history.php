<?php
require_once 'includes/db.php';
if (!isLogged()) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deposit'])) {
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user['id']]);
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, game, amount, result, outcome) VALUES (?, 'ฝากเงิน (QR)', ?, ?, 'win')");
            $stmt->execute([$user['id'], $amount, $amount]);
            header("Location: history.php"); exit();
        }
    } elseif (isset($_POST['withdraw'])) {
        $amount = floatval($_POST['amount']);
        if ($amount > 0 && $user['balance'] >= $amount) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user['id']]);
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, game, amount, result, outcome) VALUES (?, 'ถอนเงิน (ธนาคาร)', ?, 0, 'loss')");
            $stmt->execute([$user['id'], $amount]);
            header("Location: history.php"); exit();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? AND (game LIKE '%ฝาก%' OR game LIKE '%ถอน%') ORDER BY timestamp DESC");
$stmt->execute([$user['id']]);
$logs = $stmt->fetchAll();

$history = array_slice($logs, 0, 5); // For modals
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการทำรายการ | ชยพลโวย Premium</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(8, 8, 12, 0.85); border-right: 1px solid rgba(255,255,255,0.05); padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; backdrop-filter: blur(25px); }
        .sidebar-logo { font-size: 3rem; font-weight: 900; margin-bottom: 50px; text-align: center; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px; }
        .nav-link { display: flex; align-items: center; padding: 18px 25px; color: #555; text-decoration: none; border-radius: 20px; transition: 0.4s; font-weight: 700; margin-bottom: 12px; cursor: pointer; }
        .nav-link i { font-size: 1.4rem; margin-right: 15px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.03); color: var(--primary); }
        .main-panel { flex: 1; margin-left: 280px; padding: 60px; }
        .history-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 30px; padding: 40px; backdrop-filter: blur(10px); }
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; padding: 20px; color: #444; font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .log-table td { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.02); }

        /* Beautiful Popup Modals */
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.98); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(15px); padding: 20px; }
        .modal-content { background: #08080c; border: 2px solid rgba(201,163,66,0.2); padding: 50px; border-radius: 60px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 0 100px rgba(201,163,66,0.1); }
        .close-btn { position: absolute; top: 30px; right: 30px; font-size: 2rem; color: #444; cursor: pointer; }
        .qr-frame { background: #fff; padding: 20px; border-radius: 35px; display: inline-block; box-shadow: 0 0 50px rgba(201,163,66,0.2); margin-bottom: 30px; }
        .qr-frame img { width: 280px; height: 280px; display: block; border-radius: 10px; }
        .mini-history { margin-top: 40px; background: rgba(255,255,255,0.02); border-radius: 30px; padding: 25px; }
        .mini-history table { width: 100%; border-collapse: collapse; }
        .mini-history td { padding: 12px 0; border-top: 1px solid rgba(255,255,255,0.03); font-size: 0.9rem; }
        /* Mobile Fixes */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; padding-top: 80px; }
            .mobile-nav-toggle { display: block; position: fixed; top: 20px; left: 20px; z-index: 2000; background: var(--gold-grad); border: none; width: 45px; height: 45px; border-radius: 10px; color: #000; font-size: 1.2rem; cursor: pointer; }
            .sidebar-dim { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1900; backdrop-filter: blur(5px); }
            .sidebar-dim.active { display: block; }
            .glass-box { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <button class="mobile-nav-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    <div class="sidebar-dim" id="sidebar-dim" onclick="toggleSidebar()"></div>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-logo"><span>ชยพล</span>โวย</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-layer-group"></i> LOBBY</a>
                <a href="slot.php" class="nav-link"><i class="fa-solid fa-trophy"></i> SLOTS</a>
                <a href="baccarat.php" class="nav-link"><i class="fa-solid fa-diamond"></i> CASINO</a>
                <a href="football.php" class="nav-link"><i class="fa-solid fa-football"></i> SPORTS</a>
                <a href="lotto.php" class="nav-link"><i class="fa-solid fa-ticket"></i> LOTTO</a>
                <div class="nav-link" onclick="openModal('deposit-modal')"><i class="fa-solid fa-wallet"></i> DEPOSIT</div>
                <div class="nav-link" onclick="openModal('withdraw-modal')"><i class="fa-solid fa-money-bill-transfer"></i> WITHDRAW</div>
                <a href="history.php" class="nav-link active"><i class="fa-solid fa-clock-rotate-left"></i> HISTORY</a>
                <a href="profile.php" class="nav-link"><i class="fa-solid fa-user-gear"></i> PROFILE</a>
                <div style="margin-top: 40px; padding: 25px; border-radius: 30px; background: rgba(201,163,66,0.1); border: 1px solid rgba(201,163,66,0.2);"><div style="font-weight: 900; color: #fff;">💎 PLATINUM VIP</div></div>
                <a href="logout.php" class="nav-link" style="color: #ef4444; margin-top: 40px;"><i class="fa-solid fa-power-off"></i> LOGOUT</a>
            </nav>
        </div>

        <div class="main-panel">
            <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 40px;">TRANSACTION HISTORY</h1>
            <div class="history-card">
                <table class="log-table">
                    <thead><tr><th>DATE/TIME</th><th>TYPE</th><th>AMOUNT</th><th>STATUS</th></tr></thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td style="color: #888;"><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                            <td style="font-weight: 700;"><?php echo $log['game']; ?></td>
                            <td style="font-size: 1.2rem; font-weight: 800; color: var(--primary);"><?php echo number_format($log['amount'], 2); ?> ฿</td>
                            <td><span style="color: #22c55e; font-weight: 900;">SUCCESS</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)) echo "<tr><td colspan='4' style='text-align:center; padding: 50px; color: #444;'>No transactions found.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Beautified Deposit Modal (Popup) -->
    <div id="deposit-modal" class="modal">
        <div class="modal-content">
            <i class="fa-solid fa-times close-btn" onclick="closeModal('deposit-modal')"></i>
            <div style="text-align: center;">
                <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 900; margin-bottom: 10px;">ฝากเงินรวดเร็ว</h2>
                <p style="color: #444; font-weight: 800; letter-spacing: 5px; margin-bottom: 40px;">PROMPTPAY INSTANT</p>
                <div class="qr-frame"><img id="pp-qr" src="https://promptpay.io/0655530313.png"></div>
                <form method="POST">
                    <div style="color: #666; font-weight: 800; margin-bottom: 15px; font-size: 0.8rem; letter-spacing: 2px;">ระบุจำนวนเงินที่ต้องการฝาก</div>
                    <input type="number" name="amount" placeholder="0.00" required oninput="updateQR(this.value)" style="width: 100%; padding: 25px; background: #000; border: 2px solid var(--primary); border-radius: 25px; color: #fff; font-size: 2.2rem; text-align: center; font-weight: 900; outline: none; margin-bottom: 25px;">
                    <button type="submit" name="deposit" style="width: 100%; padding: 20px; background: var(--gold-grad); border: none; border-radius: 25px; color: #000; font-weight: 900; font-size: 1.4rem; cursor: pointer; box-shadow: 0 15px 30px rgba(201,163,66,0.2);">ยืนยันการฝากเงิน</button>
                </form>
            </div>
            <div class="mini-history">
                <h4 style="margin: 0 0 15px; color: var(--primary); font-weight: 900; letter-spacing: 2px;">RECENT DEPOSITS</h4>
                <table><tbody><?php foreach($history as $h): if(strpos($h['game'], 'ฝาก') !== false): ?><tr><td><?php echo date('H:i', strtotime($h['timestamp'])); ?></td><td style="font-weight: 800;">+ <?php echo number_format($h['amount'], 2); ?> ฿</td><td style="color: #22c55e; font-weight: 900;">SUCCESS</td></tr><?php endif; endforeach; ?></tbody></table>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdraw-modal" class="modal">
        <div class="modal-content" style="border-color: #ef4444;">
            <i class="fa-solid fa-times close-btn" onclick="closeModal('withdraw-modal')"></i>
            <h2 style="color: #ef4444; font-size: 2.2rem; font-weight: 900; margin-bottom: 30px; text-align: center;">ถอนเงินเข้าบัญชี</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div><select name="bank_name" style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #222; border-radius: 15px;"><option>กสิกรไทย</option><option>ไทยพาณิชย์</option></select></div>
                    <div><input type="text" name="acc_num" placeholder="เลขบัญชี..." required style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #222; border-radius: 15px;"></div>
                </div>
                <input type="text" name="acc_name" placeholder="ชื่อจริง-นามสกุล..." required style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #222; border-radius: 15px; margin-bottom: 25px;">
                <input type="number" name="amount" placeholder="0.00" required style="width: 100%; padding: 22px; background: #000; color: #fff; border: 2px solid #ef4444; border-radius: 20px; font-size: 1.8rem; text-align: center; font-weight: 900;">
                <button type="submit" name="withdraw" style="width: 100%; padding: 20px; background: #ef4444; border: none; border-radius: 20px; color: #fff; font-weight: 900; font-size: 1.4rem; cursor: pointer; margin-top: 25px;">ยืนยันการถอน</button>
            </form>
            <div class="mini-history">
                <h4 style="margin: 0 0 15px; color: #ef4444;">RECENT WITHDRAWALS</h4>
                <table><tbody><?php foreach($history as $h): if(strpos($h['game'], 'ถอน') !== false): ?><tr><td><?php echo date('H:i', strtotime($h['timestamp'])); ?></td><td style="font-weight: 800;">- <?php echo number_format($h['amount'], 2); ?> ฿</td><td style="color: #ef4444;">SUCCESS</td></tr><?php endif; endforeach; ?></tbody></table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.getElementById('sidebar-dim').classList.toggle('active');
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            if (window.innerWidth <= 992) toggleSidebar();
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.className === 'modal') { event.target.style.display = 'none'; } }

        const PROMPTPAY_ID = '0655530313'; 
        function updateQR(amount) {
            const qrImg = document.getElementById('pp-qr');
            if (amount > 0) { qrImg.src = `https://promptpay.io/${PROMPTPAY_ID}/${amount}.png`; }
            else { qrImg.src = `https://promptpay.io/${PROMPTPAY_ID}.png`; }
        }
    </script>
</body>
</html>
