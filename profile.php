<?php
require_once 'includes/db.php';
if (!isLogged()) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_password'])) {
        $new_pass = $_POST['new_password'];
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new_pass, PASSWORD_DEFAULT), $user['id']]);
        $message = "เปลี่ยนรหัสผ่านเรียบร้อย!";
    }
    // Deposit/Withdraw logic would go here if not handled globally, 
    // but the dashboard.php logic is usually enough if they are redirects.
    // For now, let's keep it simple.
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าโปรไฟล์ | ชยพลโวย Premium</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(8, 8, 12, 0.8); border-right: 1px solid rgba(255,255,255,0.05); padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; backdrop-filter: blur(20px); }
        .sidebar-logo { font-size: 3rem; font-weight: 900; margin-bottom: 50px; text-align: center; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px; }
        .nav-link { display: flex; align-items: center; padding: 18px 25px; color: #555; text-decoration: none; border-radius: 20px; transition: 0.4s; font-weight: 700; margin-bottom: 12px; }
        .nav-link i { font-size: 1.4rem; margin-right: 15px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.03); color: var(--primary); }

        .main-panel { flex: 1; margin-left: 280px; padding: 60px; display: flex; flex-direction: column; align-items: center; }
        .profile-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 40px; padding: 60px; width: 600px; backdrop-filter: blur(15px); text-align: center; }
        .avatar-circle { width: 120px; height: 120px; background: var(--gold-grad); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; color: #000; margin: 0 auto 30px; font-weight: 900; }
        .input-box { width: 100%; padding: 20px; background: #000; border: 1px solid #222; border-radius: 15px; color: #fff; font-size: 1.1rem; margin-bottom: 25px; outline: none; transition: 0.3s; }
        .input-box:focus { border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-logo"><span>ชยพล</span>โวย</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-layer-group"></i> LOBBY</a>
                <a href="slot.php" class="nav-link"><i class="fa-solid fa-trophy"></i> SLOTS</a>
                <a href="baccarat.php" class="nav-link"><i class="fa-solid fa-diamond"></i> CASINO</a>
                <a href="football.php" class="nav-link"><i class="fa-solid fa-football"></i> SPORTS</a>
                <a href="lotto.php" class="nav-link"><i class="fa-solid fa-ticket"></i> LOTTO</a>
                <a href="#" class="nav-link" onclick="openModal('deposit-modal')"><i class="fa-solid fa-wallet"></i> DEPOSIT</a>
                <a href="#" class="nav-link" onclick="openModal('withdraw-modal')"><i class="fa-solid fa-money-bill-transfer"></i> WITHDRAW</a>
                <a href="history.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> HISTORY</a>
                <a href="profile.php" class="nav-link active"><i class="fa-solid fa-user-gear"></i> PROFILE</a>
                <div style="margin-top: 60px; padding: 25px; border-radius: 30px; background: rgba(201,163,66,0.1); border: 1px solid rgba(201,163,66,0.2);">
                    <div style="font-size: 0.7rem; color: #888; letter-spacing: 2px; margin-bottom: 5px;">ACCOUNT STATUS</div>
                    <div style="font-weight: 900; color: #fff;">💎 PLATINUM VIP</div>
                </div>
                <a href="logout.php" class="nav-link" style="color: #ef4444; margin-top: 40px;"><i class="fa-solid fa-power-off"></i> LOGOUT</a>
            </nav>
        </div>

        <div class="main-panel">
            <?php if($message): ?>
                <div style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 20px 40px; border-radius: 20px; margin-bottom: 30px; font-weight: 700; border: 1px solid #22c55e;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="avatar-circle"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <h2 style="font-size: 2rem; font-weight: 900; margin-bottom: 10px;"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p style="color: #666; margin-bottom: 50px;">Member since: <?php echo date('F Y'); ?></p>

                <form method="POST">
                    <div style="text-align: left; margin-bottom: 40px;">
                        <label style="color: #444; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 15px;">Change Password</label>
                        <input type="password" name="new_password" class="input-box" placeholder="ระบุรหัสผ่านใหม่..." required>
                        <button type="submit" name="update_password" style="width: 100%; padding: 20px; background: var(--gold-grad); border: none; border-radius: 15px; color: #000; font-weight: 900; font-size: 1.1rem; cursor: pointer; transition: 0.3s;">UPDATE SETTINGS</button>
                    </div>
                </form>

                <div style="padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 20px;">
                        <div style="color: #444; font-size: 0.7rem; font-weight: 800;">TOTAL DEPOSITS</div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #22c55e;">15,400.00 ฿</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 20px;">
                        <div style="color: #444; font-size: 0.7rem; font-weight: 800;">TOTAL WITHDRAWS</div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #ef4444;">8,200.00 ฿</div>
                    </div>
                </div>
            </div>
            <a href="dashboard.php" style="margin-top: 40px; color: #444; text-decoration: none; font-weight: 700;"><i class="fa-solid fa-arrow-left"></i> BACK TO LOBBY</a>
        </div>
    </div>

    <!-- Modals -->
    <div id="deposit-modal" class="modal">
        <div class="modal-content" style="background: #08080c; border: 2px solid var(--primary); padding: 50px; text-align: center; border-radius: 50px; width: 500px;">
            <i class="fa-solid fa-times close-modal" onclick="closeModal('deposit-modal')" style="font-size: 2rem; cursor: pointer; float: right; color: #555;"></i>
            <h2 style="color: var(--primary); font-size: 2.2rem; font-weight: 900; margin-bottom: 30px;">ฝากเงินรวดเร็ว</h2>
            <div style="background: #fff; padding: 25px; border-radius: 40px; display: inline-block; margin-bottom: 30px;"><img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=EduBet-Pay-Lobby" style="width: 250px; height: 250px;"></div>
            <form method="POST"><input type="number" name="amount" placeholder="ระบุจำนวนเงิน..." style="width: 100%; padding: 22px; background: #000; border: 1px solid #333; border-radius: 20px; color: #fff; font-size: 1.6rem; margin: 25px 0; text-align: center; font-weight: 900;"><button type="submit" name="deposit" style="width: 100%; padding: 20px; background: var(--primary); border: none; border-radius: 25px; color: #000; font-weight: 900; font-size: 1.4rem; cursor: pointer;">ยืนยันการฝากเงิน</button></form>
        </div>
    </div>
    <div id="withdraw-modal" class="modal">
        <div class="modal-content" style="background: #08080c; border: 2px solid #ef4444; padding: 50px; border-radius: 50px; width: 550px;">
            <i class="fa-solid fa-times close-modal" onclick="closeModal('withdraw-modal')" style="font-size: 2rem; cursor: pointer; float: right; color: #555;"></i>
            <h2 style="color: #ef4444; font-size: 2.2rem; font-weight: 900; margin-bottom: 30px;">ถอนเงินเข้าบัญชี</h2>
            <form method="POST"><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;"><div><select name="bank_name" style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #333; border-radius: 15px;"><option>กสิกรไทย</option><option>ไทยพาณิชย์</option></select></div><div><input type="text" name="acc_num" placeholder="เลขบัญชี..." style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #333; border-radius: 15px;"></div></div><input type="text" name="acc_name" placeholder="ชื่อบัญชี..." style="width: 100%; padding: 18px; background: #000; color: #fff; border: 1px solid #333; border-radius: 15px; margin-bottom: 20px;"><input type="number" name="amount" placeholder="จำนวนเงิน..." style="width: 100%; padding: 22px; background: #000; color: #fff; border: 1px solid #333; border-radius: 20px; font-size: 1.6rem; text-align: center; font-weight: 900; margin-bottom: 30px;"><button type="submit" name="withdraw" style="width: 100%; padding: 20px; background: #ef4444; color: #fff; border: none; border-radius: 25px; font-weight: 900; font-size: 1.4rem; cursor: pointer;">ถอนเงินทันที</button></form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.className === 'modal') { event.target.style.display = 'none'; } }
    </script>
</body>
</html>
