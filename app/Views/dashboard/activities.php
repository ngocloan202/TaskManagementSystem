<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/Database.php";
$currentPage = "activities";

$userId = $_SESSION["user_id"] ?? null;

$activities = [];
if ($userId) {
  $projectQuery = "SELECT p.ProjectID 
                    FROM ProjectMembers pm, Project p
                    WHERE pm.ProjectID = p.ProjectID and pm.UserID = ?";
  $statement = $connect->prepare($projectQuery);
  $statement->bind_param("i", $userId);
  $statement->execute();
  $projectResult = $statement->get_result();
  $projectIds = [];
  while ($row = $projectResult->fetch_assoc()) {
    $projectIds[] = $row["ProjectID"];
  }
  $statement->close();

  if (!empty($projectIds)) {
    // thay cho WHERE t.ProjectID IN (?, ?, ?...)
    $placeholders = str_repeat("?,", count($projectIds) - 1) . "?";

    // Get status changes
    $statusQuery = "SELECT 
                        tsh.TaskStatusHistoryID,
                        tsh.ChangedAt,
                        t.TaskTitle,
                        u.FullName,
                        oldStatus.StatusName as OldStatus,
                        newStatus.StatusName as NewStatus,
                        'statusChange' as activityType
                    FROM TaskStatusHistory tsh
                    JOIN Task t ON tsh.TaskID = t.TaskID
                    JOIN Users u ON tsh.ChangedBy = u.UserID
                    JOIN TaskStatus oldStatus ON tsh.OldStatusID = oldStatus.TaskStatusID
                    JOIN TaskStatus newStatus ON tsh.NewStatusID = newStatus.TaskStatusID
                    WHERE t.ProjectID IN ($placeholders)
                    ORDER BY tsh.ChangedAt DESC
                    LIMIT 5";

    $statement = $connect->prepare($statusQuery);
    $types = str_repeat("i", count($projectIds));
    $statement->bind_param($types, ...$projectIds);
    $statement->execute();
    $statusResult = $statement->get_result();
    while ($row = $statusResult->fetch_assoc()) {
      $activities[] = $row;
    }
    $statement->close();

    $assignmentQuery = "SELECT 
                            ta.AssignmentID,
                            ta.AssignedAt as ChangedAt,
                            t.TaskTitle,
                            u.FullName,
                            'assignment' as activityType
                        FROM TaskAssignment ta
                        JOIN Task t ON ta.TaskID = t.TaskID
                        JOIN Users u ON ta.AssignedBy = u.UserID
                        WHERE t.ProjectID IN ($placeholders)
                        ORDER BY ta.AssignedAt DESC
                        LIMIT 5";

    $statement = $connect->prepare($assignmentQuery);
    $statement->bind_param($types, ...$projectIds);
    $statement->execute();
    $assignmentResult = $statement->get_result();
    while ($row = $assignmentResult->fetch_assoc()) {
      $activities[] = $row;
    }
    $statement->close();

    // Sort all activities by date
    usort($activities, function ($a, $b) {
      return strtotime($b["ChangedAt"]) - strtotime($a["ChangedAt"]);
    });

    $activities = array_slice($activities, 0, 10);
  }
}

function getActivityIcon($type)
{
  switch ($type) {
    case "statusChange":
      // icon đổi trạng thái
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6">
  <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0 1 12.548-3.364l1.903 1.903h-3.183a.75.75 0 1 0 0 1.5h4.992a.75.75 0 0 0 .75-.75V4.356a.75.75 0 0 0-1.5 0v3.18l-1.9-1.9A9 9 0 0 0 3.306 9.67a.75.75 0 1 0 1.45.388Zm15.408 3.352a.75.75 0 0 0-.919.53 7.5 7.5 0 0 1-12.548 3.364l-1.902-1.903h3.183a.75.75 0 0 0 0-1.5H2.984a.75.75 0 0 0-.75.75v4.992a.75.75 0 0 0 1.5 0v-3.18l1.9 1.9a9 9 0 0 0 15.059-4.035.75.75 0 0 0-.53-.918Z" clip-rule="evenodd"></path>
</svg>';
    case "assignment":
      // icon phân công
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6">
  <path d="M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.01.75.75 0 0 0 .42-.643 4.875 4.875 0 0 0-6.957-4.611 8.586 8.586 0 0 1 1.71 5.157v.003Z"></path>
</svg>';
    case "addMember":
      // icon thêm thành viên
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6">
  <path d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z"></path>
</svg>';
    // Thêm các loại khác nếu có
    default:
      // icon mặc định
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6">
  <path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z"></path>
  <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd"></path>
</svg>';
  }
}

function getStatusColor($status)
{
  switch (mb_strtolower($status)) {
    case "cần làm":
      return "text-blue-600";
    case "đang làm":
      return "text-yellow-600";
    case "đã làm":
      return "text-green-600";
    default:
      return "text-gray-600";
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hoạt động | CubeFlow</title>
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
<body style="background-color: #d9d9d9">
    <div class="flex h-screen">
        <?php include "../components/Sidebar.php"; ?>
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold mb-4">Hoạt động gần đây</h2>
                    <div class="space-y-4">
                        <?php if (empty($activities)): ?>
                            <div class="text-gray-500 text-sm">Hiện không có hoạt động nào</div>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="flex items-start space-x-3 bg-white rounded-xl shadow p-4 mb-2 hover:bg-[#FFE2D2] transition border-l-4 border-blue-500">
                                    <?php if ($activity["activityType"] === "statusChange"): ?>
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <?= getActivityIcon("statusChange") ?>
                                        </div>
                                        <div>
                                            <p class="text-sm">
                                                <span class="font-semibold"><?= htmlspecialchars(
                                                  $activity["FullName"]
                                                ) ?></span>
                                                đã thay đổi trạng thái của nhiệm vụ
                                                <span class="font-semibold"><?= htmlspecialchars(
                                                  $activity["TaskTitle"]
                                                ) ?></span>
                                                từ <span class="font-bold <?= getStatusColor(
                                                  $activity["OldStatus"]
                                                ) ?>">
                                                    <?= htmlspecialchars($activity["OldStatus"]) ?>
                                                </span>
                                                thành <span class="font-bold <?= getStatusColor(
                                                  $activity["NewStatus"]
                                                ) ?>">
                                                    <?= htmlspecialchars($activity["NewStatus"]) ?>
                                                </span>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?= date(
                                                  "d/m/Y H:i",
                                                  strtotime($activity["ChangedAt"])
                                                ) ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <?= getActivityIcon("assignment") ?>
                                        </div>
                                        <div>
                                            <p class="text-sm">
                                                <span class="font-semibold"><?= htmlspecialchars(
                                                  $activity["FullName"]
                                                ) ?></span>
                                                đã giao nhiệm vụ
                                                <span class="font-semibold"><?= htmlspecialchars(
                                                  $activity["TaskTitle"]
                                                ) ?></span>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?= date(
                                                  "d/m/Y H:i",
                                                  strtotime($activity["ChangedAt"])
                                                ) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
