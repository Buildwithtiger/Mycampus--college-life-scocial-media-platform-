<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');
$db = Database::getInstance()->getConnection();
$departments = ['ART','COM','SCI','BCA','BBA(CA)','BSC(CS)','DIPLOMA','MBA','HSVC','OTHER'];
$leaderboard = $db->query("SELECT l.*, s.username, s.profile_pic FROM leaderboard l JOIN students s ON l.student_id = s.id ORDER BY total_points DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Leaderboard</title><meta name="viewport" content="width=device-width, initial-scale=1"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#fafafa;}.leaderboard-item{display:flex;align-items:center;padding:12px;background:white;margin-bottom:8px;border-radius:8px;}.rank{width:50px;font-weight:bold;}.rank-1{color:gold;}.rank-2{color:silver;}.rank-3{color:#cd7f32;}</style></head>
<body><div class="container mt-3"><h3>Overall Leaderboard</h3><?php foreach($leaderboard as $i=>$row): ?><div class="leaderboard-item"><div class="rank <?= $i==0?'rank-1':($i==1?'rank-2':($i==2?'rank-3':'')) ?>">#<?= $i+1 ?></div><img src="assets/uploads/<?= $row['profile_pic'] ?>" width="40" height="40" class="rounded-circle me-2"><div><strong><?= htmlspecialchars($row['username']) ?></strong><br><small><?= $row['department'] ?></small></div><div class="ms-auto"><strong><?= $row['total_points'] ?></strong> pts</div></div><?php endforeach; ?></div></body></html>