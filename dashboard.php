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
            header("Location: dashboard.php"); exit();
        }
    } elseif (isset($_POST['withdraw'])) {
        $amount = floatval($_POST['amount']);
        if ($amount > 0 && $user['balance'] >= $amount) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user['id']]);
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, game, amount, result, outcome) VALUES (?, 'ถอนเงิน (ธนาคาร)', ?, 0, 'loss')");
            $stmt->execute([$user['id'], $amount]);
            header("Location: dashboard.php"); exit();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? AND (game LIKE '%ฝาก%' OR game LIKE '%ถอน%') ORDER BY timestamp DESC LIMIT 5");
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHAYAPHON WOY | Ultra-Premium Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); --dark-felt: #051a0d; }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; overflow-x: hidden; }

        /* Sidebar Refinement */
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(8, 8, 12, 0.9); border-right: 1px solid rgba(255,255,255,0.05); padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; backdrop-filter: blur(30px); }
        .sidebar-logo { font-size: 3rem; font-weight: 900; margin-bottom: 50px; text-align: center; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px; text-shadow: 0 10px 20px rgba(201,163,66,0.2); }
        .nav-link { display: flex; align-items: center; padding: 18px 25px; color: #555; text-decoration: none; border-radius: 20px; transition: 0.4s; font-weight: 700; margin-bottom: 12px; cursor: pointer; }
        .nav-link i { font-size: 1.4rem; margin-right: 15px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.03); color: var(--primary); transform: translateX(10px); }

        /* Main Content & Hero */
        .main-content { flex: 1; margin-left: 280px; padding: 40px 60px; position: relative; background: radial-gradient(circle at top right, #1a1a2e, #020205); }
        
        .hero-banner { background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://img.freepik.com/free-photo/view-casino-elements-with-cards-chips_23-2148911579.jpg') center/cover; height: 350px; border-radius: 50px; position: relative; display: flex; flex-direction: column; justify-content: center; padding: 60px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .hero-banner::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, #020205, transparent); }
        .hero-content { position: relative; z-index: 10; }
        .jackpot-box { background: rgba(0,0,0,0.8); border: 2px solid var(--primary); padding: 10px 30px; border-radius: 20px; display: inline-block; margin-top: 20px; box-shadow: 0 0 30px rgba(201,163,66,0.2); }
        .jackpot-num { font-size: 3.5rem; font-weight: 900; color: var(--primary); font-family: monospace; letter-spacing: 5px; }

        /* Stats Bar */
        .stats-bar { display: flex; gap: 30px; margin: 40px 0; }
        .stat-card { flex: 1; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 30px; display: flex; align-items: center; gap: 20px; backdrop-filter: blur(10px); }
        .stat-icon { width: 60px; height: 60px; border-radius: 20px; background: rgba(201,163,66,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--primary); }

        /* Game Cards - 3D Perspective */
        .game-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; perspective: 1000px; }
        .game-card { background: rgba(10, 10, 15, 0.9); border: 1px solid rgba(255,255,255,0.05); border-radius: 40px; overflow: hidden; transition: 0.6s cubic-bezier(0.23, 1, 0.32, 1); cursor: pointer; position: relative; }
        .game-card img { width: 100%; height: 260px; object-fit: cover; filter: brightness(0.6); transition: 0.5s; }
        .game-card:hover { transform: translateY(-20px) rotateX(5deg); border-color: var(--primary); box-shadow: 0 40px 80px rgba(0,0,0,0.8), 0 0 20px rgba(201,163,66,0.1); }
        .game-card:hover img { filter: brightness(1); transform: scale(1.1); }
        .game-badge { position: absolute; top: 20px; right: 20px; background: var(--gold-grad); color: #000; padding: 5px 15px; border-radius: 10px; font-weight: 900; font-size: 0.7rem; }

        /* Modals */
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.98); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(20px); padding: 20px; }
        .modal-content { background: #08080c; border: 2px solid rgba(201,163,66,0.2); padding: 50px; border-radius: 60px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 0 100px rgba(201,163,66,0.1); }
        .close-btn { position: absolute; top: 30px; right: 30px; font-size: 2rem; color: #444; cursor: pointer; }
        /* Mobile Fixes */
        @media (max-width: 992px) {
            .hero-content h1 { font-size: 3rem; }
            .hero-content p { font-size: 1rem; }
            .game-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }
            .sidebar-dim { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1900; backdrop-filter: blur(5px); }
            .sidebar-dim.active { display: block; }
        }
    </style>
</head>
<body>
    <button class="mobile-nav-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    <div class="sidebar-dim" id="sidebar-dim" onclick="toggleSidebar()"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo"><span>ชยพล</span>โวย</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-layer-group"></i> LOBBY</a>
                <a href="slot.php" class="nav-link"><i class="fa-solid fa-trophy"></i> SLOTS</a>
                <a href="baccarat.php" class="nav-link"><i class="fa-solid fa-diamond"></i> CASINO</a>
                <a href="football.php" class="nav-link"><i class="fa-solid fa-football"></i> SPORTS</a>
                <a href="lotto.php" class="nav-link"><i class="fa-solid fa-ticket"></i> LOTTO</a>
                <div class="nav-link" onclick="openModal('deposit-modal')"><i class="fa-solid fa-wallet"></i> DEPOSIT</div>
                <div class="nav-link" onclick="openModal('withdraw-modal')"><i class="fa-solid fa-money-bill-transfer"></i> WITHDRAW</div>
                <a href="history.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> HISTORY</a>
                <a href="profile.php" class="nav-link"><i class="fa-solid fa-user-gear"></i> PROFILE</a>
                <div style="margin-top: 40px; padding: 25px; border-radius: 30px; background: rgba(201,163,66,0.05); border: 1px solid rgba(201,163,66,0.1);"><div style="font-weight: 900; color: #fff;">💎 PLATINUM VIP</div><div style="font-size: 0.7rem; color: #666; margin-top: 5px;">EXCLUSIVE ACCESS ENABLED</div></div>
                <a href="logout.php" class="nav-link" style="color: #ef4444; margin-top: 40px;"><i class="fa-solid fa-power-off"></i> LOGOUT</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
                <div>
                    <h1 style="font-size: 3.5rem; font-weight: 900; margin: 0; letter-spacing: -3px;">WELCOME BACK, <?php echo strtoupper($user['username']); ?></h1>
                    <p style="color: #444; font-weight: 800; letter-spacing: 5px;">THE ULTIMATE GAMING EXPERIENCE</p>
                </div>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--primary); padding: 15px 45px; border-radius: 100px; text-align: center; box-shadow: 0 10px 30px rgba(201,163,66,0.1);">
                    <div style="font-size: 0.7rem; color: #888; font-weight: 800; letter-spacing: 2px;">AVAILABLE BALANCE</div>
                    <div style="font-size: 2.8rem; font-weight: 900; color: var(--primary);"><?php echo number_format($user['balance'], 2); ?> <span style="font-size: 1.2rem;">฿</span></div>
                </div>
            </header>

            <!-- Hero Banner -->
            <div class="hero-banner">
                <div class="hero-content">
                    <h2 style="font-size: 2.5rem; font-weight: 900; margin: 0;">GLOBAL PROGRESSIVE</h2>
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary); letter-spacing: 5px;">MEGA JACKPOT</h3>
                    <div class="jackpot-box"><span class="jackpot-num" id="jackpot-counter">12,548,201</span></div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-users"></i></div><div><div style="font-weight: 900; font-size: 1.5rem;">2,548</div><div style="color: #444; font-size: 0.7rem; font-weight: 800;">ACTIVE PLAYERS</div></div></div>
                <div class="stat-card"><div class="stat-icon" style="color: #22c55e; background: rgba(34,197,94,0.1);"><i class="fa-solid fa-bolt"></i></div><div><div style="font-weight: 900; font-size: 1.5rem;">INSTANT</div><div style="color: #444; font-size: 0.7rem; font-weight: 800;">AUTO WITHDRAW</div></div></div>
                <div class="stat-card"><div class="stat-icon" style="color: #3b82f6; background: rgba(59,130,246,0.1);"><i class="fa-solid fa-shield-halved"></i></div><div><div style="font-weight: 900; font-size: 1.5rem;">SECURED</div><div style="color: #444; font-size: 0.7rem; font-weight: 800;">SSL PROTECTED</div></div></div>
            </div>

            <!-- Game Grid -->
            <div class="game-grid">
                <a href="slot.php" class="game-card">
                    <div class="game-badge">HOT</div>
                    <img src="https://img.freepik.com/free-vector/gradient-slot-machine-illustration_23-2148942544.jpg">
                    <div style="padding: 30px;"><h4 style="margin: 0; font-size: 1.8rem; font-weight: 900;">PRAGMATIC SLOTS</h4><p style="color: #555; margin: 10px 0 0; font-weight: 700;">WIN BIG WITH 99.9% RTP</p></div>
                </a>
                <a href="baccarat.php" class="game-card">
                    <div class="game-badge" style="background: #ef4444; color: #fff;">LIVE</div>
                    <img src="https://img.freepik.com/free-photo/view-casino-elements-with-cards-chips_23-2148911579.jpg">
                    <div style="padding: 30px;"><h4 style="margin: 0; font-size: 1.8rem; font-weight: 900;">EVOLUTION CASINO</h4><p style="color: #555; margin: 10px 0 0; font-weight: 700;">REAL DEALERS, REAL LUXURY</p></div>
                </a>
                <a href="football.php" class="game-card">
                    <img src="https://img.freepik.com/free-photo/soccer-players-action-professional-stadium_1150-14562.jpg">
                    <div style="padding: 30px;"><h4 style="margin: 0; font-size: 1.8rem; font-weight: 900;">SPORTS BOOK</h4><p style="color: #555; margin: 10px 0 0; font-weight: 700;">GLOBAL ODDS & LIVE BETTING</p></div>
                </a>
                <a href="lotto.php" class="game-card">
                    <img src="https://img.freepik.com/free-vector/lottery-balls-falling-from-dispenser-machine_52683-47913.jpg">
                    <div style="padding: 30px;"><h4 style="margin: 0; font-size: 1.8rem; font-weight: 900;">PREMIUM HUAY</h4><p style="color: #555; margin: 10px 0 0; font-weight: 700;">X900 PAYOUT GUARANTEED</p></div>
                </a>
            </div>
        </div>
    </div>

    <!-- Modals (Beautified) -->
    <div id="deposit-modal" class="modal">
        <div class="modal-content">
            <i class="fa-solid fa-times close-btn" onclick="closeModal('deposit-modal')"></i>
            <div style="text-align: center;">
                <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 900; margin-bottom: 10px;">ฝากเงินรวดเร็ว</h2>
                <div style="background: #fff; padding: 20px; border-radius: 35px; display: inline-block; box-shadow: 0 0 50px rgba(201,163,66,0.2); margin: 30px 0;"><img id="pp-qr" src="https://promptpay.io/0655530313.png" style="width: 280px; height: 280px; display: block; border-radius: 10px;"></div>
                <form method="POST">
                    <input type="number" name="amount" placeholder="ระบุจำนวนเงิน..." required oninput="updateQR(this.value)" style="width: 100%; padding: 25px; background: #000; border: 2px solid var(--primary); border-radius: 25px; color: #fff; font-size: 2.2rem; text-align: center; font-weight: 900; outline: none; margin-bottom: 25px;">
                    <button type="submit" name="deposit" style="width: 100%; padding: 20px; background: var(--gold-grad); border: none; border-radius: 25px; color: #000; font-weight: 900; font-size: 1.4rem; cursor: pointer; box-shadow: 0 15px 30px rgba(201,163,66,0.2);">ยืนยันการฝากเงิน</button>
                </form>
            </div>
            <div style="margin-top: 40px; background: rgba(255,255,255,0.02); border-radius: 30px; padding: 25px;">
                <h4 style="margin: 0 0 15px; color: var(--primary); font-weight: 900; letter-spacing: 2px;">RECENT DEPOSITS</h4>
                <table style="width: 100%; border-collapse: collapse;"><tbody><?php foreach($history as $h): if(strpos($h['game'], 'ฝาก') !== false): ?><tr><td style="padding: 12px 0; border-top: 1px solid rgba(255,255,255,0.03); font-size: 0.9rem;"><?php echo date('H:i', strtotime($h['timestamp'])); ?></td><td style="font-weight: 800;">+ <?php echo number_format($h['amount'], 2); ?> ฿</td><td style="color: #22c55e; font-weight: 900;">SUCCESS</td></tr><?php endif; endforeach; ?></tbody></table>
            </div>
        </div>
    </div>

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

        // Jackpot Counter Animation
        let jackpot = 12548201;
        setInterval(() => {
            jackpot += Math.floor(Math.random() * 100);
            document.getElementById('jackpot-counter').innerText = jackpot.toLocaleString();
        }, 2000);
    </script>
</body>
</html>
