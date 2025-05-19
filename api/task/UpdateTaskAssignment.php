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
    if (!isset($data['task_id']) || !isset($data['user_id']) || !isset($data['action'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Extract data
    $taskId = intval($data['task_id']);
    $userId = intval($data['user_id']);
    $action = $data['action']; // 'assign' or 'unassign'
    $currentUserId = $_SESSION['user_id'];
    $activityTime = date('Y-m-d H:i:s');
    
    // Validate action
    if ($action !== 'assign' && $action !== 'unassign') {
        throw new Exception("Invalid action. Must be 'assign' or 'unassign'");
    }
    
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
    
    // Verify user has permission to modify the task (must be a project member and not an admin)
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    if ($isAdmin) {
        throw new Exception("Admins cannot modify task assignments");
    }
    
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
        throw new Exception("You don't have permission to update this task");
    }
    
    // Check if the assigned user is a member of the project
    if ($action === 'assign') {
        $checkMember = $connect->prepare("
            SELECT COUNT(*) as isMember 
            FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ?
        ");
        
        if (!$checkMember) {
            throw new Exception("Failed to prepare member check: " . $connect->error);
        }
        
        $checkMember->bind_param("ii", $projectId, $userId);
        
        if (!$checkMember->execute()) {
            throw new Exception("Failed to execute member check: " . $checkMember->error);
        }
        
        $memberResult = $checkMember->get_result()->fetch_assoc();
        if (!$memberResult || $memberResult['isMember'] == 0) {
            throw new Exception("Selected user is not a member of this project");
        }
    }
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Get current assignee(s) list (for logging changes)
        $currentAssignees = [];
        $getCurrentAssignees = $connect->prepare("
            SELECT UserID FROM TaskAssignment WHERE TaskID = ?
        ");
        
        if (!$getCurrentAssignees) {
            throw new Exception("Failed to prepare current assignees query: " . $connect->error);
        }
        
        $getCurrentAssignees->bind_param("i", $taskId);
        
        if (!$getCurrentAssignees->execute()) {
            throw new Exception("Failed to execute current assignees query: " . $getCurrentAssignees->error);
        }
        
        $currentAssigneesResult = $getCurrentAssignees->get_result();
        while ($row = $currentAssigneesResult->fetch_assoc()) {
            $currentAssignees[] = $row['UserID'];
        }
        
        if ($action === 'assign') {
            // Skip if already assigned to the same user
            if (in_array($userId, $currentAssignees)) {
                $connect->commit();
                echo json_encode(['success' => true, 'message' => 'User already assigned to this task']);
                exit;
            }
            
            // Assign user to task (removed AssignedDate column)
            $assignStmt = $connect->prepare("
                INSERT INTO TaskAssignment (TaskID, UserID, AssignedBy)
                VALUES (?, ?, ?)
            ");
            
            if (!$assignStmt) {
                throw new Exception("Failed to prepare assign statement: " . $connect->error);
            }
            
            $assignStmt->bind_param("iii", $taskId, $userId, $currentUserId);
            
            if (!$assignStmt->execute()) {
                throw new Exception("Failed to assign user: " . $assignStmt->error);
            }
            
            // Log the assignment
            $assignDetails = json_encode([
                'assigned_user_id' => $userId,
                'by_user_id' => $currentUserId,
                'timestamp' => $activityTime,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $logAssignment = $connect->prepare("
                INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
                VALUES (?, 'task_assigned', ?, ?, ?)
            ");
            
            if (!$logAssignment) {
                throw new Exception("Failed to prepare assignment log: " . $connect->error);
            }
            
            $logAssignment->bind_param("iiss", $currentUserId, $taskId, $activityTime, $assignDetails);
            
            if (!$logAssignment->execute()) {
                throw new Exception("Failed to log assignment: " . $logAssignment->error);
            }
        } else { // unassign
            // Skip if not assigned
            if (!in_array($userId, $currentAssignees)) {
                $connect->commit();
                echo json_encode(['success' => true, 'message' => 'User not assigned to this task']);
                exit;
            }
            
            // Unassign user from task
            $unassignStmt = $connect->prepare("
                DELETE FROM TaskAssignment WHERE TaskID = ? AND UserID = ?
            ");
            
            if (!$unassignStmt) {
                throw new Exception("Failed to prepare unassign statement: " . $connect->error);
            }
            
            $unassignStmt->bind_param("ii", $taskId, $userId);
            
            if (!$unassignStmt->execute()) {
                throw new Exception("Failed to unassign user: " . $unassignStmt->error);
            }
            
            // Log the unassignment
            $unassignDetails = json_encode([
                'unassigned_user_id' => $userId,
                'by_user_id' => $currentUserId,
                'timestamp' => $activityTime,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $logUnassignment = $connect->prepare("
                INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
                VALUES (?, 'task_unassigned', ?, ?, ?)
            ");
            
            if (!$logUnassignment) {
                throw new Exception("Failed to prepare unassignment log: " . $connect->error);
            }
            
            $logUnassignment->bind_param("iiss", $currentUserId, $taskId, $activityTime, $unassignDetails);
            
            if (!$logUnassignment->execute()) {
                throw new Exception("Failed to log unassignment: " . $logUnassignment->error);
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $action === 'assign' ? 'User assigned successfully' : 'User unassigned successfully',
            'action' => $action,
            'task_id' => $taskId,
            'user_id' => $userId
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log error server-side
    error_log("Error updating task assignment: " . $e->getMessage());
    
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ]);
} 