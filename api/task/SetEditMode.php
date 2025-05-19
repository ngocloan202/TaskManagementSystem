<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "../../config/SessionInit.php";

// Set JSON content type
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
    
    // Set edit_mode in session
    if (isset($data['edit_mode'])) {
        $_SESSION['edit_mode'] = (bool)$data['edit_mode'];
        error_log("Edit mode set to: " . ($_SESSION['edit_mode'] ? 'true' : 'false') . " for user " . $_SESSION['user_id']);
    } else {
        throw new Exception("Missing edit_mode parameter");
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'edit_mode' => $_SESSION['edit_mode']
    ]);
    
} catch (Exception $e) {
    // Log error server-side
    error_log("Error setting edit mode: " . $e->getMessage());
    
    // Return error as JSON response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'exception'
    ]);
} 