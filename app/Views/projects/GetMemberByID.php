<?php
// File: app/Views/projects/GetMemberByID.php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

if (!isset($_SESSION["user_id"])) {
  header('Content-Type: application/json');
  echo json_encode(['status' => 'error', 'message' => 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.']);
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  // Get member ID
  $memberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $currentUserID = $_SESSION["user_id"];
  
  if ($memberId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID thành viên không hợp lệ']);
    exit();
  }
  
  // Get member information
  $memberStmt = $connect->prepare("
    SELECT pm.ID, pm.UserID, pm.ProjectID, pm.RoleInProject, u.Username,
           (SELECT COUNT(*) FROM ProjectMembers 
            WHERE ProjectID = pm.ProjectID AND UserID = ? AND RoleInProject = 'người sở hữu') AS isCurrentUserOwner
    FROM ProjectMembers pm
    JOIN Users u ON u.ID = pm.UserID
    WHERE pm.ID = ?
  ");
  $memberStmt->bind_param('ii', $currentUserID, $memberId);
  $memberStmt->execute();
  $memberResult = $memberStmt->get_result()->fetch_assoc();
  
  if (!$memberResult) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thành viên này']);
    exit();
  }
  
  // Check if current user has permission (must be owner or self)
  $isOwner = ($memberResult['isCurrentUserOwner'] > 0);
  $isSelf = ($memberResult['UserID'] == $currentUserID);
  
  if (!$isOwner && !$isSelf) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xem thông tin của thành viên này']);
    exit();
  }
  
  // Prepare response data
  $roleId = ($memberResult['RoleInProject'] === 'người sở hữu') ? 1 : 2;
  
  $responseData = [
    'status' => 'success',
    'data' => [
      'id' => $memberResult['ID'],
      'userId' => $memberResult['UserID'],
      'projectId' => $memberResult['ProjectID'],
      'username' => $memberResult['Username'],
      'roleInProject' => $memberResult['RoleInProject'],
      'roleId' => $roleId,
      'canEdit' => $isOwner || $isSelf
    ]
  ];
  
  header('Content-Type: application/json');
  echo json_encode($responseData);
  exit();
}

// If not a GET request
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
exit();
?> 