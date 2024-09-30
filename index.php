<?php
session_start();
require_once 'includes/db_connect.php';

// 显示成功消息（如果有）
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . '</div>';
    unset($_SESSION['success_message']); // 显示后清除消息
}

// 用户状态显示
$user_status = isset($_SESSION['username']) 
    ? "欢迎，" . htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') . "！ 
       <a href='profile.php'>个人资料</a> | 
       <a href='logout.php'>登出</a>" 
    : "<a href='login.php'>登录</a> | <a href='register.php'>注册</a>";

// 获取热门帖子
$hotPostsQuery = "
    SELECT p.*, u.username, 
           COUNT(DISTINCT c.id) AS comment_count,
           COUNT(DISTINCT l.id) AS like_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN user_interactions c ON p.id = c.post_id AND c.interaction_type = 'comment'
    LEFT JOIN user_interactions l ON p.id = l.post_id AND l.interaction_type = 'like'
    GROUP BY p.id
    ORDER BY (COUNT(DISTINCT c.id) + COUNT(DISTINCT l.id)) DESC
    LIMIT 5
";
$hotPostsStmt = $conn->query($hotPostsQuery);
$hotPosts = $hotPostsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告牌网站</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        /* 基本布局样式 */
        .main-content {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .posts-container {
            flex: 3; /* 主要内容占3份 */
        }

        .sidebar {
            flex: 1; /* 侧边栏占1份 */
        }

        .post {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .post img {
            width: 100%;
            height: auto;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .post h3 {
            margin: 0 0 10px;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .pagination .active {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .hot-posts {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .hot-posts h2 {
            margin-top: 0;
        }

        .my-home-link {
            text-align: center;
            margin: 20px 0;
        }

        .my-home-link a {
            font-size: 20px;
            color: #007BFF;
            text-decoration: none;
        }

        .my-home-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- "我的小屋" 链接 -->
    <div class="my-home-link">
        <a href="https://aureola-wang.github.io/king" target="_blank">我的小屋</a>
    </div>

    <nav class="navbar">
        <div class="container">
            <a href="index.php">首页</a>
            <div>
                <a href="create_post.php">发布新帖</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">个人主页</a>
                    <a href="logout.php">登出</a>
                <?php else: ?>
                    <a href="login.php">登录</a>
                    <a href="register.php">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>欢迎来到公告牌网站</h1>
        <p><?php echo $user_status; ?></p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p><a href="create_post.php">发布新帖子</a></p>
        <?php endif; ?>

        <div class="main-content">
            <!-- 帖子列表 -->
            <?php include 'posts-container.php'; ?>

            <!-- 热门帖子部分 -->
            <div class="sidebar">
                <div class="hot-posts">
                    <h2>热门帖子</h2>
                    <?php if (!empty($hotPosts)): ?>
                        <?php foreach ($hotPosts as $post): ?>
                            <div class="post">
                                <h3><?php echo htmlspecialchars($post['title'] ?? ''); ?></h3>
                                <p class="post-meta">
                                    作者: <?php echo htmlspecialchars($post['username']); ?> | 
                                    评论: <?php echo $post['comment_count']; ?> | 
                                    点赞: <?php echo $post['like_count']; ?>
                                </p>
                                <a href="view_post.php?id=<?php echo $post['id']; ?>" class="read-more">查看详情</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>没有热门帖子。</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 分页链接移到页面最下端 -->
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
    </div>
</body>
</html>
