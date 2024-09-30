<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'includes/db_connect.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);
    $image_path = '';

    if (empty($content)) {
        $error = "帖子内容不能为空。";
    } else {
        // 处理图片上传
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $filename = $_FILES["image"]["name"];
            $filetype = $_FILES["image"]["type"];
            $filesize = $_FILES["image"]["size"];

            // 验证文件扩展名
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!array_key_exists($ext, $allowed)) {
                $error = "错误：请选择一个有效的文件格式。";
            }

            // 验证文大小 - 最大为5MB
            $maxsize = 5 * 1024 * 1024;
            if ($filesize > $maxsize) {
                $error = "错误：文件大小超过限制（最大5MB）。";
            }

            // 验证MIME类型
            if (in_array($filetype, $allowed)) {
                // 检查是否有错误
                if (empty($error)) {
                    $new_filename = uniqid() . "." . $ext;
                    $upload_dir = "uploads/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    move_uploaded_file($_FILES["image"]["tmp_name"], $upload_dir . $new_filename);
                    $image_path = $upload_dir . $new_filename;
                }
            } else {
                $error = "错误：文件类型不允许。请只上传JPG、JPEG、PNG或GIF类型的文件。";
            }
        }

        $video_url = filter_var($_POST['video_url'], FILTER_SANITIZE_URL);
        if (!empty($video_url)) {
            // 简单的YouTube URL验证
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match)) {
                $video_id = $match[1];
            } else {
                $error = "无效的YouTube URL。";
            }
        }

        if (empty($error)) {
            try {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, video_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $content, $image_path, $video_id ?? null]);
                $success = "帖子发布成功！";
            } catch(PDOException $e) {
                $error = "发布失败，请稍后再试。错误: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发布新帖子</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>发布新帖子</h2>
        <?php
        if (!empty($error)) {
            echo "<p class='error'>$error</p>";
        }
        if (!empty($success)) {
            echo "<p class='success'>$success</p>";
        }
        ?>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="content">帖子内容:</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div class="form-group">
                <label for="image">上传图片（可选）:</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label for="video_url">视频URL（可选，支持YouTube）:</label>
                <input type="url" id="video_url" name="video_url">
            </div>
            <input type="submit" value="发布帖子">
        </form>
        <p><a href="index.php">返回首页</a></p>
    </div>
</body>
</html>
