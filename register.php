<?php
require_once 'includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 简单的表单验证
    if (empty($username) || empty($password)) {
        $error = "用户名和密码都是必填的。";
    } elseif ($password !== $confirm_password) {
        $error = "两次输入的密码不匹配。";
    } elseif (strlen($password) < 6) {
        $error = "密码至少需要6个字符。";
    } else {
        try {
            // 检查用户名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn()) {
                $error = "该用户名已被使用。";
            } else {
                // 插入新用户
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $success = "注册成功！您现在可以登录了。";
            }
        } catch(PDOException $e) {
            $error = "注册失败，请稍后再试。错误: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
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
        <h2>用户注册</h2>
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
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">确认密码:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <input type="submit" value="注册">
        </form>
        <p><a href="index.php">返回首页</a></p>
    </div>
</body>
</html>
