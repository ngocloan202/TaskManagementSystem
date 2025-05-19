<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Check if ActivityLog table exists, create it if it doesn't
try {
  $tableCheckResult = $connect->query("SHOW TABLES LIKE 'ActivityLog'");
  if ($tableCheckResult && $tableCheckResult->num_rows === 0) {
    // Table doesn't exist, create it
    $createTableSQL = "
      CREATE TABLE IF NOT EXISTS ActivityLog (
        ActivityID INT AUTO_INCREMENT PRIMARY KEY,
        UserID INT NOT NULL,
        ActivityType VARCHAR(50) NOT NULL,
        RelatedID INT NOT NULL,
        ActivityTime DATETIME NOT NULL,
        Details TEXT,
        FOREIGN KEY (UserID) REFERENCES Users(UserID)
      )
    ";
    $connect->query($createTableSQL);
    error_log("Created ActivityLog table");
  }
} catch (Exception $e) {
  error_log("Error checking/creating ActivityLog table: " . $e->getMessage());
  // Continue execution even if table check/creation fails
}

$title = "Chi tiết nhiệm vụ | CubeFlow";
$currentPage = "projects";

// Initialize variables to avoid undefined variable errors
$task = null;
$assignee = null;
$activities = [];

try {
  // Lấy task ID và project ID từ URL
  $taskId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
  $projectId = isset($_GET["project_id"]) ? intval($_GET["project_id"]) : 0;

  if ($taskId <= 0 || $projectId <= 0) {
    throw new Exception("Thông tin nhiệm vụ không hợp lệ");
  }

  // Kiểm tra quyền xem task (user phải là thành viên của dự án hoặc là admin)
  $userID = $_SESSION['user_id'] ?? 0;
  $isAdmin = $_SESSION['role'] === 'ADMIN';

  if (!$isAdmin) {
    // Kiểm tra xem người dùng có phải là thành viên của dự án không
    $memberCheckStmt = $connect->prepare("
      SELECT COUNT(*) as isMember 
      FROM ProjectMembers 
      WHERE ProjectID = ? AND UserID = ?
    ");
    
    if (!$memberCheckStmt) {
      throw new Exception("Lỗi truy vấn: " . $connect->error);
    }
    
    $memberCheckStmt->bind_param("ii", $projectId, $userID);
    $memberCheckStmt->execute();
    $isMemberResult = $memberCheckStmt->get_result()->fetch_assoc();
    
    if (!$isMemberResult || $isMemberResult['isMember'] == 0) {
      throw new Exception("Bạn không có quyền xem nhiệm vụ này");
    }
  }

  // Lấy thông tin chi tiết của task
  $taskQuery = $connect->prepare("
    SELECT 
      t.TaskID,
      t.TaskTitle,
      t.TaskDescription,
      t.TagName,
      t.TagColor,
      t.Priority,
      DATE_FORMAT(t.StartDate, '%Y-%m-%d') AS StartDate,
      DATE_FORMAT(t.EndDate, '%Y-%m-%d') AS EndDate,
      ts.StatusName,
      p.ProjectName,
      p.ProjectID
    FROM Task t
    JOIN TaskStatus ts ON t.TaskStatusID = ts.TaskStatusID
    JOIN Project p ON t.ProjectID = p.ProjectID
    WHERE t.TaskID = ? AND t.ProjectID = ?
  ");

  if (!$taskQuery) {
    throw new Exception("Lỗi truy vấn task: " . $connect->error);
  }

  $taskQuery->bind_param("ii", $taskId, $projectId);
  $taskQuery->execute();
  $taskResult = $taskQuery->get_result();
  
  if ($taskResult->num_rows === 0) {
    throw new Exception("Không tìm thấy nhiệm vụ");
  }
  
  $task = $taskResult->fetch_assoc();

  // Ghi log sự kiện xem task
  try {
    // Tránh ghi log liên tục nếu người dùng refresh trang
    $lastViewKey = "last_view_task_{$userID}_{$taskId}";
    $currentTime = time();
    $lastViewTime = $_SESSION[$lastViewKey] ?? 0;
    
    // Chỉ ghi log nếu đã qua 5 phút kể từ lần xem cuối
    if ($currentTime - $lastViewTime > 300) {
      $viewTime = date('Y-m-d H:i:s');
      $details = json_encode([
        'from' => $isAdmin ? 'admin_view' : 'user_view',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'page' => 'task_details',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
      ]);
      
      $logQuery = $connect->prepare("
        INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
        VALUES (?, 'task_viewed', ?, ?, ?)
      ");
      
      if ($logQuery) {
        $logQuery->bind_param('iiss', $userID, $taskId, $viewTime, $details);
        $logQuery->execute();
        
        // Lưu thời gian xem cuối vào session
        $_SESSION[$lastViewKey] = $currentTime;
        
        error_log("Logged task view event: User $userID viewed task $taskId at $viewTime");
      } else {
        error_log("Failed to prepare task view log query: " . $connect->error);
      }
    }
  } catch (Exception $e) {
    error_log("Error logging task view: " . $e->getMessage());
    // Continue execution even if logging fails
  }

  // Lấy thông tin người được giao nhiệm vụ - Sử dụng truy vấn đơn giản hơn
  try {
    $assigneeSQL = "
      SELECT 
        u.UserID,
        u.FullName,
        u.Avatar
      FROM Users u
      JOIN TaskAssignment ta ON u.UserID = ta.UserID 
      WHERE ta.TaskID = ?
      LIMIT 1
    ";

    // Debugging
    error_log("TaskDetails.php - Executing assignee query for Task ID: $taskId");
    $assigneeQuery = $connect->prepare($assigneeSQL);

    if (!$assigneeQuery) {
      error_log("SQL Error in TaskDetails.php (assigneeQuery): " . $connect->error . " - SQL: " . $assigneeSQL);
      
      // Try a direct query to check if we can get any user from the table
      $directQuery = "SELECT TaskID, UserID FROM TaskAssignment WHERE TaskID = $taskId LIMIT 1";
      $directResult = $connect->query($directQuery);
      if ($directResult && $directResult->num_rows > 0) {
        $directRow = $directResult->fetch_assoc();
        error_log("Direct query found: TaskID={$directRow['TaskID']}, UserID={$directRow['UserID']}");
        
        // Try to get user info directly
        $userId = $directRow['UserID'];
        $userResult = $connect->query("SELECT UserID, FullName, Avatar FROM Users WHERE UserID = $userId LIMIT 1");
        if ($userResult && $userResult->num_rows > 0) {
          $assignee = $userResult->fetch_assoc();
          error_log("Found user directly: " . $assignee['FullName']);
        }
      } else {
        error_log("No assignment found for task $taskId using direct query: $directQuery");
      }
    } else {
      $assigneeQuery->bind_param("i", $taskId);
      $assigneeQuery->execute();
      
      // Check for execution errors
      if ($assigneeQuery->error) {
        error_log("Execution error for assignee query: " . $assigneeQuery->error);
      } else {
        $assigneeResult = $assigneeQuery->get_result();
        $assignee = $assigneeResult->fetch_assoc();
        
        // Log whether we found an assignee
        if ($assignee) {
          error_log("Found assignee for task $taskId: " . $assignee['FullName']);
        } else {
          error_log("No assignee found for task $taskId");
        }
      }
    }
  } catch (Exception $e) {
    error_log("Error getting assignee: " . $e->getMessage());
    // Continue execution even if assignee query fails
  }

  // Lấy hoạt động gần đây của task
  try {
    $activitySQL = "
      SELECT 
        a.ActivityTime,
        a.ActivityType,
        a.Details,
        u.FullName,
        u.Avatar
      FROM ActivityLog a
      JOIN Users u ON a.UserID = u.UserID
      WHERE a.RelatedID = ? AND a.ActivityType LIKE 'task%'
      ORDER BY a.ActivityTime DESC
      LIMIT 10
    ";

    // Debugging
    error_log("TaskDetails.php - Executing activity query for Task ID: $taskId");
    $activityQuery = $connect->prepare($activitySQL);

    if (!$activityQuery) {
      error_log("SQL Error in TaskDetails.php (activityQuery): " . $connect->error . " - SQL: " . $activitySQL);
      
      // Check if ActivityLog table exists
      $checkTable = $connect->query("SHOW TABLES LIKE 'ActivityLog'");
      if ($checkTable && $checkTable->num_rows === 0) {
        error_log("Table 'ActivityLog' does not exist in the database");
      } else {
        error_log("ActivityLog table exists but query preparation failed");
      }
    } else {
      $activityQuery->bind_param("i", $taskId);
      $result = $activityQuery->execute();
      
      if (!$result) {
        error_log("Failed to execute activity query: " . $activityQuery->error);
      } else {
        $activityResult = $activityQuery->get_result();
        while ($row = $activityResult->fetch_assoc()) {
          $activities[] = $row;
        }
        error_log("Found " . count($activities) . " activities for task $taskId");
      }
    }
  } catch (Exception $e) {
    error_log("Error getting activities: " . $e->getMessage());
    // Continue execution even if activity query fails
  }
} catch (Exception $e) {
  // Handle exceptions gracefully
  die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="../../../public/css/tailwind.css" />
  <style>
      .menuItem {
        margin-bottom: 2rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        width: 100%;
      }
      .menuItem:last-child {
        margin-bottom: 5px;
      }
    .custom-textarea {
      width: 100%;
      height: 200px; /* Điều chỉnh chiều cao theo ý muốn */
      border: 1px solid #e5e7eb; /* Màu viền */
      border-radius: 0.5rem; /* Bo góc */
      padding: 1rem; /* Khoảng cách bên trong */
    }
    /* New edit mode styles */
    .edit-mode {
      border: 2px solid #4f46e5 !important;
      background-color: #f9fafb !important;
    }
  </style>
</head>
<body style="background-color: #f0f2f5;">
  <div class="flex h-screen">
    <!-- Include Sidebar -->
    <?php include_once "../components/Sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Include Header/Topbar -->
      <?php include_once "../components/Header.php"; ?>
      
      <!-- Task Details Content -->
      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <!-- Breadcrumb -->
        <div class="flex items-center mb-6 text-gray-600">
          <a href="ProjectDetail.php?id=<?= $projectId ?>" class="text-indigo-600 font-bold text-xl">Dự án</a>
          <span class="mx-2">›</span>
          <span class="font-bold text-xl"><?= htmlspecialchars($task['ProjectName']) ?></span>
        </div>

        <!-- Task Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <a href="ProjectDetail.php?id=<?= $projectId ?>" class="text-indigo-600 flex items-center font-medium">
              <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Quay lại
            </a>
            <?php if (!$isAdmin): ?>
            <div class="space-x-2">
              <button id="btnDelete" class="bg-red-600 hover:bg-orange-200 text-white px-4 py-2 rounded-md font-semibold">Xóa</button>
              <button id="btnEdit" class="bg-indigo-600 hover:bg-[#2970FF] text-white px-4 py-2 rounded-md font-semibold">Thay đổi</button>
              <button id="btnSave" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-semibold hidden">Lưu</button>
              <button id="btnCancel" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-semibold hidden">Hủy</button>
            </div>
            <?php endif; ?>
          </div>
          
          <h1 class="text-2xl font-bold"><?= htmlspecialchars($task['TaskTitle']) ?></h1>
          <div class="text-gray-600 mt-2">trong danh sách <span id="taskStatus" class="text-indigo-600 font-bold"><?= htmlspecialchars($task['StatusName']) ?></span></div>
          
          <!-- Task Info -->
          <div class="mt-6 grid grid-cols-2 gap-6">
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"></path>
                  </svg>
                  <span>Tag</span>
                </div>
                <?php if (!$isAdmin && isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']): ?>
                <div class="flex items-center">
                  <input id="tagName" type="text" value="<?= htmlspecialchars($task['TagName'] ?? '') ?>" placeholder="Tên tag" class="mr-2 border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent edit-mode" style="max-width: 120px;">
                  <input id="tagColor" type="color" value="<?= htmlspecialchars($task['TagColor'] ?? '#3B82F6') ?>" class="h-8 w-8 border-0 rounded cursor-pointer edit-mode">
                  <div id="tagPreview" class="ml-2 px-3 py-1 rounded-full text-sm text-white" style="background-color: <?= htmlspecialchars($task['TagColor'] ?? '#3B82F6') ?>">
                    <?= htmlspecialchars($task['TagName'] ?? 'Tag mới') ?>
                  </div>
                </div>
                <?php else: ?>
                <?php if (!empty($task['TagName'])): ?>
                <span class="px-3 py-1 rounded-full text-sm text-white" style="background-color: <?= htmlspecialchars($task['TagColor']) ?>">
                  <?= htmlspecialchars($task['TagName']) ?>
                </span>
                <?php else: ?>
                <span class="text-gray-500">Chưa có tag</span>
                <?php endif; ?>
                <?php endif; ?>
              </div>
              
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                  </svg>
                  <span>Độ ưu tiên</span>
                </div>
                <div class="relative">
                  <select id="taskPriority" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
                    <option <?= $task['Priority'] === '' ? 'selected' : '' ?>>Chọn</option>
                    <option <?= $task['Priority'] === 'Khẩn cấp' ? 'selected' : '' ?>>Khẩn cấp</option>
                    <option <?= $task['Priority'] === 'Cao' ? 'selected' : '' ?>>Cao</option>
                    <option <?= $task['Priority'] === 'Trung bình' ? 'selected' : '' ?>>Trung bình</option>
                    <option <?= $task['Priority'] === 'Thấp' ? 'selected' : '' ?>>Thấp</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Ngày</span>
                </div>
                <input id="startDate" type="date" value="<?= $task['StartDate'] ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
                <span class="mx-2">-</span>
                <input id="endDate" type="date" value="<?= $task['EndDate'] ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
              </div>
            </div>
            
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Thành viên</span>
                </div>
                <?php if ($assignee): ?>
                <div class="flex items-center">
                  <img src="../../../<?= htmlspecialchars($assignee['Avatar']) ?>" alt="<?= htmlspecialchars($assignee['FullName']) ?>" class="w-8 h-8 rounded-full">
                  <span class="ml-2"><?= htmlspecialchars($assignee['FullName']) ?></span>
                </div>
                <?php else: ?>
                <span class="text-gray-500">Chưa giao cho ai</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Status Update Buttons (always visible for non-admin) -->
          <?php if (!$isAdmin): ?>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <h3 class="text-lg font-medium mb-2">Cập nhật trạng thái</h3>
            <div class="flex space-x-2">
              <button id="btnMarkTodo" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                Cần làm
              </button>
              <button id="btnMarkInProgress" class="px-3 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                Đang làm
              </button>
              <button id="btnMarkCompleted" class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                Hoàn thành
              </button>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Task Description -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 class="text-xl font-bold mb-4">Mô tả nhiệm vụ</h2>
          <div id="taskDescription" class="custom-textarea p-4 bg-gray-50 rounded-lg" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'contenteditable="false"' : 'contenteditable="true"' ?>>
            <?= nl2br(htmlspecialchars($task['TaskDescription'] ?? 'Không có mô tả')) ?>
          </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-bold mb-4">Hoạt động gần đây</h2>
          <div id="activityList" class="space-y-4">
            <?php if (count($activities) > 0): ?>
              <?php foreach ($activities as $activity): ?>
                <div class="flex items-start">
                  <img src="../../../<?= htmlspecialchars($activity['Avatar']) ?>" alt="User" class="w-8 h-8 rounded-full mr-3">
                  <div>
                    <p class="font-medium"><?= htmlspecialchars($activity['FullName']) ?></p>
                    <p class="text-gray-700">
                      <?php
                      $details = json_decode($activity['Details'], true);
                      $activityTypeMap = [
                        'task_created' => 'đã tạo nhiệm vụ',
                        'task_updated' => 'đã cập nhật nhiệm vụ',
                        'task_assigned' => 'đã giao nhiệm vụ',
                        'task_status_changed' => 'đã thay đổi trạng thái nhiệm vụ',
                        'task_comment' => 'đã bình luận'
                      ];
                      echo $activityTypeMap[$activity['ActivityType']] ?? $activity['ActivityType'];
                      ?>
                    </p>
                    <p class="text-gray-500 text-sm">
                      <?= date('H:i - d/m/Y', strtotime($activity['ActivityTime'])) ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="flex items-start">
                <div>
                  <p class="text-gray-500 text-sm">Hiện không có hoạt động nào</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <!-- Add a success notification element -->
  <div id="notification" class="fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 hidden">
    <div class="flex items-center">
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <span id="notificationMessage">Đã cập nhật thành công!</span>
    </div>
  </div>
</body>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Task information
    const taskId = <?= $taskId ?? 'null' ?>;
    const projectId = <?= $projectId ?? 'null' ?>;
    
    // Edit mode elements
    const btnEdit = document.getElementById('btnEdit');
    const btnSave = document.getElementById('btnSave');
    const btnCancel = document.getElementById('btnCancel');
    const btnDelete = document.getElementById('btnDelete');
    
    // Editable form elements
    const taskPriority = document.getElementById('taskPriority');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const taskDescription = document.getElementById('taskDescription');
    const tagName = document.getElementById('tagName');
    const tagColor = document.getElementById('tagColor');
    const tagPreview = document.getElementById('tagPreview');
    
    // Status elements
    const taskStatus = document.getElementById('taskStatus');
    const btnMarkTodo = document.getElementById('btnMarkTodo');
    const btnMarkInProgress = document.getElementById('btnMarkInProgress');
    const btnMarkCompleted = document.getElementById('btnMarkCompleted');
    
    // Track if we're in edit mode
    let editMode = false;
    
    // Save original values for cancel
    let originalValues = {
      priority: taskPriority?.value || '',
      startDate: startDate?.value || '',
      endDate: endDate?.value || '',
      description: taskDescription?.innerHTML || '',
      tagName: tagName?.value || '',
      tagColor: tagColor?.value || '#3B82F6'
    };
    
    // Function to toggle edit mode
    function toggleEditMode(enabled) {
      editMode = enabled;
      
      // Set session edit mode via fetch call
      fetch('../../../api/task/SetEditMode.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ edit_mode: enabled })
      }).catch(err => console.error('Failed to set edit mode:', err));
      
      // Toggle button visibility
      btnEdit.classList.toggle('hidden', enabled);
      btnSave.classList.toggle('hidden', !enabled);
      btnCancel.classList.toggle('hidden', !enabled);
      btnDelete.classList.toggle('hidden', enabled);
      
      // Toggle field editability (add tag fields)
      if (taskPriority) taskPriority.readOnly = !enabled;
      if (startDate) startDate.readOnly = !enabled;
      if (endDate) endDate.readOnly = !enabled;
      if (taskDescription) taskDescription.contentEditable = enabled.toString();
      
      // Toggle visual edit mode indicators
      if (enabled) {
        if (taskPriority) taskPriority.classList.add('edit-mode');
        if (startDate) startDate.classList.add('edit-mode');
        if (endDate) endDate.classList.add('edit-mode');
        if (taskDescription) taskDescription.classList.add('edit-mode');
      } else {
        if (taskPriority) taskPriority.classList.remove('edit-mode');
        if (startDate) startDate.classList.remove('edit-mode');
        if (endDate) endDate.classList.remove('edit-mode');
        if (taskDescription) taskDescription.classList.remove('edit-mode');
      }
    }
    
    // Event listeners for edit controls
    if (btnEdit) {
      btnEdit.addEventListener('click', function() {
        // Save original values before entering edit mode
        originalValues = {
          priority: taskPriority?.value || '',
          startDate: startDate?.value || '',
          endDate: endDate?.value || '',
          description: taskDescription?.innerHTML || '',
          tagName: tagName?.value || '',
          tagColor: tagColor?.value || '#3B82F6'
        };
        
        toggleEditMode(true);
        logInteraction('edit_mode_enabled', this);
      });
    }
    
    if (btnCancel) {
      btnCancel.addEventListener('click', function() {
        // Restore original values
        if (taskPriority) taskPriority.value = originalValues.priority;
        if (startDate) startDate.value = originalValues.startDate;
        if (endDate) endDate.value = originalValues.endDate;
        if (taskDescription) taskDescription.innerHTML = originalValues.description;
        if (tagName) tagName.value = originalValues.tagName;
        if (tagColor) {
          tagColor.value = originalValues.tagColor;
          tagPreview.style.backgroundColor = originalValues.tagColor;
        }
        if (tagPreview) tagPreview.textContent = originalValues.tagName || 'Tag mới';
        
        toggleEditMode(false);
        logInteraction('edit_cancelled', this);
      });
    }
    
    if (btnSave) {
      btnSave.addEventListener('click', function() {
        saveTaskChanges();
      });
    }
    
    // Function to save task changes
    function saveTaskChanges() {
      if (!taskId) return;
      
      const formData = {
        task_id: taskId,
        priority: taskPriority?.value || '',
        start_date: startDate?.value || '',
        end_date: endDate?.value || '',
        description: taskDescription?.innerText.trim() || '',
        tag_name: tagName?.value || '',
        tag_color: tagColor?.value || ''
      };
      
      // Send data to server for updating
      fetch('../../../api/task/UpdateTask.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Lỗi khi cập nhật nhiệm vụ');
        }
        return response.json();
      })
      .then(result => {
        if (result.success) {
          toggleEditMode(false);
          showNotification('Đã cập nhật nhiệm vụ thành công!');
          logInteraction('task_updated', document.body);
        } else {
          alert('Lỗi: ' + (result.message || 'Không thể cập nhật nhiệm vụ'));
        }
      })
      .catch(error => {
        console.error('Error updating task:', error);
        alert('Lỗi: ' + error.message);
      });
    }
    
    // Function to update task status
    function updateTaskStatus(statusId, statusName) {
      if (!taskId) return;
      
      // Show loading indicator
      const btn = document.querySelector(`button[data-status-id="${statusId}"]`);
      if (btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="animate-pulse">Đang cập nhật...</span>';
        btn.disabled = true;
      }
      
      const formData = {
        task_id: taskId,
        status_id: statusId
      };
      
      console.log('Sending status update:', formData);
      
      // Send data to server for updating status
      fetch('../../../api/task/UpdateTaskStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
      .then(response => {
        console.log('Status update response:', response);
        return response.json();
      })
      .then(result => {
        console.log('Status update result:', result);
        
        // Restore button state
        if (btn) {
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
        
        if (result.success) {
          // Update the UI
          taskStatus.textContent = statusName;
          showNotification(`Đã cập nhật trạng thái thành "${statusName}"!`);
          logInteraction('status_updated', document.body);
          
          // Add new activity to the activity list
          addNewActivity('đã thay đổi trạng thái nhiệm vụ thành ' + statusName);
        } else {
          alert('Lỗi: ' + (result.message || 'Không thể cập nhật trạng thái'));
        }
      })
      .catch(error => {
        console.error('Error updating status:', error);
        
        // Restore button state
        if (btn) {
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
        
        alert('Lỗi: ' + error.message);
      });
    }
    
    // Add event listeners to the status buttons
    if (btnMarkTodo) {
      btnMarkTodo.setAttribute('data-status-id', '1');
      btnMarkTodo.addEventListener('click', function() {
        updateTaskStatus(1, 'Cần làm');
      });
    }
    
    if (btnMarkInProgress) {
      btnMarkInProgress.setAttribute('data-status-id', '2');
      btnMarkInProgress.addEventListener('click', function() {
        updateTaskStatus(2, 'Đang làm');
      });
    }
    
    if (btnMarkCompleted) {
      btnMarkCompleted.setAttribute('data-status-id', '3');
      btnMarkCompleted.addEventListener('click', function() {
        updateTaskStatus(3, 'Hoàn thành');
      });
    }
    
    // Add a new activity to the activity list
    function addNewActivity(actionText) {
      const activityList = document.getElementById('activityList');
      if (!activityList) return;
      
      // Get current user's info
      const userName = '<?= $_SESSION['full_name'] ?? 'Người dùng' ?>';
      const userAvatar = '<?= $_SESSION['avatar'] ?? 'public/uploads/default-avatar.png' ?>';
      const now = new Date();
      const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                        now.getMinutes().toString().padStart(2, '0') + ' - ' + 
                        now.getDate().toString().padStart(2, '0') + '/' + 
                        (now.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                        now.getFullYear();
      
      // Create new activity element
      const newActivity = document.createElement('div');
      newActivity.className = 'flex items-start';
      newActivity.innerHTML = `
        <img src="../../../${userAvatar}" alt="User" class="w-8 h-8 rounded-full mr-3">
        <div>
          <p class="font-medium">${userName}</p>
          <p class="text-gray-700">${actionText}</p>
          <p class="text-gray-500 text-sm">${timeString}</p>
        </div>
      `;
      
      // Remove "no activities" message if present
      const noActivitiesMsg = activityList.querySelector('p.text-gray-500.text-sm');
      if (noActivitiesMsg && noActivitiesMsg.textContent.includes('Hiện không có hoạt động nào')) {
        noActivitiesMsg.parentElement.parentElement.remove();
      }
      
      // Add to the beginning of the list
      if (activityList.firstChild) {
        activityList.insertBefore(newActivity, activityList.firstChild);
      } else {
        activityList.appendChild(newActivity);
      }
    }
    
    // Function to show notification
    function showNotification(message) {
      const notification = document.getElementById('notification');
      const notificationMessage = document.getElementById('notificationMessage');
      
      notificationMessage.textContent = message;
      notification.classList.remove('hidden');
      
      // Animate in
      setTimeout(() => {
        notification.classList.remove('translate-y-20', 'opacity-0');
      }, 10);
      
      // Animate out after 3 seconds
      setTimeout(() => {
        notification.classList.add('translate-y-20', 'opacity-0');
        setTimeout(() => {
          notification.classList.add('hidden');
        }, 300);
      }, 3000);
    }
    
    // Function to log interaction events
    function logInteraction(interactionType, element) {
      // Only log if we have task information
      if (!taskId) return;
      
      // Prepare data for logging
      const data = {
        task_id: taskId,
        interaction_type: interactionType,
        element_id: element?.id || '',
        element_type: element?.tagName || '',
        timestamp: new Date().toISOString()
      };
      
      // Log to console during development
      console.log('Task interaction:', data);
      
      // Send data to server for logging
      fetch('../../../api/task/LogTaskInteraction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(result => {
        console.log('Interaction logged:', result);
      })
      .catch(error => {
        console.error('Error logging interaction:', error);
      });
    }
    
    // Track clicks on task description
    if (taskDescription) {
      taskDescription.addEventListener('click', function() {
        if (!editMode) {
          logInteraction('view_description', this);
        }
      });
    }
    
    // Track clicks on task fields
    if (taskPriority) {
      taskPriority.addEventListener('change', function() {
        logInteraction('change_priority', this);
      });
    }
    
    // Track date field interactions
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
      field.addEventListener('change', function() {
        logInteraction('change_date', this);
      });
    });
    
    // Add click tracking for the back button
    const backButton = document.querySelector('a[href^="ProjectDetail.php"]');
    if (backButton) {
      backButton.addEventListener('click', function(e) {
        // Log before navigating
        logInteraction('back_to_project', this);
        // Don't prevent default - allow navigation to continue
      });
    }
    
    // Add event listener for tag editor
    if (tagName && tagColor && tagPreview) {
      // Update tag preview when name changes
      tagName.addEventListener('input', function() {
        tagPreview.textContent = this.value || 'Tag mới';
        logInteraction('change_tag_name', this);
      });
      
      // Update tag preview color when color changes
      tagColor.addEventListener('input', function() {
        tagPreview.style.backgroundColor = this.value;
        logInteraction('change_tag_color', this);
      });
    }
    
    // Log page load complete
    logInteraction('page_loaded', document.body);
  });
</script>
</html>