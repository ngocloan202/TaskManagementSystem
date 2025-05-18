<?php
// File: create_task.php
// Xử lý tạo task mới

require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Kiểm tra nếu là yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Lấy thông tin từ form
$projectId = isset($_POST['projectId']) ? intval($_POST['projectId']) : 0;
$taskName = isset($_POST['taskName']) ? trim($_POST['taskName']) : '';
$tag = isset($_POST['tag']) ? trim($_POST['tag']) : '';
$color = isset($_POST['color']) ? $_POST['color'] : '#60A5FA';
$dueDate = isset($_POST['dueDate']) ? $_POST['dueDate'] : '';
$statusName = isset($_POST['statusName']) ? $_POST['statusName'] : 'Cần làm';


// Validate dữ liệu
if ($projectId <= 0 || empty($taskName)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Lấy TaskStatusID từ tên trạng thái
$stmt = $connect->prepare("SELECT TaskStatusID FROM TaskStatus WHERE StatusName = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $connect->error]);
    exit;
}

$stmt->bind_param("s", $statusName);
$stmt->execute();
$result = $stmt->get_result();
$statusData = $result->fetch_assoc();

if (!$statusData) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

$statusId = $statusData['TaskStatusID'];

// Insert task mới vào database
$stmt = $connect->prepare("
    INSERT INTO Task (TaskTitle, TaskDescription, TaskStatusID, Priority, StartDate, EndDate, ProjectID, ParentTaskID, TagName, TagColor)
    VALUES (?, '', ?, 'Trung Bình', CURDATE(), CURDATE(), ?, NULL, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $connect->error]);
    exit;
}

$stmt->bind_param("siiss", $taskName, $statusId, $projectId, $tag, $color);

if ($stmt->execute()) {
    $taskId = $stmt->insert_id;
    echo json_encode(['success' => true, 'message' => 'Tạo nhiệm vụ thành công', 'taskId' => $taskId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo nhiệm vụ: ' . $stmt->error]);
}

$stmt->close();
$connect->close();
?>
