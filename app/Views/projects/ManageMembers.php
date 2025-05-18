<?php
// File: app/Views/projects/ManageMembers.php
session_start();
require_once __DIR__ . '/../../../config/database.php';
$conn = $connect; // Kết nối DB
$projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;

// Xử lý POST thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pmID = !empty($_POST['ProjectMembersID']) ? (int)$_POST['ProjectMembersID'] : null;
    $userID = (int)$_POST['UserID'];
    $role   = $_POST['RoleInProject'];
    $joined = $_POST['JoinedAt'];
    if ($pmID) {
        $stmt = $conn->prepare("UPDATE ProjectMembers SET UserID=?, RoleInProject=?, JoinedAt=? WHERE ProjectMembersID=?");
        $stmt->bind_param('issi', $userID, $role, $joined, $pmID);
    } else {
        $stmt = $conn->prepare("INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) VALUES (?,?,?,?)");
        $stmt->bind_param('iiss', $projectID, $userID, $role, $joined);
    }
    $stmt->execute();
    header("Location: ?projectID={$projectID}");
    exit;
}

// Lấy danh sách thành viên và người dùng
$memberStmt = $conn->prepare("SELECT pm.ProjectMembersID, u.FullName, u.Username, u.Avatar, pm.RoleInProject, pm.JoinedAt
    FROM ProjectMembers pm JOIN Users u ON pm.UserID=u.UserID WHERE pm.ProjectID=?");
$memberStmt->bind_param('i', $projectID);
$memberStmt->execute();
$members = $memberStmt->get_result();
$usersResult = $conn->query("SELECT UserID, FullName FROM Users ORDER BY FullName");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý thành viên</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.2.4/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
<div class="container mx-auto py-8">
  <div class="bg-white shadow-lg rounded-lg p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Quản lý thành viên</h1>
    <div class="flex flex-col md:flex-row items-center justify-between mb-6">
      <div class="flex w-full md:w-1/2 mb-4 md:mb-0">
        <input id="searchMember" type="text" placeholder="Tìm kiếm thành viên..."
               class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        <button id="btnSearch" class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition">
          Tìm
        </button>
      </div>
      <button onclick="openModal('memberModal', 0)"
              class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
        Thêm thành viên
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
              <?php if ($row['Avatar']): ?>
                <img src="<?= htmlspecialchars($row['Avatar']) ?>" alt="Avatar" class="w-10 h-10 rounded-full">
              <?php else: ?>
                <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row['FullName']) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row['Username']) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row['RoleInProject']) ?></td>
            <td class="px-4 py-2 text-gray-700"><?= date('Y-m-d H:i', strtotime($row['JoinedAt'])) ?></td>
            <td class="px-4 py-2 space-x-2">
              <button onclick="openModal('memberModal', <?= $row['ProjectMembersID'] ?>)"
                      class="px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition">Sửa</button>
              <button onclick="deleteMember(<?= $row['ProjectMembersID'] ?>)"
                      class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">Xóa</button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Thêm/Sửa thành viên -->
<div id="memberModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="memberModalLabel" class="text-xl font-semibold text-gray-800">Thêm thành viên</h2>
      <button onclick="toggleModal('memberModal')" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="ProjectID" value="<?= $projectID ?>">
      <input type="hidden" name="ProjectMembersID" id="projectMemberId" value="">
      <div>
        <label for="userSelect" class="block text-gray-700 mb-1">Người dùng</label>
        <select id="userSelect" name="UserID" required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-300">
          <option value="">Chọn người dùng</option>
          <?php mysqli_data_seek($usersResult->result_id, 0);
          while ($u = $usersResult->fetch_assoc()): ?>
          <option value="<?= $u['UserID'] ?>"><?= htmlspecialchars($u['FullName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label for="roleSelect" class="block text-gray-700 mb-1">Vai trò</label>
        <select id="roleSelect" name="RoleInProject" required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-300">
          <option value="thành viên">thành viên</option>
          <option value="người sở hữu">người sở hữu</option>
        </select>
      </div>
      <div>
        <label for="joinedAt" class="block text-gray-700 mb-1">Ngày tham gia</label>
        <input id="joinedAt" name="JoinedAt" type="datetime-local" required
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-300">
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="toggleModal('memberModal')"
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">Hủy</button>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
const projectID = <?= json_encode($projectID) ?>;
function toggleModal(id) {
  document.getElementById(id).classList.toggle('hidden');
}
function openModal(id, memberId) {
  document.getElementById('memberModalLabel').innerText = memberId ? 'Sửa thành viên' : 'Thêm thành viên';
  document.getElementById('projectMemberId').value = memberId || '';
  // TODO: nếu sửa, fetch AJAX để điền form
  toggleModal(id);
}
function deleteMember(memberId) {
  if (confirm('Bạn có chắc muốn xóa thành viên này?')) {
    window.location.href = `delete_member.php?id=${memberId}&projectID=${projectID}`;
  }
}
</script>
</body>
</html>
