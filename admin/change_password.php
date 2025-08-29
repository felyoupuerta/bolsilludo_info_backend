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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];

    if (strlen($new_password) < 6) {
        $_SESSION['flash_message'] = "La contraseña debe tener al menos 6 caracteres.";
        $_SESSION['flash_type'] = "error";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Contraseña actualizada correctamente.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error al actualizar la contraseña.";
            $_SESSION['flash_type'] = "error";
        }
        $stmt->close();
        $db->close();

        header("Location: panel_usuarios.php");
        exit();
    }
}

// Obtener datos actuales del usuario
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
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
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h1>Cambiar Contraseña</h1>
        <p><strong><?= htmlspecialchars($usuario['username']) ?></strong> (<?= htmlspecialchars($usuario['email']) ?>)</p>
        <form method="POST">
            <div class="mb-3">
                <label for="new_password" class="form-label">Nueva Contraseña</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
            <a href="panel_usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
