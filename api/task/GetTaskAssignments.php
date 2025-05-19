<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "../../config/SessionInit.php";
require_once "../../config/database.php";

// Set JSON content type
header('Content-Type: application/json');

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => "Error: $errstr in $errfile on line $errline",
        'error_type' => 'php_error'
    ];
    echo json_encode($response);
    exit;
}

// Set the error handler
set_error_handler('handleError');

try {
    // Ensure request is GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    // Ensure user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Get task_id from query params
    if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing task_id parameter']);
        exit;
    }
    
    // Extract task_id
    $taskId = intval($_GET['task_id']);
    $currentUserId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    // Validate database connection
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection not established"));
    }
    
    // Check if task exists and get project ID
    $checkTask = $connect->prepare("
        SELECT t.TaskID, t.ProjectID 
        FROM Task t 
        WHERE t.TaskID = ?
    ");
    
    if (!$checkTask) {
        throw new Exception("Failed to prepare task check: " . $connect->error);
    }
    
    $checkTask->bind_param("i", $taskId);
    
    if (!$checkTask->execute()) {
        throw new Exception("Failed to execute task check: " . $checkTask->error);
    }
    
    $taskResult = $checkTask->get_result();
    if ($taskResult->num_rows === 0) {
        throw new Exception("Task not found");
    }
    
    $taskData = $taskResult->fetch_assoc();
    $projectId = $taskData['ProjectID'];
    
    // Verify user has permission to view the task (must be a project member or an admin)
    if (!$isAdmin) {
        $checkPermission = $connect->prepare("
            SELECT COUNT(*) as isMember 
            FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ?
        ");
        
        if (!$checkPermission) {
            throw new Exception("Failed to prepare permission check: " . $connect->error);
        }
        
        $checkPermission->bind_param("ii", $projectId, $currentUserId);
        
        if (!$checkPermission->execute()) {
            throw new Exception("Failed to execute permission check: " . $checkPermission->error);
        }
        
        $permissionResult = $checkPermission->get_result()->fetch_assoc();
        if (!$permissionResult || $permissionResult['isMember'] == 0) {
            throw new Exception("You don't have permission to view this task");
        }
    }
    
    // Get task assignments
    $getAssignments = $connect->prepare("
        SELECT 
            ta.TaskID,
            ta.UserID,
            ta.AssignedBy,
            u.FullName,
            u.Avatar
        FROM TaskAssignment ta
        JOIN Users u ON u.UserID = ta.UserID
        WHERE ta.TaskID = ?
    ");
    
    if (!$getAssignments) {
        throw new Exception("Failed to prepare assignments query: " . $connect->error);
    }
    
    $getAssignments->bind_param("i", $taskId);
    
    if (!$getAssignments->execute()) {
        throw new Exception("Failed to execute assignments query: " . $getAssignments->error);
    }
    
    $assignmentsResult = $getAssignments->get_result();
    $assignments = [];
    
    while ($row = $assignmentsResult->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    // Return successful response with assignments
    echo json_encode([
        'success' => true,
        'message' => 'Task assignments retrieved successfully',
        'assignments' => $assignments
    ]);
    
} catch (Exception $e) {
    // Handle exceptions gracefully
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 