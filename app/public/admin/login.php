<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';

AdminUserService::ensureSchema();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Por favor complete todos los campos.';
    } else {
        if (AdminUserService::authenticateLegacy($username, $password)) {
            AdminUserService::loginLegacySuperAdmin();
            header('Location: /admin/index.php');
            exit;
        }
        $user = AdminUserService::authenticate($username, $password);
        if ($user) {
            AdminUserService::loginSession($user);
            header('Location: /admin/index.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: /admin/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | Automarket</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --navy: #081026;
            --navy-light: #121f42;
            --primary-red: #c51f17;
            --white: #ffffff;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-2px);
        }
        .logo-img {
            max-height: 40px;
            filter: brightness(0) invert(1);
            margin-bottom: 20px;
        }
        .form-control-admin {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--white) !important;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control-admin:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(197, 31, 23, 0.25);
        }
        .btn-admin-submit {
            background-color: var(--primary-red);
            border: 1px solid var(--primary-red);
            color: var(--white);
            padding: 12px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-admin-submit:hover {
            background-color: #a41710;
            border-color: #a41710;
            transform: translateY(-1px);
        }
        .admin-alert {
            background-color: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ea868f;
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="login-card text-center">
        <img src="/assets/img/logo.png" alt="Automarket Logo" class="logo-img">
        <h4 class="fw-bold font-montserrat mb-1">Panel de Control</h4>
        <p class="text-white-50 text-sm mb-4" style="font-size: 0.85rem;">Inicie sesión para administrar contenidos</p>
        
        <?php if (!empty($error)): ?>
            <div class="admin-alert text-start">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo esc($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label for="username" class="form-label text-white-50 fw-semibold" style="font-size: 0.85rem;">Nombre de Usuario</label>
                <input type="text" id="username" name="username" class="form-control form-control-admin" placeholder="admin" required autocomplete="username">
            </div>
            
            <div class="mb-4 text-start">
                <label for="password" class="form-label text-white-50 fw-semibold" style="font-size: 0.85rem;">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control form-control-admin" placeholder="••••••••" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-admin-submit w-100 d-flex align-items-center justify-content-center gap-2">
                <span>INGRESAR</span> <i class="bi bi-box-arrow-in-right"></i>
            </button>
        </form>
        
        <div class="mt-4">
            <a href="/rent-a-car.php" class="text-white-50 text-decoration-none" style="font-size: 0.8rem;"><i class="bi bi-arrow-left me-1"></i> Volver al sitio web</a>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
