<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: /app/Views/auth/login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $projectName = $_POST['projectName'] ?? '';
  $description = $_POST['description'] ?? '';
  $createdBy = $_SESSION['user_id'];
  $backgroundUrl = $_POST['image'] ?? null; // Lấy link ảnh từ input name="image"

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

  $sql = "INSERT INTO Project (ProjectName, ProjectDescription, CreatedBy, BackgroundUrl) VALUES (?, ?, ?, ?)";
  $statement = $connect->prepare($sql);
  $statement->bind_param("ssss", $projectName, $description, $createdBy, $backgroundUrl);

  if ($statement->execute()) {
    $projectId = $connect->insert_id;

    $memberSql =
      "INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) VALUES (?, ?, 'người sở hữu', NOW())";
    $memberStmt = $connect->prepare($memberSql);
    $memberStmt->bind_param("ii", $projectId, $createdBy);
    $memberStmt->execute();
    $memberStmt->close();

    $_SESSION['success'] = 'Tạo dự án thành công!';
    header('Location: ../dashboard/HomePage.php');
    exit();
  } else {
    $_SESSION["error"] = "Có lỗi xảy ra khi tạo dự án!";
    
  }

  $statement->close();
}

header("Location: /app/Views/dashboard/HomePage.php");
exit();
?> 
