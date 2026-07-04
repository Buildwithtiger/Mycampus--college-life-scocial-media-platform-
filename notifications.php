<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_read'])) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?")->execute([$user_id]);
    redirect('notifications.php');
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return $diff . 's';
    if ($diff < 3600) return floor($diff/60) . 'm';
    if ($diff < 86400) return floor($diff/3600) . 'h';
    if ($diff < 604800) return floor($diff/86400) . 'd';
    return date('M j', $time);
}

// Fetch notifications with sender info and follow status
$stmt = $db->prepare("
    SELECT n.*, s.username, s.profile_pic,
           (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = n.from_student_id) as i_follow_them
    FROM notifications n
    LEFT JOIN students s ON n.from_student_id = s.id
    WHERE n.student_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id, $user_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications • MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding-bottom: 70px; margin: 0; }
        .container-custom { width: 100%; background: white; min-height: 100vh; }
        .notification-item { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #efefef; background: white; }
        .notification-item.unread { background: #f0f8ff; }
        .notification-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; object-fit: cover; flex-shrink: 0; cursor: pointer; }
        .notification-content { flex: 1; }
        .notification-username { font-weight: 600; }
        .notification-message { color: #8e8e8e; font-size: 14px; margin-top: 2px; }
        .notification-time { color: #8e8e8e; font-size: 12px; margin-top: 2px; }
        .follow-btn-small { background-color: #0095f6; border: none; color: white; font-weight: bold; padding: 4px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; transition: 0.2s; margin-left: 8px; flex-shrink: 0; }
        .follow-btn-small.following { background-color: #efefef; color: #262626; }
        .post-preview { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; margin-left: 8px; flex-shrink: 0; cursor: pointer; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: white; border-top: 1px solid #dbdbdb; display: flex; justify-content: space-around; padding: 10px 0; z-index: 100; }
        .bottom-nav a { color: #262626; font-size: 24px; text-decoration: none; }
        .header { position: sticky; top: 0; background: white; z-index: 10; border-bottom: 1px solid #efefef; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
        .header h3 { font-size: 20px; font-weight: 600; margin: 0; }
    </style>
</head>
<body>
<div class="container-custom">
    <div class="header">
        <h3>Notifications</h3>
        <?php if (count($notifications) > 0): ?>
            <a href="?mark_read=1" class="btn btn-sm btn-outline-primary">Mark all read</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center text-muted py-5">
            <i class="far fa-bell fa-3x mb-3"></i>
            <p>No notifications yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $n):
            $isUnread = !$n['is_read'];
            $postImage = '';
            $postId = null;
            $targetUrl = '#';

            if ($n['notification_type'] == 'like' || $n['notification_type'] == 'comment') {
                if (preg_match('/[?&]post=(\d+)/', $n['link'], $matches)) {
                    $postId = $matches[1];
                } elseif (preg_match('/[?&]id=(\d+)/', $n['link'], $matches)) {
                    $postId = $matches[1];
                }
                if ($postId) {
                    $targetUrl = "post.php?id=$postId";
                    $postStmt = $db->prepare("SELECT media FROM posts WHERE id = ?");
                    $postStmt->execute([$postId]);
                    $postMedia = $postStmt->fetchColumn();
                    if ($postMedia) {
                        $firstFile = trim(explode(',', $postMedia)[0]);
                        $postImage = 'assets/uploads/posts/' . $firstFile;
                    } else {
                        $postImage = 'assets/default.png';
                    }
                } else {
                    $targetUrl = $n['link'] ?: '#';
                }
            } elseif ($n['notification_type'] == 'follow') {
                $targetUrl = "profile.php?user_id=" . $n['from_student_id'];
            }
        ?>
        <div class="notification-item <?= $isUnread ? 'unread' : '' ?>" data-notif-id="<?= $n['id'] ?>">
            <a href="profile.php?user_id=<?= $n['from_student_id'] ?>">
                <img src="assets/uploads/profiles/<?= htmlspecialchars($n['profile_pic'] ?: 'default-avatar.png') ?>" class="notification-avatar" onerror="this.src='assets/default-avatar.png'">
            </a>
            <div class="notification-content">
                <div>
                    <a href="profile.php?user_id=<?= $n['from_student_id'] ?>" style="text-decoration: none; color: inherit;">
                        <span class="notification-username"><?= htmlspecialchars($n['username']) ?></span>
                    </a>
                    <span class="notification-message">
                        <?php if ($n['notification_type'] == 'like'): ?>
                            liked your post.
                        <?php elseif ($n['notification_type'] == 'comment'): ?>
                            commented: <?= htmlspecialchars(substr($n['message'], strpos($n['message'], ':')+1)) ?>
                        <?php elseif ($n['notification_type'] == 'follow'): ?>
                            started following you.
                        <?php else: ?>
                            <?= htmlspecialchars($n['message']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="notification-time"><?= timeAgo($n['created_at']) ?></div>
            </div>
            <?php if ($n['notification_type'] == 'follow'): ?>
                <button class="follow-btn-small <?= $n['i_follow_them'] ? 'following' : '' ?>" data-user-id="<?= $n['from_student_id'] ?>">
                    <?= $n['i_follow_them'] ? 'Following' : 'Follow' ?>
                </button>
            <?php elseif ($postImage): ?>
                <a href="<?= $targetUrl ?>">
                    <img src="<?= htmlspecialchars($postImage) ?>" class="post-preview" onerror="this.src='assets/default.png'">
                </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Bottom Navigation (full width) -->
<div class="bottom-nav">
    <a href="index.php"><i class="fas fa-home"></i></a>
    <a href="#" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="fas fa-plus-square"></i></a>
    <a href="scanner.php"><i class="fas fa-qrcode"></i></a>
    <a href="profile.php"><i class="far fa-user"></i></a>
</div>

<!-- Add Post Modal (for consistency) -->
<div class="modal fade" id="addPostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create new post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="postForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <textarea name="content" class="form-control mb-2" placeholder="Write a caption..."></textarea>
                    <input type="file" name="media[]" id="mediaInput" multiple accept="image/*,video/*" class="form-control">
                    <div id="mediaPreview" class="row mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Share</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mark notification as read when clicking avatar or post preview
    $('.notification-avatar, .post-preview').on('click', function() {
        let notifId = $(this).closest('.notification-item').data('notif-id');
        if (notifId) {
            $.post('mark_notification_read.php', {id: notifId});
        }
    });

    // Follow/Unfollow from notification (AJAX)
    $(document).on('click', '.follow-btn-small', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let btn = $(this);
        let userId = btn.data('user-id');
        $.post('follow_handler.php', {user_id: userId}, function(res) {
            if (res.following) {
                btn.text('Following').addClass('following');
            } else {
                btn.text('Follow').removeClass('following');
            }
        }, 'json');
    });

    // Add post modal scripts (same as index)
    $('#mediaInput').on('change', function() {
        let files = $(this)[0].files, preview = $('#mediaPreview'); preview.empty();
        for(let i=0; i<files.length; i++) {
            let file = files[i], reader = new FileReader();
            reader.onload = function(e) {
                let div = $('<div class="col-4 mb-2"></div>');
                if(file.type.startsWith('image/')) div.html('<img src="'+e.target.result+'" class="img-fluid rounded">');
                else if(file.type.startsWith('video/')) div.html('<video controls class="img-fluid rounded"><source src="'+e.target.result+'"></video>');
                preview.append(div);
            }
            reader.readAsDataURL(file);
        }
    });
    $('#postForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: 'add_post.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.trim() === 'success') {
                    $('#addPostModal').modal('hide');
                    location.reload();
                } else alert('Server error: ' + res);
            },
            error: function(xhr) { alert('Upload failed: ' + xhr.responseText); }
        });
    });
</script>
</body>
</html>