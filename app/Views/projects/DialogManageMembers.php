<?php
require_once __DIR__ . "/../../../config/SessionInit.php";
require_once __DIR__ . "/../../../config/database.php";
$conn = $connect;
$projectID = isset($_GET["projectID"]) ? (int) $_GET["projectID"] : 0;
$currentUserID = $_SESSION["user_id"] ?? 0;
$isEmbedded = isset($_GET["embed"]) && $_GET["embed"] == "1";

// Kiểm tra vai trò của người dùng hiện tại trong dự án
$userRoleStmt = $conn->prepare(
  "SELECT RoleInProject FROM ProjectMembers WHERE ProjectID = ? AND UserID = ?"
);
$userRoleStmt->bind_param("ii", $projectID, $currentUserID);
$userRoleStmt->execute();
$userRoleResult = $userRoleStmt->get_result();
$isOwner = false;

if ($userRoleResult->num_rows > 0) {
  $userRole = $userRoleResult->fetch_assoc()["RoleInProject"];
  $isOwner = $userRole === "người sở hữu";
}

$projectStmt = $conn->prepare("SELECT ProjectName FROM Project WHERE ProjectID=?");
$projectStmt->bind_param("i", $projectID);
$projectStmt->execute();
$projectName = $projectStmt->get_result()->fetch_assoc()["ProjectName"] ?? "Dự án không xác định";

$memberStmt = $conn->prepare("SELECT pm.ProjectMembersID, u.UserID, u.FullName, u.Username, u.Avatar, pm.RoleInProject, pm.JoinedAt
    FROM ProjectMembers pm JOIN Users u ON pm.UserID=u.UserID WHERE pm.ProjectID=?");
$memberStmt->bind_param("i", $projectID);
$memberStmt->execute();
$members = $memberStmt->get_result();
$usersResult = $conn->query("SELECT UserID, FullName FROM Users ORDER BY FullName");

// Only output the full HTML structure if not embedded
if (!$isEmbedded): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý thành viên</title>
  <link href="../../../public/css/tailwind.css" rel="stylesheet">
  <style>
    body {
      background-color: white;
      padding: 0.5rem;
      margin: 0;
    }
    .rounded-corners {
      border-radius: 8px;
    }
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: white;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      padding: 20px;
      max-height: 90vh;
      overflow-y: auto;
    }
    table {
      table-layout: fixed;
      width: 100%;
    }
    .overflow-x-auto {
      overflow-x: auto;
      scrollbar-width: thin;
    }
    th, td {
      word-break: break-word;
    }
  </style>
</head>
<body class="font-sans">
<?php endif;
?>

<div class="py-4">
  <div class="rounded-corners">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Quản lý thành viên: <?= htmlspecialchars(
      $projectName
    ) ?></h1>
    <div class="flex flex-col md:flex-row items-center justify-between mb-4">
      <div class="flex w-full md:w-1/2 mb-4 md:mb-0">
        <input id="searchMember" type="text" placeholder="Tìm kiếm thành viên..."
               class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        <button id="btnSearch" class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition">
          Tìm
        </button>
      </div>
      <!-- Primary button for opening the modal -->
      <button id="btnAddMember" onclick="openModal('memberModal', 0)" 
              class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
        Thêm thành viên
      </button>
      <!-- Alternative button with direct toggle -->
      <button id="btnAddMemberAlt" onclick="toggleModal()" type="button" 
              class="ml-2 px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition" 
              style="display:none;">
        Thêm thành viên (Alt)
      </button>
    </div>
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
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2">
              <?php if ($row["Avatar"]): ?>
                <img src="<?= htmlspecialchars(
                  $row["Avatar"]
                ) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
              <?php else: ?>
                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["FullName"]) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["Username"]) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row["RoleInProject"]) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= date(
              "d/m/Y H:i",
              strtotime($row["JoinedAt"])
            ) ?></td>
            <td class="px-4 py-2 space-x-2">
              <?php if ($isOwner && $currentUserID != $row["UserID"]): ?>
              <button onclick="deleteMember(<?= $row["ProjectMembersID"] ?>, '<?= htmlspecialchars(
  $row["FullName"]
) ?>')"
                      data-id="<?= $row["ProjectMembersID"] ?>"
                      data-member-id="<?= $row["ProjectMembersID"] ?>"
                      class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">Xóa</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Thêm thành viên -->
<div id="memberModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; overflow:auto;">
  <div style="background-color:white; border-radius:8px; width:90%; max-width:500px; padding:20px; max-height:90vh; overflow-y:auto; position:relative;">
    <div class="flex justify-between items-center mb-4">
      <h2 id="memberModalLabel" class="text-xl font-semibold text-gray-800">Thêm thành viên</h2>
      <button type="button" onclick="toggleModal()" class="text-gray-400 hover:text-red-500 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form id="memberForm" onsubmit="return submitMemberForm(this);" method="POST">
      <input type="hidden" name="projectId" value="<?php echo $projectID; ?>">
      <div class="mb-4">
        <label for="userSelect" class="block text-sm font-medium text-gray-700 mb-1">Chọn người dùng</label>
        <select id="userSelect" name="userId" class="w-full px-3 py-2 border border-gray-300 rounded-md 
               focus:outline-none focus:ring-blue-500 focus:border-blue-500">
          <option value="">-- Chọn người dùng --</option>
          <?php
          // Lấy danh sách user chưa là thành viên của project
          $userQuery =
            "SELECT UserID, FullName FROM Users WHERE UserID NOT IN (SELECT UserID FROM ProjectMembers WHERE ProjectID = ?) ORDER BY FullName";
          $stmt = $connect->prepare($userQuery);
          $stmt->bind_param("i", $projectID);
          $stmt->execute();
          $result = $stmt->get_result();
          while ($row = $result->fetch_assoc()) {
            echo "<option value='" .
              $row["UserID"] .
              "'>" .
              htmlspecialchars($row["FullName"]) .
              "</option>";
          }
          ?>
        </select>
      </div>
      <?php if ($isOwner): ?>
      <div class="mb-4">
        <label for="roleSelect" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
        <select id="roleSelect" name="roleId" class="w-full px-3 py-2 border border-gray-300 rounded-md 
               focus:outline-none focus:ring-blue-500 focus:border-blue-500">
          <option value="2">Thành viên</option>
          <option value="1">Quản lý</option>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" name="roleId" value="2">
      <?php endif; ?>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="toggleModal()"
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">Hủy</button>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
  window.projectID = <?= json_encode($projectID) ?>;
  window.isOwner = <?= json_encode($isOwner) ?>;
  window.isEmbedded = <?= json_encode($isEmbedded) ?>;
</script>
<script src="../../../public/js/dialogManageMembers.js"></script>

<?php if (!$isEmbedded): ?>
</body>
</html>
<?php endif; ?>
