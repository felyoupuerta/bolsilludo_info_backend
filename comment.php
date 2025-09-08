<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Comment.php';
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Debes iniciar sesión"]);
    exit;
}

// Obtener datos desde $_POST (FormData lo envía aquí)
$news_id = isset($_POST['news_id']) ? intval($_POST['news_id']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($news_id === 0) {
    echo json_encode(["success" => false, "message" => "Falta news_id"]);
    exit;
}

if ($comment === "") {
    echo json_encode(["success" => false, "message" => "El comentario no puede estar vacío"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener el username para la respuesta
$db = getDBConnection();
$stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];

// Guardar comentario
if (Comment::addComment($news_id, $user_id, $comment)) {
    echo json_encode([
        "success" => true, 
        "username" => $username,
        "comment" => htmlspecialchars($comment)
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error al guardar comentario"]);
}
