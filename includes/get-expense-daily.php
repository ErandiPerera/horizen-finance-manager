<?php

session_start();
ob_start(); // Start output buffering to prevent unwanted output
header('Content-Type: application/json');

// Include necessary files
include('db.php');
include('Functions.php');
include('notification.php');

// Ensure the user is logged in
if (!isset($_SESSION['UserId'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$UserId = $_SESSION['UserId'];

// Prepare the query to fetch bills data
$query = "SELECT Title, Dates, Amount FROM bills WHERE UserId = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $UserId);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Database query execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$events = [];
$sum = 0;

while ($row = $result->fetch_assoc()) {
    $start = $row['Dates'];
    $end = $row['Dates'];
    $amount = number_format($row['Amount'], 2);
    $title = $row['Title'];
    $sum += $row['Amount'];

    $events[] = [
        'title' => $title,
        'start' => $start,
        'end' => $end,
        'names' => $amount,
    ];
}

$stmt->close();
$mysqli->close();

// Add the total sum to the events array if needed
$eventsArray['sum'] = $sum;

// Clear the output buffer to remove any accidental output
ob_end_clean();
echo json_encode($events);
exit;

?>
