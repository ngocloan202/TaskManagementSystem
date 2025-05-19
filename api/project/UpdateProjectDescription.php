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
if (!isset($data['project_id']) || !is_numeric($data['project_id']) || !isset($data['project_description'])) {
  echo json_encode([
    'success' => false,
    'message' => 'Dữ liệu không hợp lệ'
  ]);
  exit;
}

$projectId = intval($data['project_id']);
$projectDescription = $data['project_description'];
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'ADMIN';

// Check project exists and user has permission (either admin or project member)
$hasPermissionQuery = "
  SELECT COUNT(*) as hasPermission 
  FROM Project p
  LEFT JOIN ProjectMembers pm ON pm.ProjectID = p.ProjectID AND pm.UserID = ?
  WHERE p.ProjectID = ? AND (? = 1 OR pm.ProjectID IS NOT NULL)
";

$stmt = $connect->prepare($hasPermissionQuery);
$stmt->bind_param("iii", $userId, $projectId, $isAdmin);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['hasPermission'] == 0) {
  echo json_encode([
    'success' => false,
    'message' => 'Bạn không có quyền cập nhật dự án này'
  ]);
  exit;
}

// Update project description
$updateStmt = $connect->prepare("UPDATE Project SET ProjectDescription = ? WHERE ProjectID = ?");
$updateStmt->bind_param("si", $projectDescription, $projectId);

if ($updateStmt->execute()) {
  echo json_encode([
    'success' => true,
    'message' => 'Cập nhật mô tả dự án thành công'
  ]);
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Lỗi khi cập nhật mô tả dự án: ' . $connect->error
  ]);
}

$updateStmt->close();
$connect->close(); 