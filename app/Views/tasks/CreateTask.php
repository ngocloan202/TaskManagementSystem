<?php

require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode(["success" => false, "message" => "Method not supported"]);
  exit();
}

$projectId = isset($_POST["projectId"]) ? intval($_POST["projectId"]) : 0;
$taskName = isset($_POST["taskName"]) ? trim($_POST["taskName"]) : "";
$tag = isset($_POST["tag"]) ? trim($_POST["tag"]) : "";
$color = isset($_POST["color"]) ? $_POST["color"] : "#60A5FA";
$dueDate = isset($_POST["dueDate"]) ? $_POST["dueDate"] : "";
$statusName = isset($_POST["statusName"]) ? $_POST["statusName"] : "To Do";

// Validate data
if ($projectId <= 0 || empty($taskName)) {
  echo json_encode(["success" => false, "message" => "Invalid data"]);
  exit();
}

// Get TaskStatusID from status name
$stmt = $connect->prepare("SELECT TaskStatusID FROM TaskStatus WHERE StatusName = ?");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "SQL Error: " . $connect->error]);
  exit();
}

$stmt->bind_param("s", $statusName);
$stmt->execute();
$result = $stmt->get_result();
$statusData = $result->fetch_assoc();

if (!$statusData) {
  echo json_encode(["success" => false, "message" => "Invalid status"]);
  exit();
}

$statusId = $statusData["TaskStatusID"];

// Insert new task into database
$stmt = $connect->prepare("
    INSERT INTO Task (TaskTitle, TaskDescription, TaskStatusID, Priority, StartDate, EndDate, ProjectID, ParentTaskID, TagName, TagColor)
    VALUES (?, '', ?, 'Medium', CURDATE(), CURDATE(), ?, NULL, ?, ?)
");

if (!$stmt) {
  echo json_encode(["success" => false, "message" => "SQL Error: " . $connect->error]);
  exit();
}

$stmt->bind_param("siiss", $taskName, $statusId, $projectId, $tag, $color);

if ($stmt->execute()) {
  $taskId = $stmt->insert_id;
  echo json_encode([
    "success" => true,
    "message" => "Task created successfully",
    "taskId" => $taskId,
  ]);
} else {
  echo json_encode(["success" => false, "message" => "Error creating task: " . $stmt->error]);
}

$stmt->close();
$connect->close();
?>
