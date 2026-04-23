<?php
require_once 'includes/db.php';
if (!isLogged()) { header("Location: index.php"); exit(); }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? AND (game LIKE '%ฝาก%' OR game LIKE '%ถอน%') ORDER BY timestamp DESC LIMIT 5");
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FRUIT BINGO 5x3 | ชยพลโวย Premium</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --casino-red: #8b0000; --gold: #ffd700; --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; overflow: hidden; }
        
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(8, 8, 12, 0.95); border-right: 1px solid rgba(255,255,255,0.05); padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; backdrop-filter: blur(30px); }
        .sidebar-logo { font-size: 3rem; font-weight: 900; margin-bottom: 50px; text-align: center; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px; }
        .nav-link { display: flex; align-items: center; padding: 18px 25px; color: #555; text-decoration: none; border-radius: 20px; transition: 0.4s; font-weight: 700; margin-bottom: 12px; cursor: pointer; }
        .nav-link i { font-size: 1.4rem; margin-right: 15px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.03); color: var(--primary); }

        .main-content { flex: 1; margin-left: 280px; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at center, #3d0000, #020205); position: relative; }
        
        .balance-box { background: rgba(0,0,0,0.8); border: 2px solid var(--primary); border-radius: 50px; padding: 10px 40px; display: flex; align-items: center; gap: 20px; margin-bottom: 30px; box-shadow: 0 0 50px rgba(201,163,66,0.1); }
        .balance-val { font-family: 'Oswald'; font-size: 2.2rem; font-weight: 700; color: #fff; }

        /* Slot UI */
        .game-frame { position: relative; padding: 20px; background: #000; border: 10px solid #222; border-radius: 40px; box-shadow: 0 40px 100px rgba(0,0,0,1); transform: scale(0.95); }
        
        .reels-container { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; background: #1a1a1a; padding: 10px; border-radius: 20px; position: relative; }
        .reel-col { display: flex; flex-direction: column; gap: 10px; background: #fff; padding: 10px; border-radius: 10px; }
        .slot-cell { width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 4.5rem; transition: 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; z-index: 10; }
        .slot-cell.spinning { animation: reelSpin 0.1s infinite; }
        @keyframes reelSpin { 0% { transform: translateY(-5px); filter: blur(2px); } 100% { transform: translateY(0); filter: blur(2px); } }

        /* Winning Visuals */
        .slot-cell.winner { z-index: 200; transform: scale(1.15); filter: drop-shadow(0 0 15px var(--gold)); background: rgba(255, 215, 0, 0.1); border-radius: 15px; }
        
        .svg-overlay { position: absolute; inset: 0; pointer-events: none; z-index: 150; display: none; }
        .win-path { fill: none; stroke-width: 10; stroke-linecap: round; stroke-linejoin: round; filter: drop-shadow(0 0 10px #000); stroke-dasharray: 2000; stroke-dashoffset: 2000; animation: drawLine 1s forwards, pulseLine 1s infinite alternate; opacity: 0.9; }
        @keyframes drawLine { to { stroke-dashoffset: 0; } }
        @keyframes pulseLine { from { stroke-width: 10; opacity: 0.7; } to { stroke-width: 14; opacity: 1; } }

        /* Bottom Controls */
        .control-panel { margin-top: 30px; display: flex; align-items: center; gap: 20px; background: #111; padding: 20px 40px; border-radius: 30px; border: 2px solid #333; }
        .ctrl-group { display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .ctrl-label { font-size: 0.6rem; font-weight: 900; color: #555; letter-spacing: 2px; }
        .ctrl-val { background: #000; border: 1px solid #444; color: var(--gold); font-family: 'Oswald'; font-size: 1.5rem; padding: 5px 20px; border-radius: 10px; min-width: 80px; text-align: center; }
        
        .btn-ui { background: linear-gradient(to bottom, #444, #111); color: #fff; border: 2px solid #555; border-radius: 10px; padding: 10px 20px; font-weight: 900; cursor: pointer; transition: 0.1s; box-shadow: 0 4px 0 #000; }
        .btn-ui:active { transform: translateY(4px); box-shadow: 0 0 0 #000; }
        .btn-ui.gold { background: var(--gold-grad); color: #000; border-color: #daa520; }
        
        .spin-btn { width: 130px; height: 130px; border-radius: 50%; background: radial-gradient(circle, #5599ff, #0044aa); border: 8px solid #222; box-shadow: 0 10px 0 #002266; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .spin-btn span { font-size: 1.8rem; font-weight: 900; color: #fff; text-shadow: 0 2px 5px #000; }
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.98); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(15px); padding: 20px; }
        .modal-content { background: #08080c; border: 2px solid rgba(201,163,66,0.2); padding: 50px; border-radius: 60px; width: 100%; max-width: 600px; position: relative; }
        .close-btn { position: absolute; top: 30px; right: 30px; font-size: 2rem; color: #444; cursor: pointer; }

        /* Mobile Fixes */
        @media (max-width: 992px) {
            .game-frame { transform: scale(0.65); margin-top: -50px; }
            .control-panel { flex-wrap: wrap; justify-content: center; transform: scale(0.85); gap: 10px; padding: 15px; }
            .sidebar-dim { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1900; backdrop-filter: blur(5px); }
            .sidebar-dim.active { display: block; }
            .main-content { padding: 10px; }
            .sidebar { left: -300px; transition: 0.3s; }
            .sidebar.active { left: 0; }
            .mobile-nav-toggle { display: block; position: fixed; top: 20px; left: 20px; z-index: 2000; background: none; border: none; color: #fff; font-size: 2rem; cursor: pointer; }
        }
        @media (max-width: 500px) {
            .game-frame { transform: scale(0.5); margin-top: -100px; }
            .balance-box { transform: scale(0.8); margin-bottom: 10px; }
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
                <a href="slot.php" class="nav-link active"><i class="fa-solid fa-trophy"></i> SLOTS</a>
                <a href="baccarat.php" class="nav-link"><i class="fa-solid fa-diamond"></i> CASINO</a>
                <a href="football.php" class="nav-link"><i class="fa-solid fa-football"></i> SPORTS</a>
                <a href="lotto.php" class="nav-link"><i class="fa-solid fa-ticket"></i> LOTTO</a>
                <div class="nav-link" onclick="openModal('deposit-modal')"><i class="fa-solid fa-wallet"></i> DEPOSIT</div>
                <div class="nav-link" onclick="openModal('withdraw-modal')"><i class="fa-solid fa-money-bill-transfer"></i> WITHDRAW</div>
                <a href="history.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> HISTORY</a>
                <a href="profile.php" class="nav-link"><i class="fa-solid fa-user-gear"></i> PROFILE</a>
                <a href="logout.php" class="nav-link" style="color: #ef4444; margin-top: 40px;"><i class="fa-solid fa-power-off"></i> LOGOUT</a>
            </nav>
        </div>

        <div class="main-content">
            <div class="balance-box">
                <div style="background: var(--gold-grad); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #000; font-weight: 900;">$</div>
                <div class="balance-val" id="balance-num"><?php echo number_format($user['balance'], 2); ?></div>
            </div>

            <div class="game-frame">
                <div class="win-announcement" id="win-ann">BINGO! BIG WIN</div>
                <div class="reels-container" id="reels-container">
                    <?php for($i=0; $i<5; $i++): ?>
                    <div class="reel-col" id="col-<?php echo $i; ?>">
                        <?php for($j=0; $j<3; $j++) echo "<div class='slot-cell'>💰</div>"; ?>
                    </div>
                    <?php endfor; ?>
                    <svg class="svg-overlay" id="payline-svg" viewBox="0 0 550 330"><path id="line-path" class="win-path" d="" /></svg>
                </div>
            </div>

            <div class="control-panel">
                <div class="ctrl-group"><div class="ctrl-label">LINES</div><div class="ctrl-val" id="lines-display">20</div><button class="btn-ui" onclick="adjustLines()">LINES</button></div>
                <div class="ctrl-group"><div class="ctrl-label">BET</div><div class="ctrl-val">$<span id="bet-display">1.00</span></div><button class="btn-ui" onclick="adjustBet()">BET</button></div>
                <button id="spin-btn" class="spin-btn"><span>SPIN</span></button>
                <div class="ctrl-group"><div class="ctrl-label">TOTAL BET</div><div class="ctrl-val" id="total-bet-display">20.00</div><button class="btn-ui gold" onclick="maxLines()">MAX</button></div>
                <div class="ctrl-group"><div class="ctrl-label">WINNINGS</div><div class="ctrl-val" id="win-display">0.00</div><button class="btn-ui" id="auto-btn" onclick="toggleAuto()">AUTO</button></div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="deposit-modal" class="modal">
        <div class="modal-content">
            <i class="fa-solid fa-times close-btn" onclick="closeModal('deposit-modal')"></i>
            <div style="text-align: center;">
                <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 900; margin-bottom: 10px;">ฝากเงินรวดเร็ว</h2>
                <div style="background: #fff; padding: 20px; border-radius: 35px; display: inline-block; margin: 30px 0;"><img id="pp-qr" src="https://promptpay.io/0655530313.png" style="width: 280px; height: 280px;"></div>
                <form method="POST"><input type="number" name="amount" placeholder="0.00" required oninput="updateQR(this.value)" style="width: 100%; padding: 25px; background: #000; border: 2px solid var(--primary); border-radius: 25px; color: #fff; font-size: 2.2rem; text-align: center; font-weight: 900; outline: none; margin-bottom: 25px;"><button type="submit" name="deposit" style="width: 100%; padding: 20px; background: var(--gold-grad); border: none; border-radius: 25px; color: #000; font-weight: 900; font-size: 1.4rem; cursor: pointer;">ยืนยันการฝากเงิน</button></form>
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

        const symbols = ['⭐', '👑', '🔔', '💰', '🍀', '➖', '7️⃣', '🍉', '🧲'];
        const lineColors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500', '#800080', '#008000', '#c9a342'];
        
        let currentBalance = <?php echo $user['balance']; ?>;
        let activeLines = 20; let betPerLine = 1.0; let isSpinning = false; let isAuto = false;

        const balanceNum = document.getElementById('balance-num');
        const spinBtn = document.getElementById('spin-btn');
        const cells = document.querySelectorAll('.slot-cell');
        const winDisplay = document.getElementById('win-display');
        const linePath = document.getElementById('line-path');
        const svgOverlay = document.getElementById('payline-svg');
        const winAnn = document.getElementById('win-ann');

        function updateUI() { 
            document.getElementById('lines-display').innerText = activeLines; 
            document.getElementById('bet-display').innerText = betPerLine.toFixed(2);
            document.getElementById('total-bet-display').innerText = (activeLines * betPerLine).toFixed(2);
        }
        function adjustLines() { if(isSpinning) return; activeLines = activeLines >= 20 ? 1 : activeLines + 1; updateUI(); }
        function adjustBet() { if(isSpinning) return; betPerLine = betPerLine >= 10 ? 1 : betPerLine + 1; updateUI(); }
        function maxLines() { if(isSpinning) return; activeLines = 20; updateUI(); }
        function toggleAuto() { isAuto = !isAuto; document.getElementById('auto-btn').style.background = isAuto ? 'var(--gold)' : ''; if(isAuto && !isSpinning) startSpin(); }

        spinBtn.addEventListener('click', startSpin);

        async function startSpin() {
            if(isSpinning) return;
            const totalBet = activeLines * betPerLine;
            if(currentBalance < totalBet) { alert("เครดิตไม่พอ!"); isAuto = false; return; }
            
            isSpinning = true; spinBtn.disabled = true; winDisplay.innerText = '0.00'; svgOverlay.style.display = 'none'; winAnn.style.display = 'none';
            cells.forEach(c => c.classList.remove('winner'));
            
            currentBalance -= totalBet; balanceNum.innerText = currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2});
            cells.forEach(c => c.classList.add('spinning'));
            const spinInterval = setInterval(() => { cells.forEach(c => c.innerText = symbols[Math.floor(Math.random()*symbols.length)]); }, 80);

            try {
                const res = await fetch('api/spin.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `bet=${betPerLine}&lines=${activeLines}` });
                const data = await res.json();
                
                setTimeout(() => {
                    clearInterval(spinInterval);
                    cells.forEach(c => c.classList.remove('spinning'));
                    
                    if(data.success) {
                        data.grid.forEach((sym, i) => { const colIdx = i % 5; const rowIdx = Math.floor(i / 5); document.getElementById(`col-${colIdx}`).children[rowIdx].innerText = sym; });
                        currentBalance = parseFloat(data.new_balance); balanceNum.innerText = currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2});
                        
                        if(data.outcome === 'win') {
                            winDisplay.innerText = data.win_amount.toFixed(2);
                            winAnn.innerText = `BINGO! +${data.win_amount.toFixed(2)} ฿`;
                            winAnn.style.display = 'block';
                            showWinLines(data.won_lines);
                        }
                    }
                    isSpinning = false; spinBtn.disabled = false; if(isAuto) setTimeout(startSpin, 1500);
                }, 1200);
            } catch(e) { location.reload(); }
        }

        function showWinLines(lines) {
            if(lines.length === 0) return;
            svgOverlay.style.display = 'block';
            let combinedPath = "";
            lines.forEach((line, idx) => {
                const points = line.pattern.map(idx => { 
                    const col = idx % 5; const row = Math.floor(idx / 5); 
                    const cell = document.getElementById(`col-${col}`).children[row];
                    cell.classList.add('winner'); // Flash winning cells
                    return { x: col * 105 + 55, y: row * 105 + 55 }; 
                });
                combinedPath += `M ${points[0].x} ${points[0].y} ` + points.slice(1).map(p => `L ${p.x} ${p.y}`).join(' ');
            });
            linePath.setAttribute('d', combinedPath);
            linePath.style.stroke = lineColors[Math.floor(Math.random() * lineColors.length)];
        }
    </script>
</body>
</html>
