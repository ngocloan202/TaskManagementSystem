<?php
// Yêu cầu các file cần thiết
require_once "../../config/SessionInit.php";
require_once "../../config/database.php";

// Kiểm tra quyền admin
check_role("ADMIN");

// Set header để trả về JSON
header('Content-Type: application/json');

try {
    // Truy vấn database để lấy tổng số công việc
    $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Task");
    $stmt->execute();
    $result = $stmt->get_result();
    $taskCount = $result->fetch_assoc()["count"];
    
    // Trả về kết quả dạng JSON
    echo json_encode(['count' => $taskCount]);
} catch (Exception $e) {
    // Trả về lỗi nếu có
    http_response_code(500);
    echo json_encode(['error' => 'Không thể lấy thông tin công việc', 'message' => $e->getMessage()]);
}
?> 