<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /app/Views/auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $projectName = clean_input($_POST["projectName"] ?? "");
    $description = clean_input($_POST["description"] ?? "");
    $image = clean_input($_POST["image"] ?? "");
    $userId = $_SESSION["user_id"];

    if (empty($projectName)) {
        $_SESSION["error"] = "Vui lòng nhập tên dự án!";
        header("Location: /app/Views/dashboard/HomePage.php");
        exit();
    }

    $checkSql = "SELECT ProjectID FROM Project WHERE ProjectName = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("s", $projectName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION["error"] = "Dự án đã tồn tại!";
        header("Location: /app/Views/dashboard/HomePage.php");
        exit();
    }
    $checkStmt->close();

    $sql = "INSERT INTO Project (ProjectName, ProjectDescription, CreatedBy, StartDate) VALUES (?, ?, ?, NOW())";
    $statement = $connect->prepare($sql);
    $statement->bind_param("ssi", $projectName, $description, $userId);
    
    if ($statement->execute()) {
        $projectId = $connect->insert_id;
    
        $memberSql = "INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) VALUES (?, ?, 'người sở hữu', NOW())";
        $memberStmt = $connect->prepare($memberSql);
        $memberStmt->bind_param("ii", $projectId, $userId);
        $memberStmt->execute();
        $memberStmt->close();

        $_SESSION["success"] = "Tạo dự án thành công!";
    } else {
        $_SESSION["error"] = "Có lỗi xảy ra khi tạo dự án!";
    }
    
    $statement->close();
}

header("Location: /app/Views/dashboard/HomePage.php");
exit();
?> 
