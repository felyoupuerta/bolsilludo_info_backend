<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir al inicio
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$title = "Registrarse";
$errors = [];

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    
    // Validaciones básicas del lado del cliente
    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $errors['general'] = 'Todos los campos son obligatorios';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Las contraseñas no coinciden';
    } else {
        // Intentar registrar usando el método mejorado
        $result = Auth::register($username, $email, $password);
        
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
            header("Location: login.php");
            exit();
        } else {
            // Determinar qué tipo de error mostrar
            if (strpos($result['error'], 'usuario') !== false) {
                $errors['username'] = $result['error'];
            } elseif (strpos($result['error'], 'email') !== false) {
                $errors['email'] = $result['error'];
            } elseif (strpos($result['error'], 'contraseña') !== false) {
                $errors['password'] = $result['error'];
            } else {
                $errors['general'] = $result['error'];
            }
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
                        <h2>Registrarse</h2>
                        <p class="text-muted">Crea tu cuenta</p>
                    </div>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password2" class="form-label">Repetir Contraseña</label>
                            <input type="password" class="form-control <?php echo isset($errors['password2']) ? 'is-invalid' : ''; ?>" 
                                   id="password2" name="password2" required>
                            <?php if (isset($errors['password2'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password2']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Registrarse</button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?> 
