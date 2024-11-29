<?php
session_start();
ob_start(); // Start output buffering

header('Content-Type: application/json');

include('db.php');
include('Functions.php');
include('notification.php');

// Ensure the user is logged in
if (!isset($_SESSION['UserId'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$UserId = $_SESSION['UserId'];

$query = "SELECT Title, Date, Amount FROM assets WHERE UserId = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $UserId);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => $row['Title'],
        'start' => $row['Date'],
        'end' => $row['Date'],
        'names' => number_format($row['Amount'], 2),
    ];
}

$stmt->close();
$mysqli->close();

// Clear output buffer and return JSON
ob_end_clean(); 
echo json_encode($events);
exit;
