<?php
require_once 'config.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$scanned = isset($_POST['scanned_data']) ? trim($_POST['scanned_data']) : '';

if (empty($scanned)) {
    echo json_encode(['status' => 'error', 'message' => 'No data scanned']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Unknown QR code'];

// -------------- -------------
// Define your QR code logic here.
// Examples:
// 1. Attendance for an event: "event:123"
// 2. View user profile: user:45
// 3. Direct URL: https://...
// 4. Custom JSON or plain text
// -------------- --------------

// Example 1: Event attendance
if (preg_match('/^event:(\d+)$/i', $scanned, $matches)) {
    $event_id = (int)$matches[1];
    $db = Database::getInstance()->getConnection();
    
    // Check if event exists and is not in the past
    $stmt = $db->prepare("SELECT id, title FROM events WHERE id = ? AND event_date >= CURDATE()");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        // Record attendance (avoid duplicates)
        $checkStmt = $db->prepare("SELECT id FROM event_attendees WHERE event_id = ? AND user_id = ?");
        $checkStmt->execute([$event_id, $user_id]);
        if (!$checkStmt->fetch()) {
            $insert = $db->prepare("INSERT INTO event_attendees (event_id, user_id, scanned_at) VALUES (?, ?, NOW())");
            $insert->execute([$event_id, $user_id]);
            $response = [
                'status' => 'success',
                'message' => "✅ Checked in to event: " . htmlspecialchars($event['title']),
                'redirect' => "event_details.php?id=$event_id"
            ];
        } else {
            $response = [
                'status' => 'success',
                'message' => "You have already checked in to " . htmlspecialchars($event['title'])
            ];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Event not found or expired'];
    }
}
// Example 2: Show user profile
elseif (preg_match('/^user:(\d+)$/i', $scanned, $matches)) {
    $profile_id = (int)$matches[1];
    $response = [
        'status' => 'success',
        'message' => "Viewing user profile",
        'redirect' => "profile.php?id=$profile_id"
    ];
}
// Example 3: Plain URL – open in new tab (or redirect)
elseif (filter_var($scanned, FILTER_VALIDATE_URL)) {
    $response = [
        'status' => 'success',
        'message' => "Opening link...",
        'redirect' => $scanned
    ];
}
// Example 4: Custom campus QR (e.g., "campus:library")
elseif (strpos($scanned, 'campus:') === 0) {
    $place = substr($scanned, 7);
    $response = [
        'status' => 'success',
        'message' => "You scanned a QR for: " . htmlspecialchars($place),
        'action_html' => '<a href="map.php?location=' . urlencode($place) . '" class="btn btn-primary mt-2">Show on Map</a>'
    ];
}
// Default: just show the scanned text 
else {
    $response = [
        'status' => 'info',
        'message' => "Scanned data: " . htmlspecialchars($scanned)
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>