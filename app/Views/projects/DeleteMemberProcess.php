<?php
header('Content-Type: application/json');
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Phương thức không hợp lệ.']);
    exit;
}

// 2) Check session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Phiên đăng nhập hết hạn.']);
    exit;
}

$currentUserID    = $_SESSION['user_id'];
$projectMembersID = intval($_POST['projectMemberId'] ?? 0);
$projectID        = intval($_POST['projectId']        ?? 0);

// 3) Validate
if ($projectMembersID <= 0 || $projectID <= 0) {
    echo json_encode(['status'=>'error','message'=>'Dữ liệu không hợp lệ!']);
    exit;
}

try {
    $connect->begin_transaction();

    // 4) Check if user is owner
    $stmt = $connect->prepare("
        SELECT COUNT(*) as cnt
          FROM ProjectMembers
         WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
    ");
    $stmt->bind_param('ii', $projectID, $currentUserID);
    $stmt->execute();
    $isOwner = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
    $stmt->close();

    if (!$isOwner) {
        throw new Exception("Bạn không có quyền xóa thành viên!");
    }

    // 5) Get user name for notification
    $stmt = $connect->prepare("
        SELECT u.FullName, pm.UserID
          FROM ProjectMembers pm
          JOIN Users u ON u.UserID = pm.UserID
         WHERE pm.ProjectMembersID = ?
    ");
    $stmt->bind_param('i', $projectMembersID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        throw new Exception("Không tìm thấy thành viên!");
    }
    if ($row['UserID'] == $currentUserID) {
        throw new Exception("Bạn không thể tự xóa mình!");
    }
    $userName = $row['FullName'];

    // 6) Delete
    $stmt = $connect->prepare("
        DELETE FROM ProjectMembers
         WHERE ProjectMembersID = ? AND ProjectID = ?
    ");
    $stmt->bind_param('ii', $projectMembersID, $projectID);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi xóa: ".$connect->error);
    }
    $stmt->close();

    $connect->commit();
    echo json_encode(['status'=>'success','message'=>"Đã xóa {$userName} khỏi dự án."]);
    exit;

} catch (Exception $e) {
    $connect->rollback();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
