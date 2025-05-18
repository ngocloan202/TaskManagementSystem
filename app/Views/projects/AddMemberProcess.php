<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";
require_once "../components/Notification.php";

if (!isset($_SESSION["user_id"])) {
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // AJAX request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.']);
  } else {
    // Regular request
    header("Location: /app/Views/auth/login.php");
  }
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $projectID = isset($_POST["ProjectID"]) ? (int)$_POST["ProjectID"] : 0;
  $userID = isset($_POST["UserID"]) ? (int)$_POST["UserID"] : 0;
  $roleInProject = isset($_POST["RoleInProject"]) ? clean_input($_POST["RoleInProject"]) : "thành viên";
  $currentUserID = $_SESSION["user_id"];
  
  // Validate form data
  if ($projectID <= 0 || $userID <= 0) {
    $errorMsg = "Dữ liệu không hợp lệ! Vui lòng thử lại.";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }
  
  // Check if current user has permissions to add members (must be an owner)
  $checkOwnerStmt = $connect->prepare("
    SELECT COUNT(*) AS isOwner 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
  ");
  $checkOwnerStmt->bind_param('ii', $projectID, $currentUserID);
  $checkOwnerStmt->execute();
  $ownerResult = $checkOwnerStmt->get_result()->fetch_assoc();
  
  // If not an owner, only allow adding as a regular member
  if ($ownerResult['isOwner'] == 0) {
    $roleInProject = "thành viên";
  }
  
  // Check if the user is already a member of the project
  $checkMemberStmt = $connect->prepare("
    SELECT COUNT(*) AS isMember, u.FullName 
    FROM ProjectMembers pm
    JOIN Users u ON u.UserID = pm.UserID 
    WHERE pm.ProjectID = ? AND pm.UserID = ?
  ");
  $checkMemberStmt->bind_param('ii', $projectID, $userID);
  $checkMemberStmt->execute();
  $memberResult = $checkMemberStmt->get_result()->fetch_assoc();
  
  if ($memberResult['isMember'] > 0) {
    $errorMsg = "Người dùng {$memberResult['FullName']} đã là thành viên của dự án!";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }
  
  // Get user's name for the success message
  $userStmt = $connect->prepare("SELECT FullName FROM Users WHERE UserID = ?");
  $userStmt->bind_param('i', $userID);
  $userStmt->execute();
  $userName = $userStmt->get_result()->fetch_assoc()['FullName'] ?? 'Người dùng';
  
  // Add the member to the project
  $currentDateTime = date('Y-m-d H:i:s');
  $addMemberStmt = $connect->prepare("
    INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) 
    VALUES (?, ?, ?, ?)
  ");
  $addMemberStmt->bind_param('iiss', $projectID, $userID, $roleInProject, $currentDateTime);
  
  $success = $addMemberStmt->execute();
  $successMsg = "Đã thêm {$userName} vào dự án với vai trò {$roleInProject}!";
  $errorMsg = "Có lỗi xảy ra khi thêm thành viên! Chi tiết: " . $connect->error;
  
  if ($success) {
    setNotification('success', $successMsg);
  } else {
    setNotification('error', $errorMsg);
  }
  
  // Check if this is an AJAX request
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => $success,
      'message' => $success ? $successMsg : $errorMsg
    ]);
    exit();
  } else {
    // Regular form submission - redirect back to the project page
    $returnUrl = "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID . "&memberAction=add";
    header("Location: " . $returnUrl);
    exit();
  }
}

// If not a POST request, redirect to the dashboard
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
  exit();
} else {
  header("Location: /app/Views/dashboard/index.php");
  exit();
}
?> 