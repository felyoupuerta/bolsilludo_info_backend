<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

if (!isset($_GET['id'])) {
    die("ID de usuario no especificado.");
}
$user_id = (int)$_GET['id'];

$db = getDBConnection();
if (!$db) {
    die("Error de conexión a la base de datos");
}

// Si envían el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];

    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Rol actualizado correctamente.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error al actualizar el rol.";
        $_SESSION['flash_type'] = "error";
    }
    $stmt->close();
    $db->close();

    header("Location: panel_usuarios.php");
    exit();
}

// Obtener datos actuales del usuario
$stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Club Nacional de Fútbol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --nacional-blue: #0038a8;
            --nacional-red: #d52b1e;
            --nacional-white: #ffffff;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(90deg, var(--nacional-blue) 70%, var(--nacional-red) 30%);
        }
        .btn-nacional {
            background-color: var(--nacional-blue);
            color: white;
            border: none;
        }
        .btn-nacional:hover {
            background-color: #002875;
            color: white;
        }
        .btn-outline-nacional {
            border: 1px solid var(--nacional-blue);
            color: var(--nacional-blue);
        }
        .btn-outline-nacional:hover {
            background-color: var(--nacional-blue);
            color: white;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background: linear-gradient(45deg, var(--nacional-blue), var(--nacional-red));
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .user-info {
            background-color: #f0f4ff;
            border-left: 4px solid var(--nacional-blue);
        }
        .form-select:focus {
            border-color: var(--nacional-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 56, 168, 0.25);
        }
        .footer {
            background-color: var(--nacional-blue);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzAwMzhhOCIgLz4KICA8cGF0aCBkPSJNMjUsMjUgTDUwLDUwIEw3NSwyNSBMNzUsNzUgTDUwLDUwIEwyNSw3NSBMMjUsMjUgWiIgZmlsbD0iI2Q1MmIxZSIgLz4KPC9zdmc+" alt="Logo" width="40" height="40" class="d-inline-block align-text-top me-2">
                Club Nacional de Fútbol
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header py-3">
                        <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Rol de Usuario</h4>
                    </div>
                    <div class="card-body">
                        <div class="user-info p-3 mb-4">
                            <p class="mb-1"><strong><i class="fas fa-user me-2"></i>Nombre de usuario:</strong> <?= htmlspecialchars($usuario['username']) ?></p>
                            <p class="mb-0"><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="role" class="form-label fw-bold"><i class="fas fa-user-tag me-2"></i>Rol del usuario</label>
                                <select name="role" id="role" class="form-select form-select-lg">
                                    <option value="user" <?= $usuario['role'] === 'user' ? 'selected' : '' ?>>Usuario Normal</option>
                                    <option value="admin" <?= $usuario['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                                <div class="form-text">Seleccione el nivel de acceso que tendrá este usuario en el sistema.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="panel_usuarios.php" class="btn btn-outline-nacional me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al Panel
                                </a>
                                <button type="submit" class="btn btn-nacional">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-3 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2023 Club Nacional de Fútbol. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Sistema de Gestión de Usuarios</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>