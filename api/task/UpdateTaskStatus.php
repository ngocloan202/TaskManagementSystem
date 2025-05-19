<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "../../config/SessionInit.php";
require_once "../../config/database.php";

// Set JSON content type - do this early to ensure all output is JSON
header('Content-Type: application/json');

// Error handler function to convert PHP errors to JSON
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => "Error: $errstr in $errfile on line $errline",
        'error_type' => 'php_error'
    ];
    echo json_encode($response);
    exit;
}

// Set the custom error handler
set_error_handler('handleError');

try {
    // Ensure request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Get JSON data from request body
    $jsonData = file_get_contents('php://input');
    if (empty($jsonData)) {
        throw new Exception("No data received");
    }
    
    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    // Validate required fields
    if (!isset($data['task_id']) || !isset($data['status_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Initialize response
    $response = ['success' => false];
    
    // Extract data
    $taskId = intval($data['task_id']);
    $statusId = intval($data['status_id']);
    $userId = $_SESSION['user_id'];
    $activityTime = date('Y-m-d H:i:s');
    
    // Log incoming data for debugging
    error_log("UpdateTaskStatus received: taskId=$taskId, statusId=$statusId, userId=$userId");
    
    // Validate database connection
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection not established"));
    }
    
    // Validate the task ID and check if user has permission to update it
    $checkTask = $connect->prepare("
        SELECT t.TaskID, t.ProjectID, ts.StatusName 
        FROM Task t 
        JOIN TaskStatus ts ON t.TaskStatusID = ts.TaskStatusID
        WHERE t.TaskID = ?
    ");
    
    if (!$checkTask) {
        throw new Exception("Failed to prepare task check statement: " . $connect->error);
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
    $oldStatusName = $taskData['StatusName'];
    
    // Debug log
    error_log("UpdateTaskStatus: Task found, ProjectID=$projectId, OldStatus=$oldStatusName");
    
    // Verify user has permission to update the task (must be a project member)
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    if (!$isAdmin) {
        $checkPermission = $connect->prepare("
            SELECT COUNT(*) as isMember 
            FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ?
        ");
        
        if (!$checkPermission) {
            throw new Exception("Failed to prepare permission check: " . $connect->error);
        }
        
        $checkPermission->bind_param("ii", $projectId, $userId);
        
        if (!$checkPermission->execute()) {
            throw new Exception("Failed to execute permission check: " . $checkPermission->error);
        }
        
        $permissionResult = $checkPermission->get_result()->fetch_assoc();
        if (!$permissionResult || $permissionResult['isMember'] == 0) {
            throw new Exception("You don't have permission to update this task");
        }
    }
    
    // Debug log
    error_log("UpdateTaskStatus: Permission check passed for user $userId");
    
    // Get the new status name
    $getStatus = $connect->prepare("SELECT StatusName FROM TaskStatus WHERE TaskStatusID = ?");
    if (!$getStatus) {
        throw new Exception("Failed to prepare status query: " . $connect->error);
    }
    
    $getStatus->bind_param("i", $statusId);
    
    if (!$getStatus->execute()) {
        throw new Exception("Failed to execute status query: " . $getStatus->error);
    }
    
    $statusResult = $getStatus->get_result();
    if ($statusResult->num_rows === 0) {
        throw new Exception("Invalid status ID");
    }
    
    $statusData = $statusResult->fetch_assoc();
    $newStatusName = $statusData['StatusName'];
    
    // Debug log
    error_log("UpdateTaskStatus: Status ID $statusId maps to '$newStatusName'");
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Update the task status
        $updateTask = $connect->prepare("UPDATE Task SET TaskStatusID = ? WHERE TaskID = ?");
        if (!$updateTask) {
            throw new Exception("Failed to prepare update statement: " . $connect->error);
        }
        
        $updateTask->bind_param("ii", $statusId, $taskId);
        
        if (!$updateTask->execute()) {
            throw new Exception("Failed to update task status: " . $updateTask->error);
        }
        
        // Create details object for the activity log
        $details = [
            'old_status' => $oldStatusName,
            'new_status' => $newStatusName,
            'updated_by' => $userId,
            'updated_at' => $activityTime,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Convert details to JSON
        $detailsJson = json_encode($details);
        
        // Log the activity
        $logActivity = $connect->prepare("
            INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
            VALUES (?, 'task_status_changed', ?, ?, ?)
        ");
        
        if (!$logActivity) {
            throw new Exception("Failed to prepare log statement: " . $connect->error);
        }
        
        $logActivity->bind_param("iiss", $userId, $taskId, $activityTime, $detailsJson);
        
        if (!$logActivity->execute()) {
            throw new Exception("Failed to log activity: " . $logActivity->error);
        }
        
        // Commit the transaction
        $connect->commit();
        
        // Debug log
        error_log("UpdateTaskStatus: Transaction committed successfully");
        
        // Update response
        $response = [
            'success' => true,
            'message' => 'Task status updated successfully',
            'status' => [
                'id' => $statusId,
                'name' => $newStatusName
            ],
            'project_id' => $projectId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        throw $e; // Re-throw to be caught by outer try-catch
    }
    
} catch (Exception $e) {
    // Log error server-side
    error_log("Error updating task status: " . $e->getMessage());
    
    // Return error as JSON response
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ];
}

// Send response
echo json_encode($response); 