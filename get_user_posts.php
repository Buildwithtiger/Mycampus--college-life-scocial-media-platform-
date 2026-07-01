<?php
require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$profile_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$exclude_id = isset($_GET['exclude']) ? (int)$_GET['exclude'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 3;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT p.*, s.username, s.profile_pic
    FROM posts p
    JOIN students s ON p.user_id = s.id
    WHERE p.user_id = :profile_id AND p.id != :exclude_id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
$stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll();
if (empty($posts)) exit;

function displayOtherMedia($mediaStr) {
    if (empty($mediaStr)) return '';
    $files = explode(',', $mediaStr);
    $firstFile = trim($files[0]);
    $path = 'assets/uploads/posts/' . $firstFile;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        return '<img src="'.$path.'" class="other-post-media" style="width:100%; max-height:300px; object-fit:cover;">';
    } elseif (in_array($ext, ['mp4','mov','avi','webm','ogg'])) {
        return '<video controls class="other-post-media" style="width:100%; max-height:300px;"><source src="'.$path.'"></video>';
    }
    return '';
}

foreach ($posts as $p) {
    $profilePic = !empty($p['profile_pic']) ? 'assets/uploads/profiles/' . $p['profile_pic'] : 'assets/default-avatar.png';
    $isOwner = ($p['user_id'] == $user_id);
    // Get comment count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $countStmt->execute([$p['id']]);
    $commentCount = $countStmt->fetchColumn();
    ?>
    <div class="other-post-card" data-post-id="<?= $p['id'] ?>">
        <div class="other-post-header">
            <img src="<?= htmlspecialchars($profilePic) ?>" onerror="this.src='assets/default-avatar.png'">
            <span class="username"><?= htmlspecialchars($p['username']) ?></span>
            <?php if ($isOwner): ?>
            <div class="dropdown ms-auto">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item edit-other-post" data-post-id="<?= $p['id'] ?>" data-content="<?= htmlspecialchars($p['content']) ?>">Edit Post</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger delete-other-post" data-post-id="<?= $p['id'] ?>">Delete Post</button></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <a href="post.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
            <?= displayOtherMedia($p['media']) ?>
            <div class="px-3 pb-2">
                <strong><?= htmlspecialchars($p['username']) ?></strong> <?= nl2br(htmlspecialchars($p['content'])) ?>
            </div>
        </a>
        <div class="post-actions" style="padding: 8px 12px; border-top: 1px solid #efefef;">
            <button class="comment-btn-feed" data-post-id="<?= $p['id'] ?>">
                <i class="far fa-comment"></i> <span class="comment-count"><?= $commentCount ?></span>
            </button>
        </div>
    </div>
    <?php
}
?>