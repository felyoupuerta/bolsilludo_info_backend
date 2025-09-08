<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'bolsilludo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Iniciar sesión
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Conexión a la base de datos
function getDBConnection() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($mysqli->connect_errno) {
        error_log("[DB ERROR] Connection failed: " . $mysqli->connect_error);
        return false;
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        error_log("[DB ERROR] Failed to set charset: " . $mysqli->error);
        $mysqli->close();
        return false;
    }

    return $mysqli;
}

// Inicialización simple para prueba de conexión y existencia de tablas
//function testDB() {
//    $db = getDBConnection();//
//    if (!$db) {
//        die("❌ No se pudo conectar a la base de datos.");
//    }

// Comprobar si la tabla users existe
//    $result = $db->query("SHOW TABLES LIKE 'users'");
//    if ($result && $result->num_rows > 0) {
//        echo "✅ Conexión OK y tabla users encontrada.\n";
//    } else {
//        echo "⚠️ Conexión OK pero tabla users no encontrada.\n";
//    }

//    $db->close();
//}

// Ejecutar prueba
//testDB();
?>
