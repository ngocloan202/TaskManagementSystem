<?php
header('Content-Type: application/json');

// Establish connection to database
require_once "../../config/database.php";
require_once "../../config/SessionInit.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  echo json_encode([
    'success' => false,
    'message' => 'Bạn cần đăng nhập để thực hiện hành động này'
  ]);
  exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode([
    'success' => false,
    'message' => 'Phương thức không được hỗ trợ'
  ]);
  exit;
}

// Parse input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate data
if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
  echo json_encode([
    'success' => false,
    'message' => 'ID dự án không hợp lệ'
  ]);
  exit;
}

$projectId = intval($data['project_id']);
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'ADMIN';

// Check if user has permission to delete project (must be project owner)
$checkPermissionQuery = "
  SELECT pm.RoleInProject 
  FROM ProjectMembers pm 
  WHERE pm.ProjectID = ? AND pm.UserID = ? AND pm.RoleInProject = 'người sở hữu'
";

$permStmt = $connect->prepare($checkPermissionQuery);
$permStmt->bind_param("ii", $projectId, $userId);
$permStmt->execute();
$permResult = $permStmt->get_result();

if (!$isAdmin && ($permResult->num_rows === 0)) {
  echo json_encode([
    'success' => false,
    'message' => 'Bạn không có quyền xóa dự án này. Chỉ người sở hữu dự án mới có thể xóa.'
  ]);
  exit;
}

try {
  // Begin transaction for atomicity
  $connect->begin_transaction();

  // Delete task assignments first (foreign key constraint)
  $deleteTaskAssignments = "DELETE ta FROM TaskAssignment ta 
                           JOIN Task t ON ta.TaskID = t.TaskID 
                           WHERE t.ProjectID = ?";
  $taStmt = $connect->prepare($deleteTaskAssignments);
  $taStmt->bind_param("i", $projectId);
  $taStmt->execute();

  // Delete tasks
  $deleteTasks = "DELETE FROM Task WHERE ProjectID = ?";
  $taskStmt = $connect->prepare($deleteTasks);
  $taskStmt->bind_param("i", $projectId);
  $taskStmt->execute();

  // Delete project members
  $deleteMembers = "DELETE FROM ProjectMembers WHERE ProjectID = ?";
  $membersStmt = $connect->prepare($deleteMembers);
  $membersStmt->bind_param("i", $projectId);
  $membersStmt->execute();

  // Finally delete the project
  $deleteProject = "DELETE FROM Project WHERE ProjectID = ?";
  $projectStmt = $connect->prepare($deleteProject);
  $projectStmt->bind_param("i", $projectId);
  $projectStmt->execute();

  // Commit the transaction
  $connect->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Dự án đã được xóa thành công'
  ]);
} catch (Exception $e) {
  // Rollback on error
  $connect->rollback();
  
  echo json_encode([
    'success' => false,
    'message' => 'Lỗi khi xóa dự án: ' . $e->getMessage()
  ]);
}

// Close connections
$connect->close(); 