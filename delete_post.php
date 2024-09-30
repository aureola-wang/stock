<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'includes/db_connect.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// 检查帖子是否属于当前用户
try {
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    if (!$stmt->fetch()) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "验证帖子失败：" . $e->getMessage();
}

// 处理删除请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $success = "帖子已成功删除！";
    } catch(PDOException $e) {
        $error = "删除失败，请稍后再试。错误: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>删除帖子</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>删除帖子</h2>
        <?php
        if (!empty($error)) {
            echo "<p class='error'>$error</p>";
        }
        if (!empty($success)) {
            echo "<p class='success'>$success</p>";
            echo "<p><a href='index.php'>返回首页</a></p>";
        } else {
        ?>
        <p>您确定要删除这篇帖子吗？此操作不可撤销。</p>
        <form method="post" action="">
            <input type="hidden" name="confirm_delete" value="1">
            <input type="submit" value="确认删除">
            <a href="index.php" class="cancel">取消</a>
        </form>
        <?php } ?>
    </div>
</body>
</html>
