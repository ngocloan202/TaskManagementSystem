<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/Database.php";

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
        $projectIds[] = $row['ProjectID'];
    }
    $statement->close();

    if (!empty($projectIds)) {
        // thay cho WHERE t.ProjectID IN (?, ?, ?...)  
        $placeholders = str_repeat('?,', count($projectIds) - 1) . '?';
        
        // Get status changes
        $statusQuery = "SELECT 
                        tsh.TaskStatusHistoryID,
                        tsh.ChangedAt,
                        t.TaskTitle,
                        u.FullName,
                        oldStatus.StatusName as OldStatus,
                        newStatus.StatusName as NewStatus,
                        'status_change' as activity_type
                    FROM TaskStatusHistory tsh
                    JOIN Task t ON tsh.TaskID = t.TaskID
                    JOIN Users u ON tsh.ChangedBy = u.UserID
                    JOIN TaskStatus oldStatus ON tsh.OldStatusID = oldStatus.TaskStatusID
                    JOIN TaskStatus newStatus ON tsh.NewStatusID = newStatus.TaskStatusID
                    WHERE t.ProjectID IN ($placeholders)
                    ORDER BY tsh.ChangedAt DESC
                    LIMIT 5";

        $statement = $connect->prepare($statusQuery);
        $types = str_repeat('i', count($projectIds));
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

        $stmt = $connect->prepare($assignmentQuery);
        $stmt->bind_param($types, ...$projectIds);
        $stmt->execute();
        $assignmentResult = $stmt->get_result();
        while ($row = $assignmentResult->fetch_assoc()) {
            $activities[] = $row;
        }
        $stmt->close();

        // Sort all activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['ChangedAt']) - strtotime($a['ChangedAt']);
        });

        // Take only the 10 most recent activities
        $activities = array_slice($activities, 0, 10);
    }
}
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-bold mb-4">Hoạt động gần đây</h2>
    <div class="space-y-4">
        <?php if (empty($activities)): ?>
            <div class="text-gray-500 text-sm">Hiện không có hoạt động nào</div>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="flex items-start space-x-3">
                    <?php if ($activity['activity_type'] === 'status_change'): ?>
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm">
                                <span class="font-semibold"><?= htmlspecialchars($activity['FullName']) ?></span>
                                đã thay đổi trạng thái của nhiệm vụ
                                <span class="font-semibold"><?= htmlspecialchars($activity['TaskTitle']) ?></span>
                                từ <span class="text-gray-600"><?= htmlspecialchars($activity['OldStatus']) ?></span>
                                thành <span class="text-gray-600"><?= htmlspecialchars($activity['NewStatus']) ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= date('d/m/Y H:i', strtotime($activity['ChangedAt'])) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm">
                                <span class="font-semibold"><?= htmlspecialchars($activity['FullName']) ?></span>
                                đã giao nhiệm vụ
                                <span class="font-semibold"><?= htmlspecialchars($activity['TaskTitle']) ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= date('d/m/Y H:i', strtotime($activity['ChangedAt'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
