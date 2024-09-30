<?php
session_start();
require_once 'includes/db_connect.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取帖子信息
$stmt = $conn->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("帖子不存在");
}

// 获取互动
$stmt = $conn->prepare("
    SELECT ui.*, u.username 
    FROM user_interactions ui 
    JOIN users u ON ui.sender_id = u.id 
    WHERE ui.post_id = ? AND (ui.is_private = FALSE OR ui.sender_id = ? OR ui.receiver_id = ? OR ? = ?)
    ORDER BY ui.created_at DESC
");
$stmt->execute([$post_id, $_SESSION['user_id'] ?? 0, $_SESSION['user_id'] ?? 0, $_SESSION['user_id'] ?? 0, $post['user_id']]);
$interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取评论
$stmt = $conn->prepare("
    SELECT ui.*, u.username 
    FROM user_interactions ui 
    JOIN users u ON ui.sender_id = u.id 
    WHERE ui.post_id = ? AND ui.interaction_type = 'comment'
    ORDER BY ui.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 构建评论树
$commentTree = [];
foreach ($comments as $comment) {
    $comment['replies'] = [];
    if ($comment['parent_id'] === null) {
        $commentTree[$comment['id']] = $comment;
    } else {
        $commentTree[$comment['parent_id']]['replies'][] = $comment;
    }
}

// 递归函数来显示评论
function displayComments($comments, $level = 0) {
    foreach ($comments as $comment) {
        echo "<div class='comment' style='margin-left: " . ($level * 20) . "px;'>";
        echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong> ";
        echo "<span class='comment-time'>" . date('Y-m-d H:i', strtotime($comment['created_at'])) . "</span></p>";
        echo "<p>" . htmlspecialchars($comment['content']) . "</p>";
        echo "<a href='#' class='reply-link' data-comment-id='" . $comment['id'] . "'>回复</a>";
        if (!empty($comment['replies'])) {
            displayComments($comment['replies'], $level + 1);
        }
        echo "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看帖子</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>查看帖子</h1>
        <div class="post">
            <h2><?php echo htmlspecialchars($post['title'] ?? ''); ?></h2>
            <p>作者: <?php echo htmlspecialchars($post['username']); ?></p>
            <p class="post-time">发布于: <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></p>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <?php if (!empty($post['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="帖子图片">
            <?php endif; ?>
            <?php if (!empty($post['video_id'])): ?>
                <div class="video-container" data-video-id="<?php echo htmlspecialchars($post['video_id']); ?>">
                    <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($post['video_id']); ?>/0.jpg" alt="视频缩略图">
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <h3>添加互动</h3>
            <form action="add_interaction.php" method="post" id="interactionForm">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="receiver_id" value="<?php echo $post['user_id']; ?>">
                <select name="interaction_type" id="interactionType">
                    <option value="like">点赞</option>
                    <option value="comment">评论</option>
                    <option value="share">分享</option>
                </select>
                <textarea name="content" id="interactionContent" placeholder="评论内容（如果选择评论）"></textarea>
                <label>
                    <input type="checkbox" name="is_private" value="1"> 仅对方可见
                </label>
                <button type="submit">提交</button>
            </form>
        <?php endif; ?>

        <h3>互动</h3>
        <?php if (empty($interactions)): ?>
            <p>暂无互动</p>
        <?php else: ?>
            <?php foreach ($interactions as $interaction): ?>
                <div class="interaction">
                    <p>
                        <?php echo htmlspecialchars($interaction['username']); ?>
                        <?php
                        switch ($interaction['interaction_type']) {
                            case 'like':
                                echo "点赞了这篇帖子";
                                break;
                            case 'comment':
                                echo "评论道：" . htmlspecialchars($interaction['content']);
                                break;
                            case 'share':
                                echo "分享了这篇帖子";
                                break;
                        }
                        ?>
                        <?php if ($interaction['is_private']): ?>
                            <span class="private-badge">私密</span>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3>评论</h3>
        <?php displayComments($commentTree); ?>

        <form id="commentForm" action="add_interaction.php" method="post">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <input type="hidden" name="receiver_id" value="<?php echo $post['user_id']; ?>">
            <input type="hidden" name="interaction_type" value="comment">
            <input type="hidden" name="parent_id" id="parentId" value="">
            <textarea name="content" required></textarea>
            <button type="submit">发表评论</button>
        </form>

    </div>
    <script src="js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const interactionType = document.getElementById('interactionType');
        const interactionContent = document.getElementById('interactionContent');

        if (interactionType && interactionContent) {
            interactionType.addEventListener('change', function() {
                if (this.value === 'comment') {
                    interactionContent.style.display = 'block';
                    interactionContent.setAttribute('required', 'required');
                } else {
                    interactionContent.style.display = 'none';
                    interactionContent.removeAttribute('required');
                }
            });

            interactionContent.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    interactionType.value = 'comment';
                }
            });
        }

        const replyLinks = document.querySelectorAll('.reply-link');
        const commentForm = document.getElementById('commentForm');
        const parentIdInput = document.getElementById('parentId');

        replyLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const commentId = this.getAttribute('data-comment-id');
                parentIdInput.value = commentId;
                commentForm.scrollIntoView({ behavior: 'smooth' });
            });
        });
    });
    </script>
</body>
</html>
