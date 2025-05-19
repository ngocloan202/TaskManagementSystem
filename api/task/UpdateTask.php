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
    if (!isset($data['task_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Initialize response
    $response = ['success' => false];
    
    // Extract data
    $taskId = intval($data['task_id']);
    $priority = $data['priority'] ?? '';
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $description = $data['description'] ?? '';
    $userId = $_SESSION['user_id'];
    $activityTime = date('Y-m-d H:i:s');
    
    // Validate database connection
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection not established"));
    }
    
    // Log incoming data for debugging
    error_log("UpdateTask received: taskId=$taskId, userId=$userId");
    
    // Validate the task ID and check if user has permission to update it
    $checkTask = $connect->prepare("
        SELECT t.TaskID, t.ProjectID, t.Priority, t.TaskDescription, 
               DATE_FORMAT(t.StartDate, '%Y-%m-%d') AS StartDate,
               DATE_FORMAT(t.EndDate, '%Y-%m-%d') AS EndDate,
               t.TagName, t.TagColor
        FROM Task t 
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
    
    // Save old values for activity log
    $oldValues = [
        'priority' => $taskData['Priority'],
        'start_date' => $taskData['StartDate'],
        'end_date' => $taskData['EndDate'],
        'description' => $taskData['TaskDescription'],
        'tag_name' => $taskData['TagName'],
        'tag_color' => $taskData['TagColor']
    ];
    
    // Debug log
    error_log("UpdateTask: Task found, ProjectID=$projectId");
    
    // Verify user has permission to update the task (must be a project member and not an admin)
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    if ($isAdmin) {
        throw new Exception("Admins cannot modify task details");
    }
    
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
    
    // Debug log
    error_log("UpdateTask: Permission check passed for user $userId");
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Build the update query based on provided fields
        $updates = [];
        $params = [];
        $types = "";
        
        if (isset($data['priority'])) {
            $updates[] = "Priority = ?";
            $params[] = $priority;
            $types .= "s";
        }
        
        if (isset($data['start_date']) && !empty($data['start_date'])) {
            $updates[] = "StartDate = ?";
            $params[] = $startDate;
            $types .= "s";
        }
        
        if (isset($data['end_date']) && !empty($data['end_date'])) {
            $updates[] = "EndDate = ?";
            $params[] = $endDate;
            $types .= "s";
        }
        
        if (isset($data['description'])) {
            $updates[] = "TaskDescription = ?";
            $params[] = $description;
            $types .= "s";
        }
        
        // Add support for tags
        if (isset($data['tag_name'])) {
            $updates[] = "TagName = ?";
            $params[] = $data['tag_name'];
            $types .= "s";
        }
        
        if (isset($data['tag_color'])) {
            $updates[] = "TagColor = ?";
            $params[] = $data['tag_color'];
            $types .= "s";
        }
        
        // Add taskId to params array (will be used in WHERE clause)
        $params[] = $taskId;
        $types .= "i";
        
        // Track what changed for the activity log
        $changes = [];
        if (isset($data['priority']) && $priority !== $oldValues['priority']) {
            $changes['priority'] = ['from' => $oldValues['priority'], 'to' => $priority];
        }
        if (isset($data['start_date']) && $startDate !== $oldValues['start_date']) {
            $changes['start_date'] = ['from' => $oldValues['start_date'], 'to' => $startDate];
        }
        if (isset($data['end_date']) && $endDate !== $oldValues['end_date']) {
            $changes['end_date'] = ['from' => $oldValues['end_date'], 'to' => $endDate];
        }
        if (isset($data['description']) && $description !== $oldValues['description']) {
            $changes['description'] = ['changed' => true];
        }
        // Track tag changes
        if (isset($data['tag_name']) && $data['tag_name'] !== ($oldValues['tag_name'] ?? '')) {
            $changes['tag_name'] = ['from' => $oldValues['tag_name'] ?? '', 'to' => $data['tag_name']];
        }
        if (isset($data['tag_color']) && $data['tag_color'] !== ($oldValues['tag_color'] ?? '')) {
            $changes['tag_color'] = ['from' => $oldValues['tag_color'] ?? '', 'to' => $data['tag_color']];
        }
        
        if (count($updates) > 0) {
            $updateQuery = "UPDATE Task SET " . implode(", ", $updates) . " WHERE TaskID = ?";
            
            $updateStmt = $connect->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception("Failed to prepare update statement: " . $connect->error);
            }
            
            // Bind parameters dynamically
            $updateStmt->bind_param($types, ...$params);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update task: " . $updateStmt->error);
            }
            
            // Only log after changes are identified
            if (count($changes) > 0) {
                error_log("UpdateTask: Successfully updated task fields: " . implode(", ", array_keys($changes)));
            }
        }
        
        // Create details object for the activity log
        $details = [
            'changes' => $changes,
            'updated_by' => $userId,
            'updated_at' => $activityTime,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Convert details to JSON
        $detailsJson = json_encode($details);
        
        // Log the activity if changes were made
        if (count($changes) > 0) {
            $logActivity = $connect->prepare("
                INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
                VALUES (?, 'task_updated', ?, ?, ?)
            ");
            
            if (!$logActivity) {
                throw new Exception("Failed to prepare log statement: " . $connect->error);
            }
            
            $logActivity->bind_param("iiss", $userId, $taskId, $activityTime, $detailsJson);
            
            if (!$logActivity->execute()) {
                throw new Exception("Failed to log activity: " . $logActivity->error);
            }
            
            error_log("UpdateTask: Successfully logged activity for task update");
        }
        
        // Commit the transaction
        $connect->commit();
        
        // Debug log
        error_log("UpdateTask: Transaction committed successfully");
        
        // Update response
        $response = [
            'success' => true,
            'message' => 'Task updated successfully',
            'changes' => $changes,
            'project_id' => $projectId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        throw $e; // Re-throw to be caught by outer try-catch
    }
    
} catch (Exception $e) {
    // Log error server-side
    error_log("Error updating task: " . $e->getMessage());
    
    // Return error as JSON response
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ];
}

// Send response
echo json_encode($response); 