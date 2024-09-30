<link rel="stylesheet" href="css/styles.css">
<?php
// 包含数据库连接
require_once 'includes/db_connect.php';

// 分页设置
$posts_per_page = 5; // 每页显示的帖子数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // 当前页码
$offset = ($page - 1) * $posts_per_page; // 计算偏移量

// 获取帖子总数
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM posts");
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $posts_per_page);
} catch (PDOException $e) {
    $error = "获取帖子总数失败：" . $e->getMessage();
}

// 获取当前页的帖子
try {
    $query = "
        SELECT p.*, u.username, 
               COUNT(DISTINCT c.id) AS comment_count,
               COUNT(DISTINCT l.id) AS like_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN user_interactions c ON p.id = c.post_id AND c.interaction_type = 'comment'
        LEFT JOIN user_interactions l ON p.id = l.post_id AND l.interaction_type = 'like'
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "获取帖子失败：" . $e->getMessage();
}

// 显示帖子内容
?>
<div class="posts-container">
    <h2>最新帖子</h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif (empty($posts)): ?>
        <p>暂时没有帖子。</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h3><?php echo htmlspecialchars($post['title'] ?? ''); ?></h3>
                <p class="post-meta">
                    作者: <?php echo htmlspecialchars($post['username']); ?> | 
                    发布于: <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?>
                </p>
                <p><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>...</p>

                <!-- 帖子图片 -->
                <?php if (!empty($post['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="帖子图片" class="post-image">
                <?php endif; ?>
                
                <!-- 帖子视频 -->
                <?php if (!empty($post['video_id'])): ?>
                    <div class="video-container">
                        <iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($post['video_id']); ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>

                <div class="post-stats">
                    <span class="comment-count">评论: <?php echo $post['comment_count']; ?></span>
                    <span class="like-count">点赞: <?php echo $post['like_count']; ?></span>
                </div>

                <a href="view_post.php?id=<?php echo $post['id']; ?>" class="read-more">查看详情</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 分页 -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>">上一页</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>">下一页</a>
    <?php endif; ?>
</div>
