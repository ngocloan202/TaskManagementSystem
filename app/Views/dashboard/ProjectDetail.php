<?php

require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

$title = "Chi tiết dự án | CubeFlow";
$currentPage = "dashboard";

// Kiểm tra nếu user là admin hoặc là thành viên của dự án
$projectId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$userID = $_SESSION['user_id'] ?? 0;
$isAdmin = $_SESSION['role'] === 'ADMIN';

if ($projectId <= 0) {
  die("Project ID không hợp lệ");
}

// Nếu không phải admin, kiểm tra xem người dùng có phải là thành viên của dự án không
if (!$isAdmin) {
  $memberCheckStmt = $connect->prepare("
    SELECT COUNT(*) as isMember 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ?
  ");
  $memberCheckStmt->bind_param("ii", $projectId, $userID);
  $memberCheckStmt->execute();
  $isMemberResult = $memberCheckStmt->get_result()->fetch_assoc();

  if (!$isMemberResult || $isMemberResult['isMember'] == 0) {
    die("Bạn không có quyền xem dự án này");
  }
}

// Add near the top of file after database connection
$taskCounts = [];
$stmt = $connect->prepare("
    SELECT ts.StatusName, COUNT(t.TaskID) as TaskCount
    FROM TaskStatus ts
    LEFT JOIN Task t ON ts.TaskStatusID = t.TaskStatusID 
    AND t.ProjectID = ?
    GROUP BY ts.StatusName
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $taskCounts[$row["StatusName"]] = $row["TaskCount"];
}

// 2) Query thông tin project + tính tiến độ
//    tiến độ = completedTasks / totalTasks * 100
$stmt = $connect->prepare("
    SELECT 
      p.ProjectName, 
      p.ProjectDescription,
      COUNT(t.TaskID) AS totalTasks,
      SUM(t.TaskStatusID = 3) AS completedTasks
    FROM Project p
    LEFT JOIN Task t ON t.ProjectID = p.ProjectID
    WHERE p.ProjectID = ?
    GROUP BY p.ProjectID
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$proj = $stmt->get_result()->fetch_assoc();
if (!$proj) {
  die("Không tìm thấy dự án");
}
$progress = $proj["totalTasks"] ? round(($proj["completedTasks"] / $proj["totalTasks"]) * 100) : 0;

// 3) Query danh sách thành viên
$mem = $connect->prepare("
    SELECT u.Avatar 
    FROM ProjectMembers pm
    JOIN Users u ON u.UserID = pm.UserID
    WHERE pm.ProjectID = ?
    ORDER BY pm.RoleInProject = 'người sở hữu' DESC, pm.JoinedAt
");
$mem->bind_param("i", $projectId);
$mem->execute();
$members = $mem->get_result()->fetch_all(MYSQLI_ASSOC);

// 4) Query task theo trạng thái
$taskStmt = $connect->prepare("
    SELECT 
      t.TaskID,
      t.TaskTitle,
      t.TagName,
      t.TagColor,
      DATE_FORMAT(t.EndDate, '%d/%m/%Y') AS dueDate,
      ts.StatusName,
      u.UserID,
      u.FullName AS AssignedToName,
      u.Avatar,
      CASE ts.StatusName
        WHEN 'Cần làm' THEN 'bg-blue-600 text-white'
        WHEN 'Đang làm' THEN 'bg-yellow-600 text-white'
        WHEN 'Đã làm'  THEN 'bg-green-600 text-white'
        ELSE 'bg-gray-400 text-white'
      END AS badgeColor
    FROM Task t
    JOIN TaskStatus ts ON ts.TaskStatusID = t.TaskStatusID
    LEFT JOIN TaskAssignment ta ON ta.TaskID = t.TaskID
    LEFT JOIN Users u ON u.UserID = ta.UserID
    WHERE t.ProjectID = ?
    ORDER BY t.EndDate ASC
");
if (!$taskStmt) {
  die("Lỗi SQL: " . $connect->error);
}
$taskStmt->bind_param("i", $projectId);
$taskStmt->execute();
$allTasks = $taskStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Phân nhóm
$tasksByStatus = [
  "Cần làm" => [],
  "Đang làm" => [],
  "Đã làm" => [],
];
foreach ($allTasks as $tk) {
  $tasksByStatus[$tk["StatusName"]][] = $tk;
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
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
  </style>

</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include_once __DIR__ . "/../components/Sidebar.php"; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
      <?php include_once __DIR__ . "/../components/Header.php"; ?>

      <main class="flex-1 overflow-y-auto p-6">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center text-gray-600">
          <?php if ($isAdmin): ?>
            <?php
            // Xây dựng URL quay lại với các tham số đã lưu
            $backUrl = "../admin/Projects.php";
            $refererParams = $_SESSION['project_view_referer']['params'] ?? [];

            if (!empty($refererParams)) {
              $queryParams = [];
              foreach ($refererParams as $key => $value) {
                $queryParams[] = htmlspecialchars($key) . '=' . htmlspecialchars($value);
              }
              $backUrl .= '?' . implode('&', $queryParams);
            }
            ?>
            <a href="<?= $backUrl ?>" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center">
              Danh sách dự án
            </a>
          <?php else: ?>
            <a href="index.php" class="text-indigo-600 font-bold text-xl">Dự án</a>
          <?php endif; ?>
          <span class="mx-2">›</span>
          <span id="breadcrumbProjectName" class="font-bold text-xl"><?= htmlspecialchars($proj["ProjectName"]) ?></span>
        </div>

        <!-- Project Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 flex items-center justify-between">
          <div id="projectDetailPage" class="relative z-0" data-project-id="<?= $projectId ?>">
            <?php if ($isAdmin): ?>
              <div class="mb-6">
                <a href="<?= $backUrl ?>" class="inline-block border border-gray-300 rounded-md shadow-sm">
                  <div class="flex items-center px-3 py-2 bg-white text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Quay lại</span>
                  </div>
                </a>
              </div>
            <?php endif; ?>
            <div class="flex items-center mb-1">
              <h1 id="projectNameDisplay" class="text-2xl font-semibold"><?= htmlspecialchars($proj["ProjectName"]) ?></h1>
              <?php if (!$isAdmin): ?>
                <button id="editProjectNameBtn" class="ml-2 text-gray-500 hover:text-indigo-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
              <?php endif; ?>
            </div>
            
            <div id="projectNameEditForm" class="hidden mb-3">
              <div class="flex flex-col">
                <div class="flex items-center relative">
                  <div id="projectNameSizer" class="absolute invisible whitespace-pre"></div>
                  <input type="text" id="projectNameInput" 
                    class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                    style="min-width: 200px;" 
                    value="<?= htmlspecialchars($proj["ProjectName"]) ?>">
                </div>
                <div class="flex items-center mt-2">
                  <button id="saveProjectNameBtn" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Lưu</button>
                  <button id="cancelProjectNameBtn" class="px-3 py-2 ml-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Hủy</button>
                </div>
              </div>
              <p id="projectNameError" class="text-red-500 text-sm mt-1 hidden"></p>
            </div>
            
            <!-- Project Description Display -->
            <div class="flex items-center">
              <p id="projectDescriptionDisplay" class="text-gray-600 mb-3"><?= htmlspecialchars($proj["ProjectDescription"]) ?></p>
              <?php if (!$isAdmin): ?>
                <button id="editProjectDescriptionBtn" class="ml-2 text-gray-500 hover:text-indigo-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
              <?php endif; ?>
            </div>
            
            <!-- Project Description Edit Form -->
            <div id="projectDescriptionEditForm" class="hidden mb-3">
              <div class="flex flex-col">
                <textarea id="projectDescriptionInput" 
                  class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full" 
                  rows="3"><?= htmlspecialchars($proj["ProjectDescription"]) ?></textarea>
                <div class="flex items-center mt-2">
                  <button id="saveProjectDescriptionBtn" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Lưu</button>
                  <button id="cancelProjectDescriptionBtn" class="px-3 py-2 ml-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Hủy</button>
                </div>
              </div>
              <p id="projectDescriptionError" class="text-red-500 text-sm mt-1 hidden"></p>
            </div>
            
            <div class="flex items-center">
              <span class="font-medium mr-2">Tiến độ: <?= $progress ?>%</span>
              <div class="w-60 h-2 bg-gray-200 rounded-full mr-4">
                <div class="h-2 bg-green-500 rounded-full" style="width: <?= $progress ?>%"></div>
              </div>
              <button class="px-3 py-1 bg-gray-200 text-indigo-700 rounded">Bảng</button>
              <?php if (!$isAdmin): ?>
                <button id="deleteProjectBtn" class="ml-3 px-3 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 border border-red-200 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Xóa dự án
                </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex items-center -space-x-2">
            <?php foreach ($members as $m): ?>
              <img src="../../../<?= htmlspecialchars($m["Avatar"]) ?>"
                class="w-8 h-8 rounded-full border-2 border-white" />
            <?php endforeach; ?>
            <?php if (!$isAdmin): ?>
              <button id="btnMember" class="ml-4 px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Quản lý thành viên
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Task Columns -->
        <div class="grid grid-cols-3 gap-6">
          <?php
          $columns = [
            "Cần làm" => [
              "color" => "text-blue-600",
              "bg" => "bg-blue-50",
              "hover" => "hover:bg-blue-100",
              "border" => "border-l-4 border-blue-500",
            ],
            "Đang làm" => [
              "color" => "text-yellow-600",
              "bg" => "bg-yellow-50",
              "hover" => "hover:bg-yellow-100",
              "border" => "border-l-4 border-yellow-500",
            ],
            "Đã làm" => [
              "color" => "text-green-600",
              "bg" => "bg-green-50",
              "hover" => "hover:bg-green-100",
              "border" => "border-l-4 border-green-500",
            ],
          ];
          foreach ($columns as $status => $styles):
            $count = $taskCounts[$status] ?? 0; ?>
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                  <h3 class="font-semibold <?= $styles["color"] ?>"><?= $status ?></h3>
                  <span class="ml-2 px-2 py-0.5 rounded-full text-sm font-medium <?= $styles[
                    "bg"
                  ] ?> <?= $styles["color"] ?>">
                    <?= $count ?>
                  </span>
                </div>
                <?php if (!$isAdmin): ?>
                  <button class="p-2 rounded-full <?= $styles["bg"] ?> <?= $styles["color"] ?>
                  hover:bg-opacity-90 transition transform hover:scale-110" onclick="addTask('<?= $status ?>')"
                    title="Thêm nhiệm vụ mới (<?= $status ?>)" id="btnAddTask">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                <?php endif; ?>
              </div>

              <!-- Tasks for this status -->
              <div class="space-y-2">
                <?php foreach ($tasksByStatus[$status] as $task): ?>
                  <div class="p-3 <?= $styles["bg"] ?> rounded-lg <?= $styles[
                       "hover"
                     ] ?> <?= $styles["border"] ?>">
                    <h4 class="font-medium"><?= htmlspecialchars($task["TaskTitle"]) ?></h4>
                    <?php if (!empty($task["TagName"])): ?>
                      <span class="inline-block px-2 py-1 rounded text-white text-xs font-semibold mb-1"
                        style="background-color: <?= htmlspecialchars($task["TagColor"]) ?>">
                        <?= htmlspecialchars($task["TagName"]) ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($task["UserID"]): ?>
                      <div class="mt-2 flex items-center text-sm text-gray-500">
                        <img src="../../..<?= htmlspecialchars(
                          $task["Avatar"]
                        ) ?>" class="w-6 h-6 rounded-full mr-2">
                        <span><?= htmlspecialchars($task["AssignedToName"]) ?></span>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php
          endforeach;
          ?>
        </div>

        <!-- Member Dialog - will be loaded via JavaScript -->
        <div id="memberDialog" class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-auto"
          style="background-color: rgba(0,0,0,0.4); backdrop-filter: blur(2px);">
          <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-auto">
            <div class="relative p-4">
              <button id="closeMemberDialog"
                class="absolute top-4 right-4 text-gray-400 hover:text-red-500 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <div id="memberDialogContent"></div>
            </div>
          </div>
        </div>

        <!-- Create Task Dialog -->
        <div id="createTaskDialog" class="hidden">
          <?php
          $projectId = $projectId; // Ensure projectId is available
          include_once "../tasks/CreateTaskDialog.php";
          ?>
        </div>
      </main>
    </div>
    <script src="../../../public/js/ProjectDetail.js"></script>
    <script src="../../../public/js/dialogManageMembers.js"></script>
    <script src="../../../public/js/projectDescriptionEdit.js"></script>
    <script src="../../../public/js/projectNameEdit.js"></script>
    <script src="../../../public/js/projectDelete.js"></script>
    
    <!-- Delete Project Confirmation Modal -->
    <div id="deleteProjectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-auto" 
         style="background-color: rgba(0,0,0,0.4); backdrop-filter: blur(2px);">
      <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-xl">
        <div class="text-center">
          <h3 class="mt-4 text-xl font-medium text-gray-900">Xác nhận xóa dự án</h3>
          <p class="mt-2 text-gray-500">
            Bạn có chắc chắn muốn xóa dự án "<span id="deleteProjectName" class="font-semibold"></span>" không?
            <br>Tất cả nhiệm vụ và dữ liệu liên quan sẽ bị xóa vĩnh viễn.
            <br>Hành động này không thể hoàn tác.
          </p>
          <div class="mt-4 flex justify-center space-x-4">
            <button id="confirmDeleteProject" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
              Xóa dự án
            </button>
            <button id="cancelDeleteProject" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
              Hủy bỏ
            </button>
          </div>
        </div>
      </div>
    </div>
</body>
</html>