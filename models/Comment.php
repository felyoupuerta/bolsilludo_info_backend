<?php
require_once __DIR__ . '/../includes/config.php';

class Comment {
    public static function addComment($news_id, $user_id, $comment) {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $news_id, $user_id, $comment);
        return $stmt->execute();
    }

    public static function getComments($news_id) {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT c.comment, c.created_at, u.username
            FROM news_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.news_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
} 
