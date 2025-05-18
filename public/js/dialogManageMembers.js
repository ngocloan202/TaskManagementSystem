// KHÔNG dùng const projectID = ... nữa!
// Thay vào đó, dùng window.projectID, window.isOwner, window.isEmbedded

// Ví dụ:
// formData.append('projectId', window.projectID);
// const projectIdField = form.querySelector('input[name="projectId"]');
// if (projectIdField) {
//   projectIdField.value = window.projectID;
// }

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
        projectIdField.value = window.projectID;
      }
    }
  }

  // Show the modal
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

// Handle form submission
function submitMemberForm(form) {
  const formData = new FormData(form);
  fetch('/app/Views/projects/AddMemberProcess.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // Nếu không phải JSON, log ra text để debug
    return response.json().catch(() => {
      return response.text().then(text => {
        console.error('Không phải JSON:', text);
        showNotification('error', 'Lỗi server: ' + text);
        throw new Error('Phản hồi không phải JSON');
      });
    });
  })
  .then(data => {
    console.log('Response:', data);
    if (data.status === 'success') {
      toggleModal();
      showNotification('success', data.message);
      setTimeout(() => { location.reload(); }, 1500);
    } else {
      showNotification('error', data.message || 'Có lỗi xảy ra khi xử lý yêu cầu.');
    }
  })
  .catch(error => {
    showNotification('error', 'Đã xảy ra lỗi khi xử lý yêu cầu: ' + error.message);
  });
  return false;
}

window.submitMemberForm = submitMemberForm;

// Handle member deletion
function deleteMember(memberId, userName) {
  if (!memberId) {
    console.error('Invalid member ID');
    return;
  }
  
  if (!confirm(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`)) {
    return;
  }
  
  
  // Create form data
  const formData = new FormData();
  formData.append('projectId', window.projectID);
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
  if (window.isEmbedded && window.parent && window.parent !== window) {
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

// Gán các hàm vào window để gọi từ HTML
window.toggleModal = toggleModal;
window.openModal = openModal;
window.submitMemberForm = submitMemberForm;
window.deleteMember = deleteMember;
window.showNotification = showNotification;