<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Fetch current user's profile picture from database
$stmt = $db->prepare("SELECT profile_pic FROM students WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($user['profile_pic']) ? 'assets/uploads/profiles/' . $user['profile_pic'] : 'assets/default-avatar.png';

// Fetch upcoming events
$slides = $db->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
        /* Your existing styles (keep as is) */
        body { background-color: #fafafa; padding-bottom: 70px; }
        .navbar { background: white; border-bottom: 1px solid #dbdbdb; position: sticky; top: 0; z-index: 100; }
        .search-bar { background-color: #efefef; border-radius: 8px; padding: 8px 12px; border: none; width: 250px; }
        .event-carousel { border-radius: 12px; overflow: hidden; margin-bottom: 20px; background: #f0f0f0; }
        .carousel-item { height: 320px; }
        .carousel-item img { width: 100%; height: 100%; object-fit: cover; }
        .carousel-caption { background: linear-gradient(to top, rgba(0,0,0,0.6), transparent); bottom: 0; left: 0; right: 0; text-align: left; padding: 20px; }
        .carousel-caption h5 { color: white; font-weight: 600; }
        .carousel-fallback { background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; height: 100%; font-size: 1.5rem; font-weight: bold; }
        .feed-post { background: white; border: 1px solid #dbdbdb; border-radius: 8px; margin-bottom: 20px; }
        .post-header { display: flex; align-items: center; padding: 12px; }
        .post-header img { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; object-fit: cover; }
        .post-header .username { font-weight: 600; }
        .post-media { width: 100%; max-height: 500px; object-fit: cover; }
        .post-actions { padding: 8px 12px; display: flex; gap: 16px; }
        .post-actions button { background: none; border: none; font-size: 24px; cursor: pointer; }
        .liked { color: #ed4956; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; border-top: 1px solid #dbdbdb; display: flex; justify-content: space-around; padding: 10px 0; z-index: 100; }
        .bottom-nav a { color: #262626; font-size: 24px; }
        .search-results { position: absolute; top: 60px; background: white; width: 300px; border: 1px solid #dbdbdb; border-radius: 8px; z-index: 1000; display: none; }
        .search-result-item { display: flex; align-items: center; padding: 8px; border-bottom: 1px solid #efefef; text-decoration: none; color: #262626; }
        .search-result-item img { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; }
        @media (max-width: 768px) { .carousel-item { height: 200px; } }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; border-top: 1px solid #dbdbdb; display: flex; justify-content: space-around; padding: 10px 0; z-index: 100; }
.bottom-nav a { color: #262626; font-size: 24px; }

    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">MyCampus</a>
            <div style="position: relative;">
                <input type="text" id="searchInput" class="search-bar" placeholder="Search">
                <div id="searchResults" class="search-results"></div>
            </div>
            <div>
                <a href="notifications.php" class="me-3"><i class="far fa-bell"></i></a>
                <a href="profile.php"><img src="<?= htmlspecialchars($profilePic) ?>" width="30" height="30" class="rounded-circle" onerror="this.src='assets/default-avatar.png'"></a>
            </div>
        </div>
    </nav>

    <div class="container mt-3">
        <div id="eventCarousel" class="carousel slide event-carousel mb-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php if (count($slides) > 0): ?>
                    <?php foreach($slides as $i => $slide): ?>
                        <div class="carousel-item <?= $i == 0 ? 'active' : '' ?>">
                            <?php if (!empty($slide['image_url'])): ?>
                                <img src="<?= htmlspecialchars($slide['image_url']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>">
                            <?php else: ?>
                                <div class="carousel-fallback"><?= htmlspecialchars($slide['title']) ?></div>
                            <?php endif; ?>
                            <div class="carousel-caption d-none d-md-block">
                                <h5><?= htmlspecialchars($slide['title']) ?></h5>
                                <p><?= htmlspecialchars($slide['description']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="carousel-item active"><div class="carousel-fallback">No upcoming events</div></div>
                <?php endif; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        </div>

        <div id="feed"></div>
    </div>

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

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Comments</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div id="commentList"></div>
                    <textarea id="commentText" class="form-control mt-2" placeholder="Add a comment..."></textarea>
                    <input type="hidden" id="commentPostId">
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" id="submitComment">Post</button></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let page = 1, loading = false;

        function loadPosts() {
            if(loading) return;
            loading = true;
            $.get('get_posts.php', {page: page}, function(data) {
                if(data && data.trim() !== "") {
                    $('#feed').append(data);
                    page++;
                    loading = false;
                } else {
                    loading = true;
                }
            }).fail(function() { loading = false; });
        }

        $(document).ready(function() {
            loadPosts();
            $(window).scroll(function() {
                if($(window).scrollTop() + $(window).height() > $(document).height() - 100) loadPosts();
            });
        });

        $('#searchInput').on('input', function() {
            let q = $(this).val();
            if(q.length > 1) {
                $.get('search_handler.php', {q: q}, function(data) {
                    $('#searchResults').html(data).show();
                });
            } else $('#searchResults').hide();
        });
        $(document).click(function(e) {
            if(!$(e.target).closest('#searchInput, #searchResults').length) $('#searchResults').hide();
        });

        $(document).on('click', '.like-btn', function() {
            let btn = $(this), postId = btn.data('post-id');
            $.post('like_handler.php', {post_id: postId}, function(res) {
                if(res.liked) {
                    btn.addClass('liked');
                    btn.find('i').removeClass('far').addClass('fas');
                } else {
                    btn.removeClass('liked');
                    btn.find('i').removeClass('fas').addClass('far');
                }
                btn.find('.like-count').text(res.count);
            }, 'json');
        });

        $(document).on('click', '.comment-btn', function() {
            let postId = $(this).data('post-id');
            $('#commentPostId').val(postId);
            $.get('get_comments.php', {post_id: postId}, function(data) {
                $('#commentList').html(data);
            });
            $('#commentModal').modal('show');
        });

        $('#submitComment').click(function() {
            let postId = $('#commentPostId').val();
            let comment = $('#commentText').val();
            if(comment.trim() == '') return;
            $.post('comment_handler.php', {post_id: postId, comment: comment}, function(res) {
                if(res.status === 'success') {
                    $('#commentText').val('');
                    $.get('get_comments.php', {post_id: postId}, function(data) {
                        $('#commentList').html(data);
                    });
                } else alert('Error posting comment');
            }, 'json');
        });

        $('#mediaInput').on('change', function() {
            let files = $(this)[0].files, preview = $('#mediaPreview');
            preview.empty();
            for(let i = 0; i < files.length; i++) {
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
                        $('#feed').empty();
                        page = 1;
                        loadPosts();
                        $('#postForm')[0].reset();
                        $('#mediaPreview').empty();
                    } else alert('Server error: ' + res);
                },
                error: function(xhr) { alert('Upload failed: ' + xhr.responseText); }
            });
        });
    </script>
</body>
</html>