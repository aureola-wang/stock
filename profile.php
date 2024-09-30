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
$error = '';
$success = '';

// 获取用户信息
try {
    $stmt = $conn->prepare("SELECT username, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "获取用户信息失败：" . $e->getMessage();
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_username)) {
        $error = "用户名不能为空。";
    } elseif ($new_username !== $user['username']) {
        // 检查新用户名是否已存在
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$new_username, $user_id]);
        if ($stmt->fetchColumn()) {
            $error = "该用户名已被使用。";
        }
    }

    if (empty($error)) {
        try {
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "两次输入的新密码不匹配。";
                } elseif (strlen($new_password) < 6) {
                    $error = "新密码至少需要6个字符。";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$new_username, $hashed_password, $user_id]);
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $user_id]);
            }
            $success = "个人资料更新成功！";
            $_SESSION['username'] = $new_username;
            $user['username'] = $new_username;
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
    <title>个人资料</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { display: block; width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>个人资料</h2>
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
                <label for="username">用户名:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-group">
                <label for="new_password">新密码 (留空表示不修改):</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <div class="form-group">
                <label for="confirm_password">确认新密码:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <div class="form-group">
                <label>注册时间: <?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></label>
            </div>
            <input type="submit" value="更新资料">
        </form>
        <p><a href="index.php">返回首页</a></p>
    </div>
</body>
</html>
