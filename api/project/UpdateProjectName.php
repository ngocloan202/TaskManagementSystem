<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session and connect to database
require_once "../../config/SessionInit.php";
require_once "../../config/database.php";

// Set content type to JSON
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
    $inputData = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($inputData['project_id']) || !is_numeric($inputData['project_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing or invalid project_id']);
        exit;
    }
    
    if (!isset($inputData['project_name']) || empty(trim($inputData['project_name']))) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Project name cannot be empty']);
        exit;
    }
    
    $projectId = intval($inputData['project_id']);
    $projectName = trim($inputData['project_name']);
    $currentUserId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    // Validate database connection
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection not established"));
    }
    
    // Check if project exists
    $checkProjectStmt = $connect->prepare("SELECT ProjectID FROM Project WHERE ProjectID = ?");
    if (!$checkProjectStmt) {
        throw new Exception("Failed to prepare project check: " . $connect->error);
    }
    
    $checkProjectStmt->bind_param("i", $projectId);
    if (!$checkProjectStmt->execute()) {
        throw new Exception("Failed to execute project check: " . $checkProjectStmt->error);
    }
    
    $projectResult = $checkProjectStmt->get_result();
    if ($projectResult->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    // If not an admin, check if user is a member of the project
    if (!$isAdmin) {
        $memberCheckStmt = $connect->prepare("
            SELECT COUNT(*) as isMember, RoleInProject  
            FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ?
        ");
        
        if (!$memberCheckStmt) {
            throw new Exception("Failed to prepare member check: " . $connect->error);
        }
        
        $memberCheckStmt->bind_param("ii", $projectId, $currentUserId);
        
        if (!$memberCheckStmt->execute()) {
            throw new Exception("Failed to execute member check: " . $memberCheckStmt->error);
        }
        
        $memberResult = $memberCheckStmt->get_result()->fetch_assoc();
        
        if (!$memberResult || $memberResult['isMember'] == 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update this project']);
            exit;
        }
        
        // Optional: Check if user has appropriate role (e.g., owner) to rename the project
        // Uncomment if you want to restrict to project owners only
        /*
        if ($memberResult['RoleInProject'] !== 'người sở hữu') {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Only project owners can rename projects']);
            exit;
        }
        */
    }
    
    // Log old project name for activity tracking
    $oldNameStmt = $connect->prepare("SELECT ProjectName FROM Project WHERE ProjectID = ?");
    if (!$oldNameStmt) {
        throw new Exception("Failed to prepare old name query: " . $connect->error);
    }
    
    $oldNameStmt->bind_param("i", $projectId);
    if (!$oldNameStmt->execute()) {
        throw new Exception("Failed to execute old name query: " . $oldNameStmt->error);
    }
    
    $oldNameResult = $oldNameStmt->get_result()->fetch_assoc();
    $oldName = $oldNameResult['ProjectName'];
    
    // Update project name
    $updateStmt = $connect->prepare("UPDATE Project SET ProjectName = ? WHERE ProjectID = ?");
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update: " . $connect->error);
    }
    
    $updateStmt->bind_param("si", $projectName, $projectId);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to execute update: " . $updateStmt->error);
    }
    
    if ($updateStmt->affected_rows === 0) {
        // No rows were updated (likely name was the same)
        echo json_encode(['success' => true, 'message' => 'No changes were made']);
        exit;
    }
    
    // Log the change to ActivityLog
    try {
        $activityStmt = $connect->prepare("
            INSERT INTO ActivityLog (UserID, ActivityType, EntityID, EntityType, Details) 
            VALUES (?, 'PROJECT_RENAME', ?, 'PROJECT', ?)
        ");
        
        if ($activityStmt) {
            $details = json_encode([
                'old_name' => $oldName,
                'new_name' => $projectName,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $activityStmt->bind_param("iis", $currentUserId, $projectId, $details);
            $activityStmt->execute();
        }
    } catch (Exception $e) {
        // Just log the error, don't fail the whole request
        error_log("Failed to log project rename activity: " . $e->getMessage());
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Project name updated successfully',
        'data' => [
            'project_id' => $projectId,
            'project_name' => $projectName
        ]
    ]);
    
} catch (Exception $e) {
    // Log error server-side
    error_log("Error updating project name: " . $e->getMessage());
    
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ]);
}
?> 