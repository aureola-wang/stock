<?php
require_once 'includes/db_connect.php';

try {
    // 获取当前数据库名称
    $stmt = $conn->query("SELECT DATABASE()");
    $db_name = $stmt->fetchColumn();

    if (!$db_name) {
        throw new Exception("无法获取数据库名称");
    }

    // 设置数据库字符集为 utf8mb4
    $conn->exec("ALTER DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "成功将数据库 $db_name 的字符集设置为 utf8mb4<br>";

    // 更新现有表的字符集
    $tables = ['users', 'posts', 'user_interactions'];
    foreach ($tables as $table) {
        $conn->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "成功将 $table 表的字符集更新为 utf8mb4<br>";
    }

    // 添加 image_path 列到 posts 表
    $sql = "ALTER TABLE posts ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL";
    $conn->exec($sql);
    echo "成功添加 image_path 列到 posts 表<br>";

    // 添加 video_id 列到 posts 表
    $sql = "ALTER TABLE posts ADD COLUMN IF NOT EXISTS video_id VARCHAR(20) DEFAULT NULL";
    $conn->exec($sql);
    echo "成功添加 video_id 列到 posts 表<br>";

    // 创建 user_interactions 表（如果不存在）
    $sql = "CREATE TABLE IF NOT EXISTS user_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        post_id INT NOT NULL,
        interaction_type ENUM('like', 'comment', 'share') NOT NULL,
        content TEXT,
        is_private BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id),
        FOREIGN KEY (post_id) REFERENCES posts(id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "成功创建 user_interactions 表（如果不存在）<br>";

    // 如果之前有移除 email 列的操作，我们可以保留它
    // 移除 users 表中的 email 列（如果存在）
    $sql = "ALTER TABLE users DROP COLUMN IF EXISTS email";
    $conn->exec($sql);
    echo "成功移除 email 列（如果存在）<br>";

    // 移除 email 的唯一索引（如果存在）
    try {
        $sql = "ALTER TABLE users DROP INDEX email";
        $conn->exec($sql);
        echo "成功移除 email 索引<br>";
    } catch (PDOException $e) {
        echo "移除 email 索引失败（可能是因为索引不存在）: " . $e->getMessage() . "<br>";
    }

    // 在现有的代码中添加以下内容
    $sql = "ALTER TABLE user_interactions 
            ADD COLUMN IF NOT EXISTS parent_id INT DEFAULT NULL,
            ADD FOREIGN KEY (parent_id) REFERENCES user_interactions(id)";
    $conn->exec($sql);
    echo "成功添加 parent_id 列到 user_interactions 表<br>";

    // 添加 created_at 列到 posts 表（如果不存在）
    $sql = "ALTER TABLE posts ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $conn->exec($sql);
    echo "成功添加 created_at 列到 posts 表（如果不存在）<br>";

    // 添加 created_at 列到 user_interactions 表（如果不存在）
    $sql = "ALTER TABLE user_interactions ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $conn->exec($sql);
    echo "成功添加 created_at 列到 user_interactions 表（如果不存在）<br>";

    echo "数据库更新完成！";

} catch(PDOException $e) {
    echo "更新数据库失败: " . $e->getMessage();
} catch(Exception $e) {
    echo "错误: " . $e->getMessage();
}
?>
