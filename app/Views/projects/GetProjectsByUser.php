<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../../config/database.php";
require_once "../../../config/SessionInit.php";

header("Content-Type: application/json");

$userId = $_SESSION["user_id"] ?? 0;
// Lấy tất cả project user là thành viên
$sql = "
SELECT 
    p.ProjectID,
    p.ProjectName,
    p.ProjectDescription,
    p.StartDate,
    p.EndDate,
    u.Avatar AS OwnerAvatar,
    u.FullName AS OwnerName,
    -- Tính tiến độ
    (SELECT COUNT(*) FROM Task t WHERE t.ProjectID = p.ProjectID) AS totalTasks,
    (SELECT COUNT(*) FROM Task t WHERE t.ProjectID = p.ProjectID AND t.TaskStatusID = 3) AS completedTasks
FROM Project p
JOIN ProjectMembers pm ON pm.ProjectID = p.ProjectID
JOIN Users u ON u.UserID = p.CreatedBy
WHERE pm.UserID = ?
ORDER BY p.ProjectID DESC
";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$projects = [];
while ($row = $result->fetch_assoc()) {
    $row["progress"] = $row["totalTasks"] ? round($row["completedTasks"] / $row["totalTasks"] * 100) : 0;
    $projects[] = $row;
}
echo json_encode($projects);