<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle post deletion
if (isset($_POST['delete_post'])) {
    $check = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check->execute([$post_id]);
    $owner = $check->fetchColumn();
    if ($owner == $user_id) {
        $del = $db->prepare("DELETE FROM posts WHERE id = ?");
        $del->execute([$post_id]);
        header("Location: profile.php");
        exit;
    }
}

// Handle comment deletion
if (isset($_POST['delete_comment'])) {
    $comment_id = (int)$_POST['comment_id'];
    $check = $db->prepare("
        SELECT c.id 
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        WHERE c.id = ? AND (c.student_id = ? OR p.user_id = ?)
    ");
    $check->execute([$comment_id, $user_id, $user_id]);
    if ($check->fetch()) {
        $del = $db->prepare("DELETE FROM comments WHERE id = ?");
        $del->execute([$comment_id]);
    }
    header("Location: post.php?id=" . $post_id);
    exit;
}

// Handle edit post (caption update)
if (isset($_POST['edit_post'])) {
    $new_content = trim($_POST['content']);
    $check = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check->execute([$post_id]);
    $owner = $check->fetchColumn();
    if ($owner == $user_id) {
        $update = $db->prepare("UPDATE posts SET content = ? WHERE id = ?");
        $update->execute([$new_content, $post_id]);
    }
    header("Location: post.php?id=" . $post_id);
    exit;
}

// Fetch current post with author details
$stmt = $db->prepare("
    SELECT p.*, s.username, s.profile_pic
    FROM posts p
    JOIN students s ON p.user_id = s.id
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) die("Post not found.");

// Check if current user liked this post
$likeCheck = $db->prepare("SELECT id FROM likes WHERE post_id = ? AND student_id = ?");
$likeCheck->execute([$post_id, $user_id]);
$userLiked = $likeCheck->rowCount() > 0;

// Fetch comments
$commentsStmt = $db->prepare("
    SELECT c.*, s.username, s.profile_pic
    FROM comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$commentsStmt->execute([$post_id]);
$comments = $commentsStmt->fetchAll();

// Helper for media display
function displayMedia($mediaStr) {
    if (empty($mediaStr)) return '';
    $files = explode(',', $mediaStr);
    $html = '';
    foreach ($files as $file) {
        $file = trim($file);
        if (empty($file)) continue;
        $path = 'assets/uploads/posts/' . $file;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $html .= '<img src="'.$path.'" class="post-media img-fluid mb-2" style="max-height:500px; width:100%; object-fit:contain;">';
        } elseif (in_array($ext, ['mp4','mov','avi','webm','ogg'])) {
            $html .= '<video controls class="post-media mb-2" style="max-height:500px; width:100%;"><source src="'.$path.'"></video>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($post['username']) ?>'s post - MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fafafa; padding-bottom: 70px; }
        .post-container { max-width: 600px; margin: 20px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; }
        .post-header { display: flex; align-items: center; justify-content: space-between; padding: 12px; }
        .post-left { display: flex; align-items: center; gap: 10px; }
        .post-header img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .post-header .username { font-weight: 600; }
        .dropdown-toggle::after { display: none; }
        .dropdown-toggle i { font-size: 20px; cursor: pointer; }
        .post-actions { padding: 8px 12px; display: flex; gap: 16px; align-items: center; }
        .like-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
        .liked { color: #ed4956; }
        .comment-section { padding: 12px; border-top: 1px solid #efefef; }
        .comment { display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; }
        .comment .comment-text { flex: 1; margin-left: 10px; }
        .comment .delete-comment { color: red; font-size: 12px; cursor: pointer; background: none; border: none; }
        .other-posts-section { margin: 20px auto; max-width: 600px; }
        .other-post-card { background: white; border: 1px solid #dbdbdb; border-radius: 8px; margin-bottom: 20px; }
        .other-post-header { display: flex; align-items: center; padding: 12px; }
        .other-post-header img { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; }
        .other-post-media { width: 100%; max-height: 300px; object-fit: cover; }
        .post-actions button { background: none; border: none; font-size: 20px; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; border-top: 1px solid #dbdbdb; display: flex; justify-content: space-around; padding: 10px 0; z-index: 100; }
        .loader { text-align: center; padding: 20px; display: none; }
        .comment-item { display: flex; align-items: start; margin-bottom: 12px; gap: 10px; }
        .comment-item .comment-text { flex: 1; }
        .comment-item .delete-feed-comment { margin-left: auto; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Current post -->
        <div class="post-container">
            <div class="post-header">
                <div class="post-left">
                    <img src="assets/uploads/profiles/<?= htmlspecialchars($post['profile_pic'] ?: 'default-avatar.png') ?>" onerror="this.src='assets/default-avatar.png'">
                    <span class="username"><?= htmlspecialchars($post['username']) ?></span>
                </div>
                <?php if ($post['user_id'] == $user_id): ?>
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editPostModal">Edit Post</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" onsubmit="return confirm('Delete this post permanently?');">
                                <button type="submit" name="delete_post" class="dropdown-item text-danger">Delete Post</button>
                            </form>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <div>
                <?= displayMedia($post['media']) ?>
            </div>
            <div class="post-actions">
                <button class="like-btn <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post_id ?>">
                    <i class="<?= $userLiked ? 'fas fa-heart' : 'far fa-heart' ?>"></i> 
                    <span id="like-count"><?= $db->query("SELECT COUNT(*) FROM likes WHERE post_id = $post_id")->fetchColumn() ?></span>
                </button>
            </div>
            <div class="px-3 pb-2">
                <strong><?= htmlspecialchars($post['username']) ?></strong> <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>

            <div class="comment-section">
                <h6>Comments</h6>
                <div id="comment-list">
                    <?php foreach ($comments as $c): ?>
                        <div class="comment" data-comment-id="<?= $c['id'] ?>">
                            <img src="assets/uploads/profiles/<?= htmlspecialchars($c['profile_pic'] ?: 'default-avatar.png') ?>" width="30" height="30" class="rounded-circle">
                            <div class="comment-text">
                                <strong><?= htmlspecialchars($c['username']) ?></strong><br>
                                <?= nl2br(htmlspecialchars($c['comment'])) ?>
                            </div>
                            <?php if ($c['student_id'] == $user_id || $post['user_id'] == $user_id): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="delete_comment" class="delete-comment btn btn-link btn-sm text-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <textarea id="commentText" class="form-control mt-2" placeholder="Add a comment..."></textarea>
                <button id="submitComment" class="btn btn-primary btn-sm mt-2">Post Comment</button>
            </div>
        </div>

        <!-- Other posts by the same user -->
        <div class="other-posts-section">
            <h5 class="mb-3">More from <?= htmlspecialchars($post['username']) ?></h5>
            <div id="otherPostsContainer"></div>
            <div class="loader" id="loader">Loading more posts...</div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <label for="editContent" class="form-label">Caption</label>
                        <textarea name="content" id="editContent" class="form-control" rows="4" required><?= htmlspecialchars($post['content']) ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_post" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Feed Comment Modal -->
    <div class="modal fade" id="feedCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="feedCommentList"></div>
                    <textarea id="feedCommentText" class="form-control mt-2" placeholder="Add a comment..."></textarea>
                    <input type="hidden" id="feedCommentPostId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="submitFeedComment">Post Comment</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="fas fa-plus-square"></i></a>
        <a href="scanner.php"><i class="fas fa-qrcode"></i></a>
        <a href="chat.php"><i class="far fa-comment-dots"></i></a>
        <a href="profile.php"><i class="far fa-user"></i></a>
    </div>

    <!-- Add Post Modal (keep as is) -->
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
        let page = 1;
        let loading = false;
        let hasMore = true;
        const userId = <?= $post['user_id'] ?>;
        const currentPostId = <?= $post_id ?>;

        function loadOtherPosts() {
            if (loading || !hasMore) return;
            loading = true;
            $('#loader').show();
            $.get('get_user_posts.php', {user_id: userId, page: page, exclude: currentPostId}, function(data) {
                if (data.trim() === "") {
                    hasMore = false;
                    $('#loader').hide();
                } else {
                    $('#otherPostsContainer').append(data);
                    page++;
                    loading = false;
                    $('#loader').hide();
                }
            }).fail(function() {
                loading = false;
                $('#loader').hide();
            });
        }

        // Infinite scroll on window
        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 200) {
                loadOtherPosts();
            }
        });

        // Initial load
        loadOtherPosts();

        // Like functionality for current post
        $('.like-btn').click(function() {
            let btn = $(this), postId = btn.data('post-id');
            $.post('like_handler.php', {post_id: postId}, function(res) {
                if(res.liked) {
                    btn.addClass('liked');
                    btn.find('i').removeClass('far').addClass('fas');
                } else {
                    btn.removeClass('liked');
                    btn.find('i').removeClass('fas').addClass('far');
                }
                $('#like-count').text(res.count);
            }, 'json');
        });

        // Add comment via AJAX for current post
        $('#submitComment').click(function() {
            let comment = $('#commentText').val();
            if(comment.trim() === '') return;
            $.post('comment_handler.php', {post_id: <?= $post_id ?>, comment: comment}, function(res) {
                if(res.status === 'success') {
                    $('#commentText').val('');
                    $.get('get_comments.php', {post_id: <?= $post_id ?>}, function(data) {
                        $('#comment-list').html(data);
                    });
                } else alert('Error: ' + res.message);
            }, 'json');
        });

        // ========== FEED COMMENTS (for "More from" posts) ==========
        $(document).on('click', '.comment-btn-feed', function() {
            let postId = $(this).data('post-id');
            $('#feedCommentPostId').val(postId);
            loadFeedComments(postId);
            $('#feedCommentModal').modal('show');
        });

        function loadFeedComments(postId) {
            $.get('get_feed_comments.php', {post_id: postId}, function(data) {
                $('#feedCommentList').html(data);
            });
        }

        $('#submitFeedComment').click(function() {
            let postId = $('#feedCommentPostId').val();
            let comment = $('#feedCommentText').val();
            if (comment.trim() === '') return;
            $.post('add_feed_comment.php', {post_id: postId, comment: comment}, function(res) {
                if (res.status === 'success') {
                    $('#feedCommentText').val('');
                    loadFeedComments(postId);
                    $('.comment-btn-feed[data-post-id="' + postId + '"] .comment-count').text(res.new_count);
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        });

        $(document).on('click', '.delete-feed-comment', function() {
            let commentId = $(this).data('comment-id');
            let postId = $(this).data('post-id');
            if (confirm('Delete this comment?')) {
                $.post('delete_feed_comment.php', {comment_id: commentId, post_id: postId}, function(res) {
                    if (res.success) {
                        loadFeedComments(postId);
                        $('.comment-btn-feed[data-post-id="' + postId + '"] .comment-count').text(res.new_count);
                    } else {
                        alert(res.message || 'Delete failed');
                    }
                }, 'json');
            }
        });

        // Edit/Delete handlers for other posts (AJAX)
        $(document).on('click', '.delete-other-post', function() {
            let postId = $(this).data('post-id');
            if (confirm('Delete this post permanently?')) {
                $.post('delete_post_ajax.php', {post_id: postId}, function(res) {
                    if (res.success) {
                        $('.other-post-card[data-post-id="' + postId + '"]').remove();
                    } else {
                        alert(res.message || 'Delete failed');
                    }
                }, 'json');
            }
        });

        $(document).on('click', '.edit-other-post', function() {
            let postId = $(this).data('post-id');
            let content = $(this).data('content');
            $('#editOtherPostId').val(postId);
            $('#editOtherContent').val(content);
            $('#editOtherPostModal').modal('show');
        });

        $('#saveOtherEdit').click(function() {
            let postId = $('#editOtherPostId').val();
            let newContent = $('#editOtherContent').val();
            if (newContent.trim() === '') return;
            $.post('edit_post_ajax.php', {post_id: postId, content: newContent}, function(res) {
                if (res.success) {
                    let card = $('.other-post-card[data-post-id="' + postId + '"]');
                    let username = card.find('.username').text();
                    card.find('.px-3.pb-2').html('<strong>' + username + '</strong> ' + newContent.replace(/\n/g, '<br>'));
                    $('#editOtherPostModal').modal('hide');
                } else {
                    alert(res.message || 'Edit failed');
                }
            }, 'json');
        });

        // Media preview for Add Post Modal
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