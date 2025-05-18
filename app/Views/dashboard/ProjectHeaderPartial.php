<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

$projectId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($projectId <= 0) {
  die("Project ID không hợp lệ");
}

// Get project info
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

// Query member list for avatars
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
?>

<!-- Project Header -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6 flex items-center justify-between">
  <div id="projectDetailPage" class="relative z-0">
    <h1 class="text-2xl font-semibold mb-1"><?= htmlspecialchars($proj["ProjectName"]) ?></h1>
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
  </div>
</div> 