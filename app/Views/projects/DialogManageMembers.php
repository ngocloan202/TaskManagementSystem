<?php
require_once __DIR__ . "/../../../config/SessionInit.php";
require_once __DIR__ . "/../../../config/database.php";

$projectID = isset($_GET["projectID"]) ? (int) $_GET["projectID"] : 0;
$currentUserID = $_SESSION["user_id"] ?? 0;
$isEmbedded = isset($_GET["embed"]) && $_GET["embed"] == "1";

$userRoleStmt = $connect->prepare("
    SELECT RoleInProject 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ?
");
$userRoleStmt->bind_param("ii", $projectID, $currentUserID);
$userRoleStmt->execute();
$isOwner = $userRoleStmt->get_result()->fetch_assoc()["RoleInProject"] === "người sở hữu";

$projectStmt = $connect->prepare("SELECT ProjectName FROM Project WHERE ProjectID = ?");
$projectStmt->bind_param("i", $projectID);
$projectStmt->execute();
$projectName = $projectStmt->get_result()->fetch_assoc()["ProjectName"] ?? "Dự án không xác định";

$memberStmt = $connect->prepare("
    SELECT 
        pm.ProjectMembersID,
        u.UserID,
        u.FullName,
        u.Username,
        u.Avatar,
        pm.RoleInProject,
        pm.JoinedAt
    FROM ProjectMembers pm 
    JOIN Users u ON pm.UserID = u.UserID 
    WHERE pm.ProjectID = ?
    ORDER BY pm.RoleInProject = 'người sở hữu' DESC, pm.JoinedAt
");
$memberStmt->bind_param("i", $projectID);
$memberStmt->execute();
$members = $memberStmt->get_result();

// Get list of users who are not members yet
$availableUsersStmt = $connect->prepare("
    SELECT UserID, FullName 
    FROM Users 
    WHERE UserID NOT IN (
        SELECT UserID 
        FROM ProjectMembers 
        WHERE ProjectID = ?
    )
    ORDER BY FullName
");
$availableUsersStmt->bind_param("i", $projectID);
$availableUsersStmt->execute();
$availableUsers = $availableUsersStmt->get_result();
?>

<!-- Set global projectID variable -->
<script>
    window.projectID = <?= $projectID ?>;
</script>

<!-- HTML Template -->
<div class="py-4" data-project-id="<?= $projectID ?>">
    <div class="rounded-corners">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">
            Quản lý thành viên: <?= htmlspecialchars($projectName) ?>
        </h1>

        <!-- Search and Add Member -->
        <div class="flex flex-col md:flex-row items-center justify-between mb-4">
            <div class="flex w-full md:w-1/2 mb-4 md:mb-0">
                <input id="searchMember" type="text" 
                       placeholder="Tìm kiếm thành viên..."
                       class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button id="btnSearch" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition">
                    Tìm
                </button>
            </div>
            <button id="btnAddMember" 
                    class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                Thêm thành viên
            </button>
        </div>

        <!-- Members Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Avatar</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Họ & Tên</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Vai trò</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Ngày tham gia</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $members->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50" data-member-id="<?= $row["ProjectMembersID"] ?>">
                        <td class="px-4 py-2">
                            <?php if ($row["Avatar"]): ?>
                                <img src="<?= htmlspecialchars($row["Avatar"]) ?>" 
                                     alt="Avatar" 
                                     class="w-8 h-8 rounded-full">
                            <?php else: ?>
                                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["FullName"]) ?></td>
                        <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["Username"]) ?></td>
                        <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["RoleInProject"]) ?></td>
                        <td class="px-4 py-2 text-gray-700">
                            <?= date("d/m/Y H:i", strtotime($row["JoinedAt"])) ?>
                        </td>
                        <td class="px-4 py-2 space-x-2">
                            <?php if ($isOwner && $currentUserID != $row["UserID"]): ?>
                            <button onclick="deleteMember(<?= $row["ProjectMembersID"] ?>, '<?= htmlspecialchars($row["FullName"]) ?>')"
                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                Xóa
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="memberModal" class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-opacity-50" style="background-color: rgba(0, 0, 0, 0.4);">
    <div class="bg-white rounded-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="memberModalLabel" class="text-xl font-semibold text-gray-800">Thêm thành viên</h2>
            <button id="closeMemberModal" type="button" onclick="document.getElementById('memberModal').classList.add('hidden'); document.body.style.overflow = 'auto';" class="text-gray-400 hover:text-red-500 focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="memberForm" onsubmit="return false;">
            <input type="hidden" name="projectId" value="<?= $projectID ?>">
            
            <div class="mb-4">
                <label for="userSelect" class="block text-sm font-medium text-gray-700 mb-1">
                    Chọn người dùng
                </label>
                <select id="userSelect" name="userId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Chọn người dùng --</option>
                    <?php while ($user = $availableUsers->fetch_assoc()): ?>
                    <option value="<?= $user["UserID"] ?>">
                        <?= htmlspecialchars($user["FullName"]) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($isOwner): ?>
            <div class="mb-4">
                <label for="roleSelect" class="block text-sm font-medium text-gray-700 mb-1">
                    Vai trò
                </label>
                <select id="roleSelect" name="roleId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="2">Thành viên</option>
                    <option value="1">Quản lý</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="roleId" value="2">
            <?php endif; ?>

            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelMemberModal" onclick="document.getElementById('memberModal').classList.add('hidden'); document.body.style.overflow = 'auto';" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">
                    Hủy
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete confirmation modal (should be placed at the end of the page, before </body>) -->
<div id="confirmDeleteModal" style="background-color: rgba(0, 0, 0, 0.4);" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
    <div class="mb-4 text-lg font-semibold text-gray-800" id="confirmDeleteMessage"></div>
    <div class="flex justify-end space-x-3">
      <button id="cancelDeleteBtn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">Hủy</button>
      <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">Xóa</button>
    </div>
  </div>
</div>

<!-- Your JS scripts -->
<script src="../../../public/js/dialogManageMembers.js"></script>
</body>
</html>
