<?php
session_start();
require_once "includes/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión']);
    exit;
}

if (!isset($_POST['news_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de noticia no recibido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$news_id = intval($_POST['news_id']);

$db = getDBConnection();
if (!$db) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}

// Verificar si ya dio like
$stmt = $db->prepare("SELECT id FROM news_likes WHERE user_id = ? AND news_id = ?");
$stmt->bind_param("ii", $user_id, $news_id);
$stmt->execute();
$result = $stmt->get_result();
$like = $result->fetch_assoc();

if ($like) {
    // Quitar like
    $stmt = $db->prepare("DELETE FROM news_likes WHERE user_id = ? AND news_id = ?");
    $stmt->bind_param("ii", $user_id, $news_id);
    $stmt->execute();
    $liked = false;
} else {
    // Dar like
    $stmt = $db->prepare("INSERT INTO news_likes (user_id, news_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $news_id);
    $stmt->execute();
    $liked = true;
}

// Contar likes totales
$stmt = $db->prepare("SELECT COUNT(*) as total FROM news_likes WHERE news_id = ?");
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalLikes = $row['total'];

echo json_encode([
    'success' => true,
    'liked'   => $liked,
    'likes'   => $totalLikes
]);
exit;

