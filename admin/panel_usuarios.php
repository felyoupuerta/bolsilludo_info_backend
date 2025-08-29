<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

$title = "Gestión de Usuarios - Panel de Administración";

// Manejo de eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // No permitir eliminar el propio usuario admin
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['flash_message'] = "No puedes eliminarte a ti mismo.";
        $_SESSION['flash_type'] = "error";
    } else {
        $db = getDBConnection();
        if ($db) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['flash_message'] = "Usuario eliminado correctamente.";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "No se pudo eliminar el usuario. Puede que sea un administrador.";
                    $_SESSION['flash_type'] = "error";
                }
            } else {
                $_SESSION['flash_message'] = "Error al eliminar el usuario.";
                $_SESSION['flash_type'] = "error";
            }
            $stmt->close();
            $db->close();
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$usuarios_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $usuarios_por_pagina;

$db = getDBConnection();
if (!$db) {
    die("Error de conexión a la base de datos");
}

// Contar el total de usuarios
$result = $db->query("SELECT COUNT(*) as total FROM users");
if (!$result) {
    die("Error al contar usuarios: " . $db->error);
}
$row = $result->fetch_assoc();
$total_usuarios = $row['total'];
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

// Obtener usuarios con roles
$stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC LIMIT ? OFFSET ?");
if (!$stmt) {
    die("Error en la consulta: " . $db->error);
}
$stmt->bind_param("ii", $usuarios_por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - BOLSILLUDO_INFO</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --nacional-blue: #003f7f;
            --nacional-white: #ffffff;
            --nacional-red: #dc143c;
            --nacional-gold: #ffd700;
            --nacional-light-blue: #4a90e2;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--nacional-blue) 0%, var(--nacional-light-blue) 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .header-admin {
            background: linear-gradient(45deg, var(--nacional-blue), var(--nacional-red));
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }
        
        .header-admin h1 {
            margin: 0;
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .btn-nacional {
            background: linear-gradient(45deg, var(--nacional-blue), var(--nacional-light-blue));
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-nacional:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            color: white;
        }
        
        .btn-warning-custom {
            background: linear-gradient(45deg, var(--nacional-gold), #ffb347);
            color: #000;
        }
        
        .btn-danger-custom {
            background: linear-gradient(45deg, var(--nacional-red), #ff6b6b);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .table-nacional {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table-nacional thead {
            background: linear-gradient(45deg, var(--nacional-blue), var(--nacional-light-blue));
            color: white;
        }
        
        .table-nacional thead th {
            padding: 15px;
            font-weight: 600;
            border: none;
        }
        
        .table-nacional tbody tr {
            transition: all 0.3s ease;
        }
        
        .table-nacional tbody tr:hover {
            background-color: rgba(74, 144, 226, 0.1);
            transform: scale(1.01);
        }
        
        .table-nacional tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .role-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-admin {
            background: linear-gradient(45deg, var(--nacional-gold), #ffb347);
            color: #000;
        }
        
        .role-user {
            background: linear-gradient(45deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .pagination-nacional {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination-nacional a, .pagination-nacional strong {
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination-nacional a {
            background: linear-gradient(45deg, var(--nacional-light-blue), #87ceeb);
            color: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .pagination-nacional a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .pagination-nacional strong {
            background: linear-gradient(45deg, var(--nacional-red), #ff6b6b);
            color: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-sm-nacional {
            padding: 8px 12px;
            font-size: 0.8rem;
            border-radius: 15px;
        }
        
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stats-card {
            background: linear-gradient(45deg, var(--nacional-blue), var(--nacional-light-blue));
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-width: 200px;
        }
        
        .stats-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stats-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header-admin">
        <div class="container text-center">
            <h1><i class="fas fa-shield-alt"></i> BOLSILLUDO_INFO</h1>
            <p class="mb-0 fs-5">Panel de Administración - Club Nacional de Football</p>
        </div>
    </div>

    <div class="container">
        <div class="admin-container">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $_SESSION['flash_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['flash_message']); 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="header-controls">
                <div>
                    <a href="../index.php" class="btn btn-nacional">
                        <i class="fas fa-arrow-left"></i> Volver al Inicio
                    </a>
                    <a href="index.php" class="btn btn-nacional">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>
                <div class="stats-card">
                    <h3><?php echo $total_usuarios; ?></h3>
                    <p>Usuarios Registrados</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary fw-bold">
                    <i class="fas fa-users"></i> Gestión de Usuarios
                </h2>
                <a href="#" class="btn btn-success-custom">
                    <i class="fas fa-user-cog"></i> Gestionar Roles y Contraseñas
                </a>
            </div>
            
            <?php if (!empty($usuarios)): ?>
                <div class="table-responsive">
                    <table class="table table-nacional">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-user"></i> Usuario</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-user-tag"></i> Rol</th>
                                <th><i class="fas fa-calendar"></i> Fecha Registro</th>
                                <th><i class="fas fa-cogs"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usuarios as $usuario): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($usuario['id']) ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                <?= strtoupper(substr($usuario['username'], 0, 1)) ?>
                                            </div>
                                            <strong><?= htmlspecialchars($usuario['username']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>
                                        <span class="role-badge <?= $usuario['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                            <i class="fas fa-<?= $usuario['role'] === 'admin' ? 'crown' : 'user' ?>"></i>
                                            <?= ucfirst(htmlspecialchars($usuario['role'] ?? 'user')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_rol.php?id=<?= $usuario['id'] ?>" 
                                               class="btn btn-warning-custom btn-sm-nacional" 
                                               title="Editar Usuario">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="change_password.php?id=<?= $usuario['id'] ?>" 
                                               class="btn btn-nacional btn-sm-nacional" 
                                               title="Cambiar Contraseña">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?= htmlspecialchars($usuario['username']) ?>')">
                                                    <input type="hidden" name="user_id" value="<?= $usuario['id'] ?>">
                                                    <button type="submit" class="btn btn-danger-custom btn-sm-nacional" title="Eliminar Usuario">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination-nacional">
                        <?php if($pagina > 1): ?>
                            <a href="?pagina=<?= $pagina - 1 ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $pagina - 2);
                        $end = min($total_paginas, $pagina + 2);
                        
                        for($i = $start; $i <= $end; $i++): ?>
                            <?php if($i == $pagina): ?>
                                <strong><?= $i ?></strong>
                            <?php else: ?>
                                <a href="?pagina=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($pagina < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina + 1 ?>">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay usuarios registrados</h4>
                    <p class="text-muted">Los usuarios aparecerán aquí cuando se registren en el sitio</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(username) {
            return confirm(`¿Estás seguro de que quieres eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`);
        }
    </script>
</body>
</html>
