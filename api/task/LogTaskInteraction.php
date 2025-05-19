<?php
require_once "../../config/SessionInit.php";
require_once "../../config/database.php";

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON data from request body
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate required fields
if (!isset($data['task_id']) || !isset($data['interaction_type'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Initialize response
$response = ['success' => false];

try {
    // Extract data
    $taskId = intval($data['task_id']);
    $interactionType = $data['interaction_type'];
    $userId = $_SESSION['user_id'];
    $activityTime = date('Y-m-d H:i:s');
    
    // Create details object
    $details = [
        'element_id' => $data['element_id'] ?? '',
        'element_type' => $data['element_type'] ?? '',
        'client_timestamp' => $data['timestamp'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Convert details to JSON
    $detailsJson = json_encode($details);
    
    // Map client interaction types to activity types
    $activityTypeMap = [
        'view_description' => 'task_detail_viewed',
        'change_priority' => 'task_priority_changed',
        'change_date' => 'task_date_changed',
        'page_loaded' => 'task_detail_viewed',
        'back_to_project' => 'task_navigation',
        'member_added' => 'task_assigned',
        'member_removed' => 'task_unassigned'
    ];
    
    $activityType = $activityTypeMap[$interactionType] ?? 'task_updated';
    
    // Insert into ActivityLog
    $stmt = $connect->prepare("
        INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $connect->error);
    }
    
    $stmt->bind_param("isiss", $userId, $activityType, $taskId, $activityTime, $detailsJson);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    // Update response
    $response = [
        'success' => true,
        'message' => 'Interaction logged successfully',
        'log_id' => $connect->insert_id
    ];
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    // Log error server-side
    error_log("Error logging task interaction: " . $e->getMessage());
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 