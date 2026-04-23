<?php
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $password_hashed]);
                $success = "Registration successful! Please login.";
            } catch (PDOException $e) {
                $error = "Username already exists.";
            }
        }
    } elseif (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHAYAPHON WOY | LOGIN & REGISTER</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #c9a342; --gold-grad: linear-gradient(135deg, #c9a342, #f5e0a3, #c9a342); }
        body { background: #020205; color: #fff; margin: 0; font-family: 'Outfit', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .bg-gradient { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e, #050510, #020205); z-index: -1; }
        
        .auth-card { width: 100%; max-width: 480px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 50px; border-radius: 50px; backdrop-filter: blur(40px); box-shadow: 0 40px 100px rgba(0,0,0,0.6); text-align: center; position: relative; }
        .logo { font-size: 3.5rem; font-weight: 900; margin-bottom: 40px; background: var(--gold-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -3px; }
        
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-size: 0.7rem; font-weight: 800; color: #555; letter-spacing: 2px; margin-bottom: 8px; padding-left: 10px; }
        .input-wrapper { position: relative; }
        .input-wrapper i.main-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #444; }
        .input-wrapper i.toggle-eye { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #444; cursor: pointer; transition: 0.3s; }
        .input-wrapper i.toggle-eye:hover { color: var(--primary); }
        
        .input-wrapper input { width: 100%; padding: 18px 50px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; color: #fff; font-size: 1rem; outline: none; transition: 0.3s; box-sizing: border-box; }
        .input-wrapper input:focus { border-color: var(--primary); background: rgba(0,0,0,0.5); }
        
        .btn-submit { background: var(--gold-grad); color: #000; border: none; padding: 20px; border-radius: 20px; font-weight: 900; font-size: 1.2rem; cursor: pointer; transition: 0.4s; width: 100%; margin-top: 15px; }
        .btn-submit:disabled { opacity: 0.3; cursor: not-allowed; }

        .toggle-text { margin-top: 25px; color: #444; font-weight: 700; font-size: 0.9rem; }
        .toggle-text span { color: var(--primary); cursor: pointer; }

        .alert { padding: 15px; border-radius: 15px; margin-bottom: 25px; font-size: 0.9rem; font-weight: 700; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        
        #match-msg { font-size: 0.7rem; margin-top: 5px; padding-left: 10px; font-weight: 700; display: none; }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>

    <div class="auth-card">
        <div class="logo"><span>ชยพล</span>โวย</div>
        
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <!-- Login Form -->
        <form id="login-form" method="POST">
            <h3 style="margin-bottom: 25px; font-weight: 800;">MEMBER LOGIN</h3>
            <div class="input-group">
                <label>USERNAME</label>
                <div class="input-wrapper"><i class="fa-solid fa-user main-icon"></i><input type="text" name="username" placeholder="Username" required></div>
            </div>
            <div class="input-group">
                <label>PASSWORD</label>
                <div class="input-wrapper"><i class="fa-solid fa-lock main-icon"></i><input type="password" name="password" id="login-pass" placeholder="Password" required><i class="fa-solid fa-eye toggle-eye" onclick="togglePass('login-pass', this)"></i></div>
            </div>
            <button type="submit" name="login" class="btn-submit">LOGIN NOW</button>
            <p class="toggle-text">Don't have an account? <span onclick="toggleAuth()">Register</span></p>
        </form>

        <!-- Register Form -->
        <form id="register-form" method="POST" style="display: none;">
            <h3 style="margin-bottom: 25px; font-weight: 800;">NEW ACCOUNT</h3>
            <div class="input-group">
                <label>CHOOSE USERNAME</label>
                <div class="input-wrapper"><i class="fa-solid fa-user-plus main-icon"></i><input type="text" name="username" placeholder="Username" required></div>
            </div>
            <div class="input-group">
                <label>SET PASSWORD</label>
                <div class="input-wrapper"><i class="fa-solid fa-key main-icon"></i><input type="password" name="password" id="reg-pass" placeholder="Password" required oninput="checkMatch()"><i class="fa-solid fa-eye toggle-eye" onclick="togglePass('reg-pass', this)"></i></div>
            </div>
            <div class="input-group">
                <label>CONFIRM PASSWORD</label>
                <div class="input-wrapper"><i class="fa-solid fa-shield-check main-icon"></i><input type="password" name="confirm_password" id="reg-confirm" placeholder="Confirm Password" required oninput="checkMatch()"><i class="fa-solid fa-eye toggle-eye" onclick="togglePass('reg-confirm', this)"></i></div>
                <div id="match-msg"></div>
            </div>
            <button type="submit" name="register" id="reg-btn" class="btn-submit">CREATE ACCOUNT</button>
            <p class="toggle-text">Already a member? <span onclick="toggleAuth()">Back to Login</span></p>
        </form>
    </div>

    <script>
        function toggleAuth() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            loginForm.style.display = loginForm.style.display === 'none' ? 'block' : 'none';
            registerForm.style.display = registerForm.style.display === 'none' ? 'block' : 'none';
        }

        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function checkMatch() {
            const pass = document.getElementById('reg-pass').value;
            const confirm = document.getElementById('reg-confirm').value;
            const msg = document.getElementById('match-msg');
            const btn = document.getElementById('reg-btn');

            if (confirm === "") {
                msg.style.display = 'none';
                btn.disabled = false;
                return;
            }

            msg.style.display = 'block';
            if (pass === confirm) {
                msg.innerText = "✓ Passwords match";
                msg.style.color = "#22c55e";
                btn.disabled = false;
            } else {
                msg.innerText = "✗ Passwords do not match";
                msg.style.color = "#ef4444";
                btn.disabled = true;
            }
        }
    </script>
</body>
</html>
