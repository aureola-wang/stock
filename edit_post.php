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

// 获取帖子信息
try {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "获取帖子信息失败：" . $e->getMessage();
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);

    if (empty($content)) {
        $error = "帖子内容不能为空。";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$content, $post_id, $user_id]);
            $success = "帖子新成功！";
            $post['content'] = $content;
        } catch(PDOException $e) {
            $error = "更新失败，请稍后再试。错误: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑帖子</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>编帖子</h2>
        <?php
        if (!empty($error)) {
            echo "<p class='error'>$error</p>";
        }
        if (!empty($success)) {
            echo "<p class='success'>$success</p>";
        }
        ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="content">帖子内容:</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <input type="submit" value="更新帖子">
        </form>
        <p><a href="index.php">返回首页</a></p>
    </div>
</body>
</html>
