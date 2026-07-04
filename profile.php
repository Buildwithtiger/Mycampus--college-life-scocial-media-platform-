<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$profile_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id;
$isOwner = ($profile_id == $user_id);

// Fetch profile
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$profile_id]);
$profile = $stmt->fetch();
if (!$profile) { echo "User not found"; exit; }

// Check blocks
$blockCheck = $db->prepare("SELECT id FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
$blockCheck->execute([$user_id, $profile_id, $profile_id, $user_id]);
$blocked = $blockCheck->rowCount() > 0;

// Check if current user follows this profile
$followStatus = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$followStatus->execute([$user_id, $profile_id]);
$isFollowing = $followStatus->rowCount() > 0;

// Check if profile owner follows current user (they follow me)
$theyFollowMe = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$theyFollowMe->execute([$profile_id, $user_id]);
$theyFollowBack = $theyFollowMe->rowCount() > 0;

// Determine if viewer can see posts
$canView = true;
if ($profile['privacy'] == 'private' && !$isOwner) {
    // For private account, need mutual follow: viewer follows owner AND owner follows viewer
    $canView = ($isFollowing && $theyFollowBack);
}
if ($blocked) $canView = false;

// Count followers
$followersStmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followersStmt->execute([$profile_id]);
$followers = $followersStmt->fetchColumn();

// Count following
$followingStmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$followingStmt->execute([$profile_id]);
$following = $followingStmt->fetchColumn();

// Count posts
$postsCountStmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$postsCountStmt->execute([$profile_id]);
$postsCount = $postsCountStmt->fetchColumn();

// Get posts (only if canView)
$posts = [];
if ($canView) {
    $postsStmt = $db->prepare("
        SELECT id, media, created_at
        FROM posts
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $postsStmt->execute([$profile_id]);
    $posts = $postsStmt->fetchAll();
}

function getFirstMediaUrl($mediaStr) {
    if (empty($mediaStr)) return 'assets/default.png';
    $firstFile = trim(explode(',', $mediaStr)[0]);
    return 'assets/uploads/posts/' . $firstFile;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($profile['username']) ?> - MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding-bottom: 70px; }
        .profile-header { padding: 30px 0; display: flex; align-items: center; gap: 30px; flex-wrap: wrap; }
        .profile-pic { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .stats { display: flex; gap: 30px; margin: 15px 0; }
        .post-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; }
        .post-grid-item { aspect-ratio: 1 / 1; overflow: hidden; cursor: pointer; }
        .post-grid-item img { width: 100%; height: 100%; object-fit: cover; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; border-top: 1px solid #dbdbdb; display: flex; justify-content: space-around; padding: 10px 0; z-index: 100; }
        .bottom-nav a { color: #262626; font-size: 24px; }
        .follow-btn { background-color: #0095f6; border: none; color: white; font-weight: bold; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .follow-btn.following { background-color: #efefef; color: #262626; }
        @media (max-width: 768px) {
            .profile-header { flex-direction: column; text-align: center; gap: 15px; }
            .stats { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <img src="assets/uploads/profiles/<?= htmlspecialchars($profile['profile_pic'] ?: 'default-avatar.png') ?>" class="profile-pic" onerror="this.src='assets/default-avatar.png'">
            <div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h2><?= htmlspecialchars($profile['username']) ?></h2>
                    <?php if(!$isOwner && !$blocked): ?>
                        <button id="followBtn" class="follow-btn <?= $isFollowing ? 'following' : '' ?>" data-user-id="<?= $profile_id ?>">
                            <?= $isFollowing ? 'Following' : 'Follow' ?>
                        </button>
                    <?php endif; ?>
                    <?php if($isOwner): ?>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit profile</button>
                        <div class="dropdown">
                            <button class="btn" data-bs-toggle="dropdown"><i class="fas fa-bars"></i></button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="stats mt-2">
                    <div><strong><?= $postsCount ?></strong> posts</div>
                    <div><strong id="followersCount"><?= $followers ?></strong> followers</div>
                    <div><strong><?= $following ?></strong> following</div>
                </div>
                <div class="mt-2"><strong><?= htmlspecialchars($profile['real_name']) ?></strong></div>
                <div><?= nl2br(htmlspecialchars($profile['bio'])) ?></div>
                <div><?= htmlspecialchars($profile['class']) ?> • <?= htmlspecialchars($profile['year']) ?></div>
            </div>
        </div>

        <?php if($canView): ?>
        <div class="post-grid">
            <?php if (empty($posts)): ?>
                <div class="alert alert-info text-center">No posts yet.</div>
            <?php else: ?>
                <?php foreach($posts as $post): ?>
                    <div class="post-grid-item" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                        <img src="<?= htmlspecialchars(getFirstMediaUrl($post['media'])) ?>" alt="Post">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <div class="alert alert-info">This account is private. Only mutual followers can see posts.</div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editProfileForm" enctype="multipart/form-data"><div class="modal-body">
            <div class="mb-3">
                <label>Profile Picture</label>
                <input type="file" name="profile_pic" class="form-control" accept="image/*">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_pic" id="removePicCheck">
                    <label class="form-check-label" for="removePicCheck">Remove current photo (revert to default)</label>
                </div>
            </div>
            <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($profile['username']) ?>"></div>
            <div class="mb-3"><label>Bio</label><textarea name="bio" class="form-control" maxlength="250" rows="3"><?= htmlspecialchars($profile['bio']) ?></textarea></div>
            <div class="mb-3"><label>Gender</label><select name="gender" class="form-control"><option value="">Prefer not to say</option><option value="Male" <?= $profile['gender']=='Male'?'selected':'' ?>>Male</option><option value="Female" <?= $profile['gender']=='Female'?'selected':'' ?>>Female</option></select></div>
            <div class="mb-3"><label>Class</label><input type="text" name="class" class="form-control" value="<?= htmlspecialchars($profile['class']) ?>"></div>
            <div class="mb-3"><label>Year</label><select name="year" class="form-control"><option>First Year</option><option>Second Year</option><option>Third Year</option><option>Fourth Year</option></select></div>
        </div><div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div></form>
        </div></div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="fas fa-plus-square"></i></a>
        <a href="scanner.php"><i class="fas fa-qrcode"></i></a>
        <a href="profile.php"><i class="far fa-user"></i></a>
    </div>

    <!-- Add Post Modal -->
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
        $('#followBtn').click(function() {
            let btn = $(this), userId = btn.data('user-id');
            $.post('follow_handler.php', {user_id: userId}, function(res) {
                if(res.following) {
                    btn.text('Following').addClass('following');
                } else {
                    btn.text('Follow').removeClass('following');
                }
                $('#followersCount').text(res.followers_count);
                // Reload the page to reflect new follow status (posts become visible only if mutual)
                location.reload();
            }, 'json');
        });
        $('#editProfileForm').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({ url: 'update_profile.php', type: 'POST', data: formData, processData: false, contentType: false, success: function(res) { if(res === 'success') location.reload(); else alert('Error: '+res); } });
        });
        $('#postForm').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({ url: 'add_post.php', type: 'POST', data: formData, processData: false, contentType: false, success: function(res) { if(res.trim() === 'success') { $('#addPostModal').modal('hide'); location.reload(); } else alert('Error: '+res); } });
        });
        $('#mediaInput').on('change', function() {
            let files = $(this)[0].files, preview = $('#mediaPreview'); preview.empty();
            for(let i=0; i<files.length; i++) {
                let file = files[i], reader = new FileReader();
                reader.onload = function(e) {
                    let div = $('<div class="col-4 mb-2"></div>');
                    if(file.type.startsWith('image/')) div.html('<img src="'+e.target.result+'" class="img-fluid">');
                    else div.html('<video controls class="img-fluid"><source src="'+e.target.result+'"></video>');
                    preview.append(div);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>