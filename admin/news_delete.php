<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'Noticia eliminada exitosamente';
    } else {
        $_SESSION['flash_error'] = 'Error al eliminar la noticia';
    }
    
    $db->close();
}

header("Location: index.php");
exit();
?> 
