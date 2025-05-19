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
    
    // Validate required parameters
    if (!isset($_GET['project_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing project_id parameter']);
        exit;
    }
    
    // Extract data
    $projectId = intval($_GET['project_id']);
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
    
    // Validate database connection
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection not established"));
    }
    
    // Check if project exists
    $checkProject = $connect->prepare("
        SELECT ProjectID, ProjectName 
        FROM Project 
        WHERE ProjectID = ?
    ");
    
    if (!$checkProject) {
        throw new Exception("Failed to prepare project check: " . $connect->error);
    }
    
    $checkProject->bind_param("i", $projectId);
    
    if (!$checkProject->execute()) {
        throw new Exception("Failed to execute project check: " . $checkProject->error);
    }
    
    $projectResult = $checkProject->get_result();
    if ($projectResult->num_rows === 0) {
        throw new Exception("Project not found");
    }
    
    $projectData = $projectResult->fetch_assoc();
    
    // Verify user has permission to view the project (must be a project member or an admin)
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
            throw new Exception("You don't have permission to view this project");
        }
    }
    
    // Get project members
    $getMembersQuery = "
        SELECT 
            u.UserID,
            u.FullName,
            u.Email,
            u.Avatar
        FROM 
            Users u
        JOIN 
            ProjectMembers pm ON u.UserID = pm.UserID
        WHERE 
            pm.ProjectID = ?
        ORDER BY 
            u.FullName ASC
    ";
    
    $getMembers = $connect->prepare($getMembersQuery);
    
    if (!$getMembers) {
        throw new Exception("Failed to prepare members query: " . $connect->error);
    }
    
    $getMembers->bind_param("i", $projectId);
    
    if (!$getMembers->execute()) {
        throw new Exception("Failed to execute members query: " . $getMembers->error);
    }
    
    $membersResult = $getMembers->get_result();
    $members = [];
    
    while ($member = $membersResult->fetch_assoc()) {
        $members[] = $member;
    }
    
    // Return project members
    echo json_encode([
        'success' => true,
        'project_id' => $projectId,
        'project_name' => $projectData['ProjectName'],
        'members' => $members,
        'total_members' => count($members)
    ]);
    
} catch (Exception $e) {
    // Log error server-side
    error_log("Error getting project members: " . $e->getMessage());
    
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ]);
} 