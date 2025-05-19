<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại."
    ]);
    exit();
}

// Get information from request
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
    // Start transaction
    $connect->begin_transaction();

    // 1. Check owner permission
    $ownerCheckStmt = $connect->prepare("
        SELECT COUNT(*) AS isOwner 
        FROM ProjectMembers 
        WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
    ");
    $ownerCheckStmt->bind_param("ii", $projectID, $currentUserID);
    $ownerCheckStmt->execute();
    $isOwner = $ownerCheckStmt->get_result()->fetch_assoc()["isOwner"] > 0;

    // 2. Determine role
    $roleInProject = $isOwner && $roleId == 1 ? "người sở hữu" : "thành viên";

    // 3. Check if user is already a member
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

    // 4. Add new member
    $currentDateTime = date("Y-m-d H:i:s");
    $addMemberStmt = $connect->prepare("
        INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) 
        VALUES (?, ?, ?, ?)
    ");
    $addMemberStmt->bind_param("iiss", $projectID, $userID, $roleInProject, $currentDateTime);
    
    if (!$addMemberStmt->execute()) {
        throw new Exception("Lỗi khi thêm thành viên: " . $connect->error);
    }

    // 5. Get user name for notification
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
    $connect->rollback();
    
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 