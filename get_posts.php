<?php
require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT 
        p.*, 
        s.username, 
        s.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND student_id = :user_id) as user_liked
    FROM posts p
    JOIN students s ON p.user_id = s.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($posts) && $page === 1) {
    echo '<div class="alert alert-info text-center">No posts yet. Click + to create your first post!</div>';
    exit;
}
if (empty($posts)) exit;

function getMediaFiles($mediaStr) {
    if (empty($mediaStr)) return [];
    $files = explode(',', $mediaStr);
    return array_map('trim', $files);
}

function isVideo($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'mov', 'avi', 'webm', 'ogg']);
}

function renderCarousel($mediaFiles, $postId) {
    $count = count($mediaFiles);
    if ($count == 1) {
        $file = $mediaFiles[0];
        $path = 'assets/uploads/posts/' . $file;
        if (isVideo($file)) {
            return '<video controls class="post-media" style="width:100%; max-height:600px; object-fit:contain;">
                        <source src="'.$path.'">
                    </video>';
        } else {
            return '<img src="'.$path.'" class="post-media" style="width:100%; max-height:600px; object-fit:contain;">';
        }
    }

    // Multiple media → carousel
    $html = '<div id="postCarousel-'.$postId.'" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                <div class="carousel-inner">';
    foreach ($mediaFiles as $idx => $file) {
        $active = $idx == 0 ? 'active' : '';
        $path = 'assets/uploads/posts/' . $file;
        if (isVideo($file)) {
            $html .= '<div class="carousel-item '.$active.'" style="text-align: center;">
                        <video controls style="width:auto; max-width:100%; max-height:600px; object-fit:contain; margin:0 auto;">
                            <source src="'.$path.'">
                        </video>
                      </div>';
        } else {
            $html .= '<div class="carousel-item '.$active.'" style="text-align: center;">
                        <img src="'.$path.'" style="width:auto; max-width:100%; max-height:600px; object-fit:contain; margin:0 auto;">
                      </div>';
        }
    }
    $html .= '</div>';
    if ($count > 1) {
        $html .= '<button class="carousel-control-prev" type="button" data-bs-target="#postCarousel-'.$postId.'" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                  </button>
                  <button class="carousel-control-next" type="button" data-bs-target="#postCarousel-'.$postId.'" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                  </button>';
    }
    $html .= '</div>';
    return $html;
}

foreach ($posts as $post) {
    $profilePic = !empty($post['profile_pic']) ? 'assets/uploads/profiles/' . $post['profile_pic'] : 'assets/default-avatar.png';
    $likedClass = $post['user_liked'] ? 'liked' : '';
    $likeIcon = $post['user_liked'] ? 'fas fa-heart' : 'far fa-heart';
    $mediaFiles = getMediaFiles($post['media']);
    ?>
    <div class="feed-post" data-post-id="<?= $post['id'] ?>">
        <div class="post-header">
            <img src="<?= htmlspecialchars($profilePic) ?>" onerror="this.src='assets/default-avatar.png'">
            <span class="username"><?= htmlspecialchars($post['username']) ?></span>
        </div>
        <div class="post-media-container">
            <?= renderCarousel($mediaFiles, $post['id']) ?>
        </div>
        <div class="post-actions">
            <button class="like-btn <?= $likedClass ?>" data-post-id="<?= $post['id'] ?>">
                <i class="<?= $likeIcon ?>"></i> <span class="like-count"><?= $post['like_count'] ?></span>
            </button>
            <button class="comment-btn" data-post-id="<?= $post['id'] ?>">
                <i class="far fa-comment"></i> <span class="comment-count"><?= $post['comment_count'] ?></span>
            </button>
        </div>
        <div class="px-3 pb-2">
            <strong><?= htmlspecialchars($post['username']) ?></strong> 
            <?= nl2br(htmlspecialchars($post['content'])) ?>
        </div>
    </div>
    <?php
}
?>