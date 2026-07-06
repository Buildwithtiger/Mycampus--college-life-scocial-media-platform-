<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$qr = sanitize($_POST['qr']);
$dept = $db->prepare("SELECT id FROM departments WHERE dept_code = ?");
$dept->execute([$qr]);
$deptRow = $dept->fetch();
if (!$deptRow) { echo json_encode(['status' => 'error', 'message' => 'Invalid QR code']); exit; }
$dept_id = $deptRow['id'];
$today = date('Y-m-d');
$check = $db->prepare("SELECT id FROM attendance_records WHERE student_id = ? AND department_id = ? AND entry_date = ?");
$check->execute([$user_id, $dept_id, $today]);
if ($check->rowCount() > 0) { echo json_encode(['status' => 'error', 'message' => 'You have already marked attendance for this department today']); exit; }
$db->prepare("INSERT INTO attendance_records (student_id, department_id, entry_date, entry_time) VALUES (?, ?, ?, ?)")->execute([$user_id, $dept_id, $today, date('H:i:s')]);
echo json_encode(['status' => 'success', 'message' => 'Attendance recorded successfully']);