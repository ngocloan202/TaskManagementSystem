<?php

require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

$title = "Chi tiết dự án | CubeFlow";
$currentPage = "dashboard";

$projectId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($projectId <= 0) {
  die("Project ID không hợp lệ");
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
          <a href="HomePage.php" class="text-indigo-600 font-bold">Dự án</a>
          <span class="mx-2">›</span>
          <span class="font-bold"><?= htmlspecialchars($proj["ProjectName"]) ?></span>
        </div>

        <!-- Project Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 flex items-center justify-between">
          <div id="projectDetailPage" class="relative z-0">
            <h1 class="text-2xl font-semibold mb-1"><?= htmlspecialchars(
              $proj["ProjectName"]
            ) ?></h1>
            <p class="text-gray-600 mb-3"><?= htmlspecialchars($proj["ProjectDescription"]) ?></p>
            <div class="flex items-center">
              <span class="font-medium mr-2">Tiến độ: <?= $progress ?>%</span>
              <div class="w-60 h-2 bg-gray-200 rounded-full mr-4">
                <div class="h-2 bg-green-500 rounded-full" style="width: <?= $progress ?>%"></div>
              </div>
              <button class="px-3 py-1 bg-gray-200 text-indigo-700 rounded">Bảng</button>
            </div>
          </div>
          <div class="flex items-center -space-x-2">
            <?php foreach ($members as $m): ?>
              <img src="../../../<?= htmlspecialchars($m["Avatar"]) ?>"
                class="w-8 h-8 rounded-full border-2 border-white" />
            <?php endforeach; ?>
            <button id="btnMember" class="ml-4 px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
              Quản lý thành viên
            </button>
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
                <button class="p-2 rounded-full <?= $styles["bg"] ?> <?= $styles["color"] ?>
                  hover:bg-opacity-90 transition transform hover:scale-110" onclick="addTask('<?= $status ?>')"
                  title="Thêm nhiệm vụ mới (<?= $status ?>)"
                  id="btnAddTask">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                </button>
              </div>

              <!-- Tasks for this status -->
              <div class="space-y-2">
                <?php foreach ($tasksByStatus[$status] as $task): ?>
                  <div class="p-3 <?= $styles["bg"] ?> rounded-lg <?= $styles["hover"] ?> <?= $styles["border"] ?>">
                    <h4 class="font-medium"><?= htmlspecialchars($task["TaskTitle"]) ?></h4>
                    <?php if (!empty($task['TagName'])): ?>
                      <span class="inline-block px-2 py-1 rounded text-white text-xs font-semibold mb-1"
                            style="background-color: <?= htmlspecialchars($task['TagColor']) ?>">
                        <?= htmlspecialchars($task['TagName']) ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($task["UserID"]): ?>
                      <div class="mt-2 flex items-center text-sm text-gray-500">
                        <img src="../../..<?= htmlspecialchars($task["Avatar"]) ?>" class="w-6 h-6 rounded-full mr-2">
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

        <script>
          // Khi click Quản lý thành viên thì show dialog (bạn tự thêm dialog HTML)
          document.getElementById('btnMember').addEventListener('click', () => {
            document.getElementById('memberDialog').classList.remove('hidden');
          });

          function addTask(statusName) {
            document.getElementById('createTaskDialog').classList.remove('hidden');
            document.getElementById('statusField').value = statusName;
          }
          
          // Đóng dialog khi nhấn nút đóng
          document.addEventListener('DOMContentLoaded', function() {
            // Tìm nút đóng trong dialog
            const closeButton = document.querySelector('#createTaskDialog button[class*="hover:bg-indigo-500"]');
            if (closeButton) {
              closeButton.addEventListener('click', function() {
                document.getElementById('createTaskDialog').classList.add('hidden');
              });
            }
          });
        </script>

        <!-- Create Task Dialog -->
        <div id="createTaskDialog" class="hidden">
          <?php 
          $projectId = $projectId; // Ensure projectId is available
          include_once "../tasks/CreateTaskDialog.php"; 
          ?>
        </div>
      </main>
    </div>
</body>
</html>