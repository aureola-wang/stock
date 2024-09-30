<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("请先登录");
}

$post_id = $_POST['post_id'] ?? 0;
$receiver_id = $_POST['receiver_id'] ?? 0;
$interaction_type = $_POST['interaction_type'] ?? '';
$content = mb_convert_encoding($_POST['content'] ?? '', 'UTF-8', 'auto');
$is_private = isset($_POST['is_private']) ? 1 : 0;
$parent_id = $_POST['parent_id'] ?? null;

if (!$post_id || !$receiver_id || !$interaction_type) {
    die("缺少必要参数");
}

try {
    $stmt = $conn->prepare("INSERT INTO user_interactions (sender_id, receiver_id, post_id, interaction_type, content, is_private, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $post_id, $interaction_type, $content, $is_private, $parent_id]);
    
    // 设置成功消息
    $_SESSION['success_message'] = "互动已成功添加！";
    
    // 重定向到首页
    header("Location: index.php");
    exit();
} catch (PDOException $e) {
    die("添加互动失败：" . $e->getMessage());
}
