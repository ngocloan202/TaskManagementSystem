<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
$conn = $connect; 
$projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;
$currentUserID = $_SESSION["user_id"] ?? 0;
$isEmbedded = isset($_GET['embed']) && $_GET['embed'] == '1';

// Kiểm tra vai trò của người dùng hiện tại trong dự án
$userRoleStmt = $conn->prepare("SELECT RoleInProject FROM ProjectMembers WHERE ProjectID = ? AND UserID = ?");
$userRoleStmt->bind_param('ii', $projectID, $currentUserID);
$userRoleStmt->execute();
$userRoleResult = $userRoleStmt->get_result();
$isOwner = false;

if ($userRoleResult->num_rows > 0) {
    $userRole = $userRoleResult->fetch_assoc()["RoleInProject"];
    $isOwner = ($userRole === 'người sở hữu');
}

$projectStmt = $conn->prepare("SELECT ProjectName FROM Project WHERE ProjectID=?");
$projectStmt->bind_param('i', $projectID);
$projectStmt->execute();
$projectName = $projectStmt->get_result()->fetch_assoc()['ProjectName'] ?? 'Dự án không xác định';

$memberStmt = $conn->prepare("SELECT pm.ProjectMembersID, u.UserID, u.FullName, u.Username, u.Avatar, pm.RoleInProject, pm.JoinedAt
    FROM ProjectMembers pm JOIN Users u ON pm.UserID=u.UserID WHERE pm.ProjectID=?");
$memberStmt->bind_param('i', $projectID);
$memberStmt->execute();
$members = $memberStmt->get_result();
$usersResult = $conn->query("SELECT UserID, FullName FROM Users ORDER BY FullName");

// Only output the full HTML structure if not embedded
if (!$isEmbedded):
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
<?php endif; ?>

<?php include_once(__DIR__ . '/../components/Notification.php'); ?>

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
            <td class="px-4 py-2 text-gray-700"><?= date('d/m/Y H:i', strtotime($row['JoinedAt'])) ?></td>
            <td class="px-4 py-2 space-x-2">
              <?php if ($isOwner || ($currentUserID == $row['UserID'])): ?>
              <button onclick="openModal('memberModal', <?= $row['ProjectMembersID'] ?>)"
                      data-id="<?= $row['ProjectMembersID'] ?>"
                      class="px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition">Sửa</button>
              <?php if ($isOwner && $currentUserID != $row['UserID']): ?>
              <button onclick="deleteMember(<?= $row['ProjectMembersID'] ?>)"
                      data-id="<?= $row['ProjectMembersID'] ?>"
                      class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">Xóa</button>
              <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Thêm/Sửa thành viên -->
<div id="memberModal" class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="memberModalLabel" class="text-xl font-semibold text-gray-800">Thêm thành viên</h2>
      <button onclick="toggleModal('memberModal')" class="text-gray-400 hover:text-red-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form id="memberForm" method="POST" class="space-y-4" onsubmit="submitMemberForm(event)">
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
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-300" 
                <?= !$isOwner ? 'disabled' : '' ?>>
          <option value="thành viên">thành viên</option>
          <?php if ($isOwner): ?>
          <option value="người sở hữu">người sở hữu</option>
          <?php endif; ?>
        </select>
        <?php if (!$isOwner): ?>
        <input type="hidden" name="RoleInProject" value="thành viên">
        <p class="text-sm text-gray-500 mt-1">Chỉ người sở hữu dự án mới có thể phân quyền người sở hữu.</p>
        <?php endif; ?>
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
const isOwner = <?= json_encode($isOwner) ?>;
const isEmbedded = <?= json_encode($isEmbedded) ?>;
let currentFormAction = '';

function toggleModal(id) {
  document.getElementById(id).classList.toggle('hidden');
  document.getElementById(id).classList.toggle('flex');
}

function openModal(id, memberId) {
  const isEditing = Boolean(memberId);
  document.getElementById('memberModalLabel').innerText = isEditing ? 'Sửa thành viên' : 'Thêm thành viên';
  document.getElementById('projectMemberId').value = memberId || '';
  
  // Set the form action based on whether we're adding or editing
  currentFormAction = isEditing ? 'EditMemberProcess.php' : 'AddMemberProcess.php';
  
  if (memberId) {
    // Fetch member data for editing
    fetch(`GetMemberByID.php?id=${memberId}`)
      .then(response => response.json())
      .then(data => {
        if (data) {
          document.getElementById('userSelect').value = data.UserID;
          
          // Nếu có role selector (cho người sở hữu)
          const roleSelect = document.getElementById('roleSelect');
          if (!roleSelect.disabled) {
            roleSelect.value = data.RoleInProject;
          }
        }
      })
      .catch(error => console.error('Error fetching member data:', error));
  } else {
    // Reset the form for adding new member
    document.getElementById('userSelect').value = '';
    const roleSelect = document.getElementById('roleSelect');
    if (!roleSelect.disabled) {
      roleSelect.value = 'thành viên';
    }
  }
  
  document.getElementById(id).classList.add('flex');
  document.getElementById(id).classList.remove('hidden');
}

function submitMemberForm(event) {
  event.preventDefault();
  const form = document.getElementById('memberForm');
  const formData = new FormData(form);
  
  // Show loading state
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = 'Đang xử lý...';
  submitBtn.disabled = true;
  
  fetch(currentFormAction, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => response.json())
  .then(data => {
    // Close the modal
    toggleModal('memberModal');
    
    // Show notification
    if (data.success) {
      showNotification('success', data.message);
    } else {
      showNotification('error', data.message);
    }
    
    // Refresh the member list
    if (isEmbedded) {
      // If we're in an iframe/embedded context, tell the parent to refresh
      if (window.parent && window.parent !== window) {
        window.parent.document.dispatchEvent(new CustomEvent('memberDataChanged'));
      } else {
        // Refresh the content without full page reload
        location.reload();
      }
    } else {
      // Standalone page, just reload
      location.reload();
    }
  })
  .catch(error => {
    console.error('Error submitting form:', error);
    showNotification('error', 'Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại.');
  })
  .finally(() => {
    // Reset button state
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
  });
}

function deleteMember(memberId) {
  if (confirm('Bạn có chắc muốn xóa thành viên này?')) {
    // Show loading state by changing the button text/style
    const buttons = document.querySelectorAll(`button[data-id="${memberId}"]`);
    const deleteBtn = Array.from(buttons).find(btn => btn.textContent.includes('Xóa'));
    
    if (deleteBtn) {
      const originalBtnText = deleteBtn.innerHTML;
      deleteBtn.innerHTML = 'Đang xóa...';
      deleteBtn.disabled = true;
    }
    
    // Make AJAX request instead of page navigation
    fetch(`DeleteMemberProcess.php?id=${memberId}&projectID=${projectID}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      // Show notification
      if (data.success) {
        showNotification('success', data.message);
      } else {
        showNotification('error', data.message);
      }
      
      // Refresh the member list
      if (isEmbedded) {
        // If we're in an iframe/embedded context, tell the parent to refresh
        if (window.parent && window.parent !== window) {
          window.parent.document.dispatchEvent(new CustomEvent('memberDataChanged'));
        } else {
          // Refresh the content without full page reload
          location.reload();
        }
      } else {
        // Standalone page, just reload
        location.reload();
      }
    })
    .catch(error => {
      console.error('Error deleting member:', error);
      showNotification('error', 'Có lỗi xảy ra khi xóa thành viên. Vui lòng thử lại.');
      
      // Reset button state if there was an error
      if (deleteBtn) {
        deleteBtn.innerHTML = originalBtnText;
        deleteBtn.disabled = false;
      }
    });
  }
}

// Helper function to show notifications
function showNotification(type, message) {
  if (typeof window.showNotification === 'function') {
    window.showNotification(type, message);
  } else if (isEmbedded && window.parent && typeof window.parent.showNotification === 'function') {
    window.parent.showNotification(type, message);
  } else {
    // Fallback alert if notification functions aren't available
    if (type === 'error') {
      alert('Lỗi: ' + message);
    } else {
      alert(message);
    }
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
</script>

<?php if (!$isEmbedded): ?>
</body>
</html>
<?php endif; ?>
