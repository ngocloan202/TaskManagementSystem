<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";
require_once "../components/Notification.php";

header("Content-Type: application/json");

// Kiểm tra session
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại."
    ]);
    exit();
}

// Lấy thông tin từ request
$projectMembersID = isset($_POST["projectMemberId"]) ? (int) $_POST["projectMemberId"] : 0;
$projectID = isset($_POST["projectId"]) ? (int) $_POST["projectId"] : 0;
$currentUserID = $_SESSION["user_id"];

// Validate input
if ($projectMembersID <= 0 || $projectID <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Dữ liệu không hợp lệ!"
    ]);
    exit();
}

try {
    // Bắt đầu transaction
    $connect->begin_transaction();

    // 1. Kiểm tra quyền owner
    $ownerCheckStmt = $connect->prepare("
        SELECT COUNT(*) AS isOwner 
        FROM ProjectMembers 
        WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
    ");
    $ownerCheckStmt->bind_param("ii", $projectID, $currentUserID);
    $ownerCheckStmt->execute();
    $isOwner = $ownerCheckStmt->get_result()->fetch_assoc()["isOwner"] > 0;

    if (!$isOwner) {
        throw new Exception("Bạn không có quyền xóa thành viên!");
    }

    // 2. Lấy thông tin thành viên cần xóa
    $targetUserStmt = $connect->prepare("
        SELECT pm.UserID, u.FullName 
        FROM ProjectMembers pm
        JOIN Users u ON u.UserID = pm.UserID
        WHERE pm.ProjectMembersID = ?
    ");
    $targetUserStmt->bind_param("i", $projectMembersID);
    $targetUserStmt->execute();
    $targetUser = $targetUserStmt->get_result()->fetch_assoc();

    if (!$targetUser) {
        throw new Exception("Không tìm thấy thành viên!");
    }

    // 3. Kiểm tra không được xóa chính mình
    if ($targetUser["UserID"] == $currentUserID) {
        throw new Exception("Bạn không thể tự xóa mình khỏi dự án!");
    }

    // 4. Xóa thành viên
    $deleteStmt = $connect->prepare("
        DELETE FROM ProjectMembers 
        WHERE ProjectMembersID = ? AND ProjectID = ?
    ");
    $deleteStmt->bind_param("ii", $projectMembersID, $projectID);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Lỗi khi xóa thành viên: " . $connect->error);
    }

    // Commit transaction
    $connect->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Đã xóa {$targetUser['FullName']} khỏi dự án thành công!"
    ]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $connect->rollback();
    
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

// If not a POST or GET request
header("Content-Type: application/json");
echo json_encode(["status" => "error", "message" => "Phương thức không hợp lệ."]);
exit();
?> 