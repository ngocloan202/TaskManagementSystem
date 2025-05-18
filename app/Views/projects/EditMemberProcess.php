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
  $projectMembersID = isset($_POST["ProjectMembersID"]) ? (int)$_POST["ProjectMembersID"] : 0;
  $projectID = isset($_POST["ProjectID"]) ? (int)$_POST["ProjectID"] : 0;
  $userID = isset($_POST["UserID"]) ? (int)$_POST["UserID"] : 0;
  $roleInProject = isset($_POST["RoleInProject"]) ? clean_input($_POST["RoleInProject"]) : "thành viên";
  $currentUserID = $_SESSION["user_id"];
  
  // Validate form data
  if ($projectMembersID <= 0 || $projectID <= 0 || $userID <= 0) {
    $errorMsg = "Dữ liệu không hợp lệ! Vui lòng thử lại.";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }
  
  // Check if current user has permission to edit (must be owner or editing themselves)
  $checkPermissionStmt = $connect->prepare("
    SELECT pm.UserID, pm.RoleInProject, u.FullName,
           (SELECT COUNT(*) FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu') AS isCurrentUserOwner
    FROM ProjectMembers pm
    JOIN Users u ON u.UserID = pm.UserID
    WHERE pm.ProjectMembersID = ?
  ");
  $checkPermissionStmt->bind_param('iii', $projectID, $currentUserID, $projectMembersID);
  $checkPermissionStmt->execute();
  $permissionResult = $checkPermissionStmt->get_result()->fetch_assoc();
  
  if (!$permissionResult) {
    $errorMsg = "Thành viên không tồn tại trong dự án này!";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }
  
  $userName = $permissionResult['FullName'];
  
  // Check if current user is the owner or is editing themselves
  $isOwner = ($permissionResult['isCurrentUserOwner'] > 0);
  $isEditingSelf = ($permissionResult['UserID'] == $currentUserID);
  
  // If not owner, they can only edit themselves and can't change role
  if (!$isOwner) {
    if (!$isEditingSelf) {
      $errorMsg = "Bạn không có quyền chỉnh sửa thông tin của thành viên khác!";
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit();
      } else {
        setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
      }
    }
    // Preserve original role if not an owner
    $originalRole = $permissionResult['RoleInProject'];
    $roleInProject = $originalRole;
  }
  
  // Update the member information
  $currentDateTime = date('Y-m-d H:i:s');
  $updateStmt = $connect->prepare("
    UPDATE ProjectMembers 
    SET UserID = ?, RoleInProject = ?, JoinedAt = ? 
    WHERE ProjectMembersID = ?
  ");
  $updateStmt->bind_param('issi', $userID, $roleInProject, $currentDateTime, $projectMembersID);
  
  $success = $updateStmt->execute();
  $successMsg = "Đã cập nhật thông tin thành viên {$userName} thành công!";
  $errorMsg = "Có lỗi xảy ra khi cập nhật thông tin thành viên! Chi tiết: " . $connect->error;
  
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
    $returnUrl = "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID . "&memberAction=edit";
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