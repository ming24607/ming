<?php
require_once 'includes/db.php';
if (!isLogged()) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$promptpay_id = '0655530313';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user['id']]);
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, game, amount, result, outcome) VALUES (?, 'ฝากเงิน (QR)', ?, ?, 'win')");
        $stmt->execute([$user['id'], $amount, $amount]);
        header("Location: deposit.php?success=1"); exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? AND game LIKE '%ฝาก%' ORDER BY timestamp DESC LIMIT 10");
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ฝากเงินรวดเร็ว | ชยพลโวย Premium Payment</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; overflow-x: hidden; }

        .payment-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(8, 8, 12, 0.85); border-right: 1px solid rgba(255,255,255,0.05); padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; backdrop-filter: blur(25px); }
        .sidebar-logo { font-size: 3rem; font-weight: 900; margin-bottom: 50px; text-align: center; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px; }
        .nav-link { display: flex; align-items: center; padding: 18px 25px; color: #555; text-decoration: none; border-radius: 20px; transition: 0.4s; font-weight: 700; margin-bottom: 12px; }
        .nav-link i { font-size: 1.4rem; margin-right: 15px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.03); color: var(--primary); }

        .main-content { flex: 1; margin-left: 280px; padding: 60px; display: grid; grid-template-columns: 1fr 400px; gap: 40px; }

        /* Left: Deposit Form */
        .deposit-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(201,163,66,0.15); border-radius: 50px; padding: 60px; backdrop-filter: blur(20px); position: relative; overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,0.5); }
        .deposit-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(201,163,66,0.05) 0%, transparent 70%); z-index: -1; }
        
        .qr-wrapper { background: #fff; padding: 30px; border-radius: 40px; display: inline-block; box-shadow: 0 0 60px rgba(201,163,66,0.2); transition: 0.5s; position: relative; }
        .qr-wrapper img { width: 320px; height: 320px; display: block; border-radius: 10px; }
        .qr-wrapper::after { content: 'SCAN TO PAY'; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: var(--gold-grad); color: #000; padding: 8px 25px; border-radius: 50px; font-weight: 900; font-size: 0.7rem; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }

        .input-group { margin-top: 50px; text-align: center; }
        .amount-input { background: rgba(0,0,0,0.5); border: 2px solid rgba(201,163,66,0.3); border-radius: 30px; padding: 30px; width: 100%; max-width: 400px; font-size: 3rem; font-weight: 900; color: var(--primary); text-align: center; transition: 0.4s; outline: none; box-shadow: inset 0 5px 15px rgba(0,0,0,0.5); }
        .amount-input:focus { border-color: var(--primary); box-shadow: 0 0 30px rgba(201,163,66,0.2); }

        .confirm-btn { margin-top: 30px; background: var(--gold-grad); border: none; padding: 25px 80px; border-radius: 100px; color: #000; font-weight: 900; font-size: 1.4rem; cursor: pointer; transition: 0.4s; box-shadow: 0 20px 40px rgba(201,163,66,0.3); }
        .confirm-btn:hover { transform: scale(1.05); box-shadow: 0 25px 50px rgba(201,163,66,0.4); }

        /* Right: History Sidebar */
        .history-panel { background: rgba(8, 8, 12, 0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 40px; padding: 40px; backdrop-filter: blur(10px); }
        .log-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: rgba(255,255,255,0.02); border-radius: 20px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.03); }
        .log-icon { width: 45px; height: 45px; background: rgba(34, 197, 94, 0.1); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #22c55e; margin-right: 15px; }

        @keyframes successPop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .success-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; display: flex; align-items: center; justify-content: center; text-align: center; animation: fadeIn 0.3s forwards; }
    </style>
</head>
<body>
    <?php if(isset($_GET['success'])): ?>
    <div class="success-overlay">
        <div style="animation: successPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <i class="fa-solid fa-circle-check" style="font-size: 6rem; color: #22c55e; margin-bottom: 30px;"></i>
            <h1 style="font-size: 3rem; font-weight: 900; margin-bottom: 10px;">DEPOSIT SUCCESSFUL</h1>
            <p style="color: #666; font-weight: 700; margin-bottom: 40px;">YOUR BALANCE HAS BEEN UPDATED INSTANTLY</p>
            <a href="dashboard.php" class="confirm-btn" style="text-decoration: none;">CONTINUE TO LOBBY</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="payment-container">
        <div class="sidebar">
            <div class="sidebar-logo"><span>ชยพล</span>โวย</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-layer-group"></i> LOBBY</a>
                <a href="slot.php" class="nav-link"><i class="fa-solid fa-trophy"></i> SLOTS</a>
                <a href="baccarat.php" class="nav-link"><i class="fa-solid fa-diamond"></i> CASINO</a>
                <a href="football.php" class="nav-link"><i class="fa-solid fa-football"></i> SPORTS</a>
                <a href="lotto.php" class="nav-link"><i class="fa-solid fa-ticket"></i> LOTTO</a>
                <a href="deposit.php" class="nav-link active"><i class="fa-solid fa-wallet"></i> DEPOSIT</a>
                <div class="nav-link" style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-money-bill-transfer"></i> WITHDRAW</div>
                <a href="history.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> HISTORY</a>
                <a href="profile.php" class="nav-link"><i class="fa-solid fa-user-gear"></i> PROFILE</a>
            </nav>
        </div>

        <div class="main-content">
            <div class="deposit-card">
                <div style="text-align: center;">
                    <h2 style="font-size: 2.5rem; font-weight: 900; margin: 0; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">INSTANT DEPOSIT</h2>
                    <p style="color: #444; font-weight: 800; letter-spacing: 5px; margin-bottom: 50px;">PROMPTPAY SECURE GATEWAY</p>
                    
                    <div class="qr-wrapper">
                        <img id="pp-qr" src="https://promptpay.io/<?php echo $promptpay_id; ?>.png">
                    </div>

                    <form method="POST" class="input-group">
                        <div style="color: #666; font-weight: 800; margin-bottom: 15px; font-size: 0.8rem; letter-spacing: 2px;">ENTER DEPOSIT AMOUNT (THB)</div>
                        <input type="number" id="deposit-amount" name="amount" class="amount-input" placeholder="0.00" required oninput="updateQR(this.value)" autofocus>
                        <div><button type="submit" name="deposit" class="confirm-btn">CONFIRM DEPOSIT</button></div>
                    </form>
                </div>
            </div>

            <div class="history-panel">
                <h3 style="margin: 0 0 30px; font-weight: 900; letter-spacing: 2px; border-left: 4px solid var(--primary); padding-left: 15px;">RECENT LOGS</h3>
                <?php foreach($history as $h): ?>
                <div class="log-item">
                    <div style="display: flex; align-items: center;">
                        <div class="log-icon"><i class="fa-solid fa-arrow-down-to-bracket"></i></div>
                        <div><div style="font-weight: 800;"><?php echo number_format($h['amount'], 2); ?> ฿</div><div style="font-size: 0.7rem; color: #444;"><?php echo date('d M, H:i', strtotime($h['timestamp'])); ?></div></div>
                    </div>
                    <div style="font-weight: 900; color: #22c55e; font-size: 0.8rem;">SUCCESS</div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($history)) echo "<div style='text-align:center; color:#333; margin-top:50px;'>NO RECENT DEPOSITS</div>"; ?>
            </div>
        </div>
    </div>

    <script>
        const PROMPTPAY_ID = '<?php echo $promptpay_id; ?>';
        function updateQR(amount) {
            const qrImg = document.getElementById('pp-qr');
            if (amount > 0) {
                qrImg.src = `https://promptpay.io/${PROMPTPAY_ID}/${amount}.png`;
            } else {
                qrImg.src = `https://promptpay.io/${PROMPTPAY_ID}.png`;
            }
        }
    </script>
</body>
</html>
