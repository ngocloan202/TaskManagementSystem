<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
$conn = $connect; 
$projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;

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
    
    echo "<script>window.location.reload();</script>";
    exit;
}

$projectStmt = $conn->prepare("SELECT ProjectName FROM Project WHERE ProjectID=?");
$projectStmt->bind_param('i', $projectID);
$projectStmt->execute();
$projectName = $projectStmt->get_result()->fetch_assoc()['ProjectName'] ?? 'Dự án không xác định';

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
  </style>
</head>
<body class="font-sans">
<div class="py-4">
  <div class="rounded-corners">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Quản lý thành viên: <?= htmlspecialchars($projectName) ?></h1>
    <div class="flex flex-col md:flex-row items-center justify-between mb-4">
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
                <img src="<?= htmlspecialchars($row['Avatar']) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
              <?php else: ?>
                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
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
<div id="memberModal" class="fixed inset-0 items-center justify-center bg-opacity-50 hidden">
  <div class="bg-white rounded-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="memberModalLabel" class="text-xl font-semibold text-gray-800">Thêm thành viên</h2>
      <button onclick="toggleModal('memberModal')" class="text-gray-400 hover:text-red-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
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
        <input id="joinedAt" name="JoinedAt" type="datetime-local" required value="<?= date('Y-m-d\TH:i') ?>"
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
  document.getElementById(id).classList.toggle('flex');
}
function openModal(id, memberId) {
  document.getElementById('memberModalLabel').innerText = memberId ? 'Sửa thành viên' : 'Thêm thành viên';
  document.getElementById('projectMemberId').value = memberId || '';
  
  if (memberId) {
    // Fetch member data for editing
    fetch(`GetMemberData.php?id=${memberId}`)
      .then(response => response.json())
      .then(data => {
        if (data) {
          document.getElementById('userSelect').value = data.UserID;
          document.getElementById('roleSelect').value = data.RoleInProject;
          document.getElementById('joinedAt').value = data.JoinedAt.replace(' ', 'T');
        }
      })
      .catch(error => console.error('Error fetching member data:', error));
  }
  
  document.getElementById(id).classList.add('flex');
  document.getElementById(id).classList.remove('hidden');
}
function deleteMember(memberId) {
  if (confirm('Bạn có chắc muốn xóa thành viên này?')) {
    window.location.href = `DeleteMember.php?id=${memberId}&projectID=${projectID}`;
  }
}

// Search functionality
document.getElementById('searchMember').addEventListener('input', function() {
  const searchTerm = this.value.toLowerCase().trim();
  const rows = document.querySelectorAll('tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});

document.getElementById('btnSearch').addEventListener('click', function() {
  const input = document.getElementById('searchMember');
  const event = new Event('input');
  input.dispatchEvent(event);
});

// Allow parent window to close this dialog if needed
window.addEventListener('message', function(event) {
  if (event.data === 'close') {
    window.parent.postMessage('closed', '*');
  }
});
</script>
</body>
</html>
