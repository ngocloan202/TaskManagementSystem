<?php
session_start();
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

<!-- Immediate script to attach handlers -->
<script>
  // Reset body overflow (in case it was set to hidden and not reset)
  document.body.style.overflow = 'auto';
  
  // Immediately attach handlers to buttons
  (function attachHandlers() {
    console.log('Immediately attaching handlers for regular buttons');
    
    // Regular add button
    const addButtons = document.querySelectorAll('button');
    for (const btn of addButtons) {
      if (btn.textContent.trim() === 'Thêm thành viên') {
        console.log('Found add member button in immediate script');
        btn.onclick = function(e) {
          e.preventDefault();
          e.stopPropagation();
          console.log('Add member button clicked via immediate handler');
          openModal('memberModal', 0);
          return false;
        };
      }
    }
  })();
</script>

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
// Debug output
console.log('DialogManageMembers.php loaded');
console.log('Project ID:', <?= json_encode($projectID) ?>);
console.log('Is owner:', <?= json_encode($isOwner) ?>);
console.log('Is embedded:', <?= json_encode($isEmbedded) ?>);

const projectID = <?= json_encode($projectID) ?>;
const isOwner = <?= json_encode($isOwner) ?>;
const isEmbedded = <?= json_encode($isEmbedded) ?>;
let currentFormAction = '';

function toggleModal() {
  console.log("Toggling modal");
  
  const modal = document.getElementById('memberModal');
  if (!modal) {
    console.error("Modal not found!");
    return;
  }
  
  const currentDisplay = modal.style.display;
  
  if (currentDisplay === 'none' || currentDisplay === '') {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  } else {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
  }
}

