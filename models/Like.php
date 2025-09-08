<?php
require_once __DIR__ . '/../includes/config.php';

class Like {
    public static function addLike($news_id, $user_id) {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT IGNORE INTO news_likes (news_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $news_id, $user_id);
        return $stmt->execute();
    }

    public static function removeLike($news_id, $user_id) {
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $news_id, $user_id);
        return $stmt->execute();
    }

    public static function hasUserLiked($news_id, $user_id) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT 1 FROM news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $news_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public static function getLikesCount($news_id) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM news_likes WHERE news_id = ?");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_array();
        return $row[0];
    }
} 
