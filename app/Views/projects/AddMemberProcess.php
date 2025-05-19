<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

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
$projectID = isset($_POST["projectId"]) ? (int) $_POST["projectId"] : 0;
$userID = isset($_POST["userId"]) ? (int) $_POST["userId"] : 0;
$roleId = isset($_POST["roleId"]) ? (int) $_POST["roleId"] : 2;
$currentUserID = $_SESSION["user_id"];

// Validate input
if ($projectID <= 0 || $userID <= 0) {
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

    // 2. Xác định role
    $roleInProject = $isOwner && $roleId == 1 ? "người sở hữu" : "thành viên";

    // 3. Kiểm tra user đã là thành viên chưa
    $checkMemberStmt = $connect->prepare("
        SELECT COUNT(*) AS isMember, u.FullName 
        FROM ProjectMembers pm
        JOIN Users u ON u.UserID = pm.UserID 
        WHERE pm.ProjectID = ? AND pm.UserID = ?
    ");
    $checkMemberStmt->bind_param("ii", $projectID, $userID);
    $checkMemberStmt->execute();
    $memberResult = $checkMemberStmt->get_result()->fetch_assoc();

    if ($memberResult["isMember"] > 0) {
        throw new Exception("Người dùng {$memberResult['FullName']} đã là thành viên của dự án!");
    }

    // 4. Thêm thành viên mới
    $currentDateTime = date("Y-m-d H:i:s");
    $addMemberStmt = $connect->prepare("
        INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) 
        VALUES (?, ?, ?, ?)
    ");
    $addMemberStmt->bind_param("iiss", $projectID, $userID, $roleInProject, $currentDateTime);
    
    if (!$addMemberStmt->execute()) {
        throw new Exception("Lỗi khi thêm thành viên: " . $connect->error);
    }

    // 5. Lấy tên user để hiển thị thông báo
    $userStmt = $connect->prepare("SELECT FullName FROM Users WHERE UserID = ?");
    $userStmt->bind_param("i", $userID);
    $userStmt->execute();
    $userName = $userStmt->get_result()->fetch_assoc()["FullName"] ?? "Người dùng";

    // Commit transaction
    $connect->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Đã thêm {$userName} vào dự án với vai trò {$roleInProject}!"
    ]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $connect->rollback();
    
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 