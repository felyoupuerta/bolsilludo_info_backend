<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté logueado antes de hacer logout
if (Auth::isLoggedIn()) {
    $username = $_SESSION['username'] ?? 'Usuario';
    
    // Log del logout
    error_log("[LOGOUT] User logged out: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Establecer mensaje de despedida
    $_SESSION['flash_info'] = 'Has cerrado sesión correctamente. ¡Hasta pronto!';
    
    // Realizar logout
    Auth::logout();
} else {
    // Si no está logueado, redirigir al inicio
    header("Location: index.php");
    exit();
}
?>
