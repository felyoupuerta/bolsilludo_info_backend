<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir al inicio
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$title = "Iniciar Sesión";
$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validación básica
    if (empty($username) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        // Intentar login
        if (Auth::login($username, $password)) {
            $_SESSION['flash_success'] = '¡Bienvenido, ' . htmlspecialchars($username) . '!';
            
            // Redirigir al admin si es administrador, sino al inicio
            if (Auth::isAdmin()) {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos. Por favor, verifica tus credenciales.';
            
            // Log del intento fallido (sin mostrar la contraseña)
            error_log("[LOGIN ATTEMPT] Failed login attempt for username: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
}

require_once 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2>Iniciar Sesión</h2>
                        <p class="text-muted">Accede a tu cuenta</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Iniciar Sesión</button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?> 