function openModal(modalId, memberId = 0) {
  console.log("Opening modal for member ID:", memberId);
  
  const modal = document.getElementById('memberModal');
  if (!modal) {
    console.error("Modal not found!");
    return;
  }

  // Set title based on whether we're editing or adding
  const modalTitle = document.getElementById('memberModalLabel');
  if (modalTitle) {
    modalTitle.textContent = memberId > 0 ? "Chỉnh sửa thành viên" : "Thêm thành viên";
  }

  // Update form action and member ID if editing
  const form = document.getElementById('memberForm');
  const memberIdInput = document.getElementById('projectMemberId');
  
  if (form && memberIdInput) {
    memberIdInput.value = memberId;
    
    if (memberId > 0) {
      // Get existing data for editing
      const roleSelect = document.getElementById('roleSelect');
      if (roleSelect) {
        // Find the row with the member ID and get the current role
        const memberRow = document.querySelector(`tr[data-member-id="${memberId}"]`);
        if (memberRow) {
          const roleId = memberRow.getAttribute('data-role-id');
          if (roleId) {
            roleSelect.value = roleId;
          }
        }
      }
    } else {
      // Reset form for adding a new member
      form.reset();
      // Make sure projectId is set back
      const projectIdField = form.querySelector('input[name="projectId"]');
      if (projectIdField) {
        projectIdField.value = <?= json_encode($projectID) ?>;
      }
    }
  }

  // Show the modal
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

// Handle form submission
function submitMemberForm(form) {
  console.log("Form submission initiated");
  
  // Determine if we're adding or editing a member
  const memberId = document.getElementById('projectMemberId').value;
  const formAction = memberId > 0 ? 'EditMemberProcess.php' : 'AddMemberProcess.php';
  console.log(`Form action: ${formAction}, Member ID: ${memberId}`);
  
  // Get form data
  const formData = new FormData(form);
  
  // Set the form action field
  document.getElementById('formAction').value = memberId > 0 ? 'edit' : 'add';
  
  // Send the form data using fetch
  fetch(formAction, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .catch(error => {
    console.error('Error:', error);
    return { status: 'error', message: 'Đã xảy ra lỗi khi xử lý yêu cầu.' };
  })
  .then(data => {
    // Close the modal
    toggleModal();
    
    // Show notification
    if (data.status === 'success') {
      showNotification('success', data.message);
      // Reload the members list
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      showNotification('error', data.message || 'Có lỗi xảy ra khi xử lý yêu cầu.');
    }
  });
  
  return false; // Prevent traditional form submission
}

// Handle member deletion
function deleteMember(memberId, userName) {
  if (!memberId) {
    console.error('Invalid member ID');
    return;
  }
  
  if (!confirm(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`)) {
    return;
  }
  
  console.log(`Deleting member ID: ${memberId}`);
  
  // Create form data
  const formData = new FormData();
  formData.append('projectId', <?php echo $projectID; ?>);
  formData.append('projectMemberId', memberId);
  
  // Send request
  fetch('DeleteMemberProcess.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .catch(error => {
    console.error('Error:', error);
    return { status: 'error', message: 'Đã xảy ra lỗi khi xử lý yêu cầu.' };
  })
  .then(data => {
    // Show notification
    if (data.status === 'success') {
      showNotification('success', data.message);
      // Remove member row from table
      const memberRow = document.querySelector(`tr[data-member-id="${memberId}"]`);
      if (memberRow) {
        memberRow.remove();
      }
    } else {
      showNotification('error', data.message || 'Có lỗi xảy ra khi xóa thành viên.');
    }
  });
}

// Helper function for displaying notifications
function showNotification(type, message) {
  console.log('Showing notification:', type, message);
  
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-[10000] ${
    type === 'success' ? 'bg-green-500' : 'bg-red-500'
  } text-white max-w-md`;
  
  // Add content
  notification.innerHTML = `
    <div class="flex items-start">
      <div class="flex-shrink-0 mr-3">
        ${type === 'success' 
          ? '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
          : '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        }
      </div>
      <div>${message}</div>
      <button class="ml-auto -mr-1 text-white hover:text-gray-100" onclick="this.parentElement.parentElement.remove()">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  `;
  
  // Add to body
  document.body.appendChild(notification);
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (document.body.contains(notification)) {
      notification.remove();
    }
  }, 5000);
  
  // Try to also notify parent if embedded
  if (isEmbedded && window.parent && window.parent !== window) {
    try {
      if (typeof window.parent.showNotification === 'function') {
        window.parent.showNotification(type, message);
      }
    } catch (e) {
      console.error('Error notifying parent:', e);
    }
  }
}

// Direct script to ensure Add Member button works
document.addEventListener('DOMContentLoaded', function() {
  console.log('Setting up event listeners for manage members dialog');
  
  // Add event listener for Add Member button
  const addMemberBtn = document.getElementById('btnAddMember');
  if (addMemberBtn) {
    console.log('Add Member button found, attaching event listener');
    addMemberBtn.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('Add Member button clicked');
      openModal('memberModal', 0);
    });
  } else {
    console.error('Add Member button not found!');
  }
  
  // Add event listeners for Edit buttons
  const editButtons = document.querySelectorAll('.edit-member-btn');
  console.log('Found', editButtons.length, 'edit buttons');
  editButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const memberId = this.getAttribute('data-member-id');
      console.log('Edit button clicked for member ID:', memberId);
      openModal('memberModal', memberId);
    });
  });
});

// A direct function to click the button programmatically
function clickAddMemberButton() {
  console.log('Programmatically clicking Add Member button');
  const btn = document.getElementById('btnAddMember');
  if (btn) {
    console.log('Button found, simulating click');
    btn.click();
  } else {
    console.error('Button not found for programmatic click');
  }
}
</script>

<script>
// Add direct handlers as a last resort
document.addEventListener('DOMContentLoaded', function() {
  console.log('Final initialization check for modal functionality');
  
  // Add direct handler to the add button
  const addBtn = document.getElementById('btnAddMember');
  if (addBtn) {
    addBtn.onclick = function(e) {
      e.preventDefault();
      console.log('Direct handler: Add button clicked');
      openModal('memberModal', 0);
      return false;
    };
  }
  
  // Make sure modal is properly styled
  const modal = document.getElementById('memberModal');
  if (modal) {
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
    modal.style.zIndex = '9999';
    modal.style.display = 'none';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
  }
  
  // Test toggle
  console.log('Modal element found:', modal ? 'Yes' : 'No');
  
  // Add click handler to close button
  const closeBtn = modal?.querySelector('button[onclick="toggleModal()"]');
  if (closeBtn) {
    closeBtn.onclick = function(e) {
      e.preventDefault();
      console.log('Direct handler: Close button clicked');
      toggleModal();
      return false;
    };
  }
});
</script>

<?php if (!$isEmbedded): ?>
</body>
</html>
<?php endif; ?>
