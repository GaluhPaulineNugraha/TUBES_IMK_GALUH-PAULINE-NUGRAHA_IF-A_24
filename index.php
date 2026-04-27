<?php
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM kasir WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $kasir = $stmt->fetch();
    
    if ($kasir) {
        $_SESSION['kasir_id'] = $kasir['id'];
        $_SESSION['kasir_nama'] = $kasir['nama'];
        $_SESSION['kasir_username'] = $kasir['username'];
        header('Location: analisis.php');
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto Serba Serbi - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #2A4B2F; display: flex; min-height: 100vh; }
        .login-wrapper { display: flex; width: 100%; min-height: 100vh; }
        .login-left { flex: 1; background: #FFFFFF; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .logo-container { text-align: center; }
        .resto-name { 
            font-size: 32px; 
            font-weight: 800; 
            color: #2A4B2F; 
            margin-bottom: 30px;
            margin-top: 50px;
        }
        .main-logo { 
            width: 350px; 
            height: 350px; 
            object-fit: contain;
            max-width: 100%;
        }
        .login-right { flex: 1; background: #2A4B2F; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .login-card { width: 100%; max-width: 400px; }
        .welcome-text { font-size: 28px; font-weight: 700; color: white; text-align: center; margin-bottom: 8px; }
        .login-subtext { color: rgba(255,255,255,0.7); text-align: center; margin-bottom: 35px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: white; font-size: 14px; }
        .input-group label i { margin-right: 8px; color: #DAA900; }
        .input-group input { width: 100%; padding: 14px 16px; border: none; border-radius: 12px; font-size: 14px; background: white; }
        .input-group input:focus { outline: none; box-shadow: 0 0 0 3px rgba(218,169,0,0.3); }
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .password-wrapper input {
            width: 100%;
            padding: 14px 45px 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            background: white;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: #DAA900;
        }
        .checkbox-group { margin-bottom: 25px; }
        .checkbox-label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: rgba(255,255,255,0.8); }
        .checkbox-label input { width: 18px; height: 18px; accent-color: #DAA900; }
        .btn-login { width: 100%; padding: 14px; background: #DAA900; color: #2A4B2F; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; }
        .btn-login:hover { background: #c49c00; transform: translateY(-2px); }
        .error-message { background: rgba(220,38,38,0.2); color: #ffcccc; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .demo-info { background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; margin-top: 20px; text-align: center; color: rgba(255,255,255,0.7); font-size: 12px; }
        .demo-info strong { color: #DAA900; }
        
        /* MEMBUAT KONTEN DI KIRI LEBIH KE BAWAH */
        .logo-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 100%;
            padding-bottom: 80px;
        }
        
        @media (max-width: 900px) { 
            .login-wrapper { flex-direction: column; } 
            .main-logo { width: 200px; height: 200px; } 
            .resto-name { font-size: 24px; margin-top: 20px; } 
            .logo-wrapper { padding-bottom: 40px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div class="logo-container">
                <div class="logo-wrapper">
                    <h1 class="resto-name">Resto Serba Serbi</h1>
                    <img src="img/logo1.png" alt="Logo Resto Serba Serbi" class="main-logo" onerror="this.src='https://placehold.co/350x350/2A4B2F/DAA900?text=Resto+Serba+Serbi'">
                </div>
            </div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <p class="welcome-text">Selamat Datang</p>
                <p class="login-subtext">Silahkan Login untuk Mengakses Halaman Kasir</p>
                <?php if ($error): ?>
                    <div class="error-message"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="input-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" placeholder="Masukkan Username" required>
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" placeholder="Masukkan Password" required>
                            <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            Ingatkan Saya
                        </label>
                    </div>
                    <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>
</body>
</html>