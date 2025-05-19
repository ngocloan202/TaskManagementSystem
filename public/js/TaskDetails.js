document.addEventListener('DOMContentLoaded', () => {
  // Truy cập dữ liệu task từ biến global
  const taskData = window.TASK_DATA || {};
  const taskId = taskData.taskId;
  const projectId = taskData.projectId;
  const isAdmin = taskData.isAdmin;
  
  // Edit mode elements
  const btnEdit = document.getElementById('btnEdit');
  const btnSave = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');
  const btnDelete = document.getElementById('btnDelete');
  
  // Editable form elements
  const taskPriority = document.getElementById('taskPriority');
  const startDate = document.getElementById('startDate');
  const endDate = document.getElementById('endDate');
  const taskDescription = document.getElementById('taskDescription');
  const tagName = document.getElementById('tagName');
  const tagColor = document.getElementById('tagColor');
  const tagPreview = document.getElementById('tagPreview');
  
  // Status elements
  const taskStatus = document.getElementById('taskStatus');
  const btnMarkTodo = document.getElementById('btnMarkTodo');
  const btnMarkInProgress = document.getElementById('btnMarkInProgress');
  const btnMarkCompleted = document.getElementById('btnMarkCompleted');
  
  // Track if we're in edit mode and status update in progress
  let editMode = false;
  let statusUpdateInProgress = false;
  
  // Save original values for cancel
  let originalValues = {
    priority: taskData.priority || '',
    startDate: taskData.startDate || '',
    endDate: taskData.endDate || '',
    description: taskData.description || '',
    tagName: taskData.tagName || '',
    tagColor: taskData.tagColor || '#3B82F6'
  };
  
  // Function to reset edit mode in the session
  function resetSessionEditMode() {
    // Clear edit mode in the session
    fetch('../../../api/task/SetEditMode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ edit_mode: false })
    }).catch(err => console.error('Failed to reset edit mode:', err));
  }
  
  // Reset session edit mode on page load
  resetSessionEditMode();
  
  // Function to toggle edit mode
  function toggleEditMode(enabled) {
    editMode = enabled;
    
    // Set session edit mode via fetch call
    fetch('../../../api/task/SetEditMode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ edit_mode: enabled })
    }).catch(err => console.error('Failed to set edit mode:', err));
    
    // Toggle button visibility
    btnEdit?.classList.toggle('hidden', enabled);
    btnSave?.classList.toggle('hidden', !enabled);
    btnCancel?.classList.toggle('hidden', !enabled);
    btnDelete?.classList.toggle('hidden', enabled);
    
    // Toggle field editability
    if (taskPriority) taskPriority.disabled = !enabled;
    if (startDate) startDate.readOnly = !enabled;
    if (endDate) endDate.readOnly = !enabled;
    if (taskDescription) taskDescription.contentEditable = enabled.toString();
    
    // Toggle visual edit mode indicators
    if (enabled) {
      if (taskPriority) taskPriority.classList.add('edit-mode');
      if (startDate) startDate.classList.add('edit-mode');
      if (endDate) endDate.classList.add('edit-mode');
      if (taskDescription) taskDescription.classList.add('edit-mode');
      
      // Hiển thị form chỉnh sửa tag
      updateTagContainerForEditMode(true);
    } else {
      if (taskPriority) taskPriority.classList.remove('edit-mode');
      if (startDate) startDate.classList.remove('edit-mode');
      if (endDate) endDate.classList.remove('edit-mode');
      if (taskDescription) taskDescription.classList.remove('edit-mode');
      
      // Hiển thị lại tag bình thường
      updateTagContainerForEditMode(false);
    }
  }
  
  // Function to save task changes
  function saveTaskChanges() {
    if (!taskId) return;
    
    // Disable buttons and show loading
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="animate-pulse">Đang lưu...</span>';
    btnCancel.disabled = true;
    
    // Lấy giá trị từ các input - đảm bảo lấy cả giá trị từ tag
    const tagNameInput = document.getElementById('tagName');
    const tagColorInput = document.getElementById('tagColor');
    
    // Fallback to originalValues if elements can't be found
    // This ensures we always have correct tag values
    const tagName = tagNameInput ? tagNameInput.value : originalValues.tagName;
    const tagColor = tagColorInput ? tagColorInput.value : originalValues.tagColor;
    
    const formData = {
      task_id: taskId,
      priority: taskPriority?.value || '',
      start_date: startDate?.value || '',
      end_date: endDate?.value || '',
      description: taskDescription?.innerText.trim() || '',
      tag_name: tagName || '',
      tag_color: tagColor || '#3B82F6'
    };
    
    // Send data to server for updating
    fetch('../../../api/task/UpdateTask.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Lỗi khi cập nhật nhiệm vụ');
      }
      return response.json();
    })
    .then(result => {
      // Re-enable buttons and restore text
      btnSave.disabled = false;
      btnSave.innerHTML = 'Lưu';
      btnCancel.disabled = false;
      
      if (result.success) {
        // Always reset edit mode both in UI and session
        toggleEditMode(false);
        resetSessionEditMode();
        
        // Cập nhật taskData local với giá trị mới
        taskData.tagName = formData.tag_name;
        taskData.tagColor = formData.tag_color;
        taskData.priority = formData.priority;
        taskData.startDate = formData.start_date;
        taskData.endDate = formData.end_date;
        taskData.description = formData.description;
        
        showNotification('Đã cập nhật nhiệm vụ thành công!');
        logInteraction('task_updated', document.body);
        
        // Update original values after successful save
        originalValues = {
          priority: taskPriority?.value || '',
          startDate: startDate?.value || '',
          endDate: endDate?.value || '',
          description: taskDescription?.innerHTML || '',
          tagName: formData.tag_name,
          tagColor: formData.tag_color
        };
        
        // Force update the tag display with the saved values
        updateTagContainerForEditMode(false);
      } else {
        alert('Lỗi: ' + (result.message || 'Không thể cập nhật nhiệm vụ'));
      }
    })
    .catch(error => {
      // Re-enable buttons and restore text
      btnSave.disabled = false;
      btnSave.innerHTML = 'Lưu';
      btnCancel.disabled = false;
      
      console.error('Error updating task:', error);
      alert('Lỗi: ' + error.message);
    });
  }
  
  // Cập nhật hiển thị tag container dựa vào chế độ chỉnh sửa
  function updateTagContainerForEditMode(isEditMode) {
    const tagContainer = document.getElementById('tagContainer');
    if (!tagContainer) return;
    
    // Always ensure we're using the most current color values from taskData
    const currentTagName = taskData.tagName || '';
    const currentTagColor = taskData.tagColor || '#3B82F6';
    
    if (isEditMode) {
      tagContainer.innerHTML = '';
      tagContainer.className = 'flex items-center space-x-2'; // Thêm space-x để canh đều
      
      // Container cho tag input và preview để nằm trên cùng một dòng
      const tagInputGroup = document.createElement('div');
      tagInputGroup.className = 'flex items-center space-x-2';
      
      // Tạo input tên tag - với style thanh lịch hơn
      const tagNameInput = document.createElement('input');
      tagNameInput.id = 'tagName';
      tagNameInput.type = 'text';
      tagNameInput.value = currentTagName;
      tagNameInput.placeholder = 'Tên tag';
      tagNameInput.className = 'border-b border-gray-300 focus:border-indigo-500 focus:outline-none px-1 py-1 text-sm';
      tagNameInput.style.maxWidth = '100px';
      tagNameInput.style.background = 'transparent';
      
      // Tạo input màu tag - nhỏ gọn hơn
      const tagColorInput = document.createElement('input');
      tagColorInput.id = 'tagColor';
      tagColorInput.type = 'color';
      tagColorInput.value = currentTagColor;
      tagColorInput.className = 'h-6 w-6 border-0 cursor-pointer';
      tagColorInput.style.padding = '0';
      
      // Tạo tag preview - ngay cạnh input
      const tagPreview = document.createElement('div');
      tagPreview.id = 'tagPreview';
      tagPreview.className = 'px-2 py-1 rounded-full text-xs text-white';
      tagPreview.style.backgroundColor = currentTagColor;
      tagPreview.textContent = currentTagName || 'Tag mới';
      
      // Thêm các phần tử vào container
      tagInputGroup.appendChild(tagNameInput);
      tagInputGroup.appendChild(tagColorInput);
      tagContainer.appendChild(tagInputGroup);
      tagContainer.appendChild(tagPreview);
      
      // Thêm event listeners
      tagNameInput.addEventListener('input', function() {
        tagPreview.textContent = this.value || 'Tag mới';
        logInteraction('change_tag_name', this);
      });
      
      tagColorInput.addEventListener('input', function() {
        const newColor = this.value;
        console.log('Tag color changed to:', newColor);
        tagPreview.style.backgroundColor = newColor;
        // Immediately update the value in original values to ensure it persists
        originalValues.tagColor = newColor;
        
        // Update taskData to ensure the color is available when toggling edit mode
        taskData.tagColor = newColor;
        
        logInteraction('change_tag_color', this);
      });
    } else {
      // Hiển thị tag thông thường
      tagContainer.innerHTML = '';
      tagContainer.className = 'flex items-center'; // Reset class
      
      if (currentTagName) {
        // Nếu có tag
        const tagSpan = document.createElement('span');
        tagSpan.className = 'px-3 py-1 rounded-full text-sm text-white';
        tagSpan.style.backgroundColor = currentTagColor;
        tagSpan.textContent = currentTagName;
        tagContainer.appendChild(tagSpan);
      } else {
        // Nếu không có tag
        const noTagSpan = document.createElement('span');
        noTagSpan.className = 'text-gray-500';
        noTagSpan.textContent = 'Chưa có tag';
        tagContainer.appendChild(noTagSpan);
      }
    }
  }
  
  // Event listeners for edit controls
  if (btnEdit) {
    btnEdit.addEventListener('click', function() {
      // Save original values before entering edit mode
      originalValues = {
        priority: taskPriority?.value || '',
        startDate: startDate?.value || '',
        endDate: endDate?.value || '',
        description: taskDescription?.innerHTML || '',
        tagName: tagName?.value || '',
        tagColor: tagColor?.value || '#3B82F6'
      };
      
      toggleEditMode(true);
      logInteraction('edit_mode_enabled', this);
    });
  }
  
  if (btnCancel) {
    btnCancel.addEventListener('click', function() {
      // Restore original values
      if (taskPriority) taskPriority.value = originalValues.priority;
      if (startDate) startDate.value = originalValues.startDate;
      if (endDate) endDate.value = originalValues.endDate;
      if (taskDescription) taskDescription.innerHTML = originalValues.description;
      if (tagName) tagName.value = originalValues.tagName;
      if (tagColor) {
        tagColor.value = originalValues.tagColor;
        tagPreview.style.backgroundColor = originalValues.tagColor;
      }
      if (tagPreview) tagPreview.textContent = originalValues.tagName || 'Tag mới';
      
      toggleEditMode(false);
      logInteraction('edit_cancelled', this);
    });
  }
  
  if (btnSave) {
    btnSave.addEventListener('click', function() {
      saveTaskChanges();
    });
  }
  
  // Function to update task status
  function updateTaskStatus(statusId, statusName, buttonElement) {
    if (!taskId || statusUpdateInProgress) return;
    
    // Set flag to prevent multiple status updates
    statusUpdateInProgress = true;
    
    // Show loading indicator on the clicked button
    const originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = '<span class="animate-pulse">Đang cập nhật...</span>';
    buttonElement.disabled = true;
    
    // Disable all status buttons while updating
    if (btnMarkTodo) btnMarkTodo.disabled = true;
    if (btnMarkInProgress) btnMarkInProgress.disabled = true;
    if (btnMarkCompleted) btnMarkCompleted.disabled = true;
    
    const formData = {
      task_id: taskId,
      status_id: statusId
    };
    
    // Send data to server for updating status
    fetch('../../../api/task/UpdateTaskStatus.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    })
    .then(response => {
      return response.json();
    })
    .then(result => {
      // Reset status update flag
      statusUpdateInProgress = false;
      
      // Restore all buttons
      if (btnMarkTodo) {
        btnMarkTodo.innerHTML = 'Cần làm';
        btnMarkTodo.disabled = false;
      }
      if (btnMarkInProgress) {
        btnMarkInProgress.innerHTML = 'Đang làm';
        btnMarkInProgress.disabled = false;
      }
      if (btnMarkCompleted) {
        btnMarkCompleted.innerHTML = 'Hoàn thành';
        btnMarkCompleted.disabled = false;
      }
      
      if (result.success) {
        // Update the UI
        taskStatus.textContent = statusName;
        showNotification(`Đã cập nhật trạng thái thành "${statusName}"!`);
        logInteraction('status_updated', document.body);
        
        // Add new activity to the activity list
        addNewActivity('đã thay đổi trạng thái nhiệm vụ thành ' + statusName);
      } else {
        alert('Lỗi: ' + (result.message || 'Không thể cập nhật trạng thái'));
      }
    })
    .catch(error => {
      console.error('Error updating status:', error);
      
      // Reset status update flag
      statusUpdateInProgress = false;
      
      // Restore all buttons
      if (btnMarkTodo) {
        btnMarkTodo.innerHTML = 'Cần làm';
        btnMarkTodo.disabled = false;
      }
      if (btnMarkInProgress) {
        btnMarkInProgress.innerHTML = 'Đang làm';
        btnMarkInProgress.disabled = false;
      }
      if (btnMarkCompleted) {
        btnMarkCompleted.innerHTML = 'Hoàn thành';
        btnMarkCompleted.disabled = false;
      }
      
      alert('Lỗi: ' + error.message);
    });
  }
  
  // Add event listeners to the status buttons
  if (btnMarkTodo) {
    btnMarkTodo.addEventListener('click', function() {
      updateTaskStatus(1, 'Cần làm', this);
    });
  }
  
  if (btnMarkInProgress) {
    btnMarkInProgress.addEventListener('click', function() {
      updateTaskStatus(2, 'Đang làm', this);
    });
  }
  
  if (btnMarkCompleted) {
    btnMarkCompleted.addEventListener('click', function() {
      updateTaskStatus(3, 'Hoàn thành', this);
    });
  }
  
  // Add a new activity to the activity list
  function addNewActivity(actionText) {
    const activityList = document.getElementById('activityList');
    if (!activityList) return;
    
    // Get current user's info
    const userName = taskData.currentUser.name || 'Người dùng';
    const userAvatar = taskData.currentUser.avatar || 'public/uploads/default-avatar.png';
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                      now.getMinutes().toString().padStart(2, '0') + ' - ' + 
                      now.getDate().toString().padStart(2, '0') + '/' + 
                      (now.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                      now.getFullYear();
    
    // Create new activity element
    const newActivity = document.createElement('div');
    newActivity.className = 'flex items-start';
    newActivity.innerHTML = `
      <img src="../../../${userAvatar}" alt="User" class="w-8 h-8 rounded-full mr-3">
      <div>
        <p class="font-medium">${userName}</p>
        <p class="text-gray-700">${actionText}</p>
        <p class="text-gray-500 text-sm">${timeString}</p>
      </div>
    `;
    
    // Remove "no activities" message if present
    const noActivitiesMsg = activityList.querySelector('p.text-gray-500.text-sm');
    if (noActivitiesMsg && noActivitiesMsg.textContent.includes('Hiện không có hoạt động nào')) {
      noActivitiesMsg.parentElement.parentElement.remove();
    }
    
    // Add to the beginning of the list
    if (activityList.firstChild) {
      activityList.insertBefore(newActivity, activityList.firstChild);
    } else {
      activityList.appendChild(newActivity);
    }
  }
  
  // Function to show notification
  function showNotification(message) {
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notificationMessage');
    
    notificationMessage.textContent = message;
    notification.classList.remove('hidden');
    
    // Animate in
    setTimeout(() => {
      notification.classList.remove('translate-y-20', 'opacity-0');
    }, 10);
    
    // Animate out after 3 seconds
    setTimeout(() => {
      notification.classList.add('translate-y-20', 'opacity-0');
      setTimeout(() => {
        notification.classList.add('hidden');
      }, 300);
    }, 3000);
  }
  
  // Function to log interaction events
  function logInteraction(interactionType, element) {
    // Only log if we have task information
    if (!taskId) return;
    
    // Prepare data for logging
    const data = {
      task_id: taskId,
      interaction_type: interactionType,
      element_id: element?.id || '',
      element_type: element?.tagName || '',
      timestamp: new Date().toISOString()
    };
    
    // Send data to server for logging
    fetch('../../../api/task/LogTaskInteraction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(result => {
      console.log('Interaction logged:', result);
    })
    .catch(error => {
      console.error('Error logging interaction:', error);
    });
  }
  
  // Track clicks on task description
  if (taskDescription) {
    taskDescription.addEventListener('click', function() {
      if (!editMode) {
        logInteraction('view_description', this);
      }
    });
  }
  
  // Track clicks on task fields
  if (taskPriority) {
    taskPriority.addEventListener('change', function() {
      logInteraction('change_priority', this);
    });
  }
  
  // Track date field interactions
  const dateFields = document.querySelectorAll('input[type="date"]');
  dateFields.forEach(field => {
    field.addEventListener('change', function() {
      logInteraction('change_date', this);
    });
  });
  
  // Add click tracking for the back button
  const backButton = document.querySelector('a[href^="ProjectDetail.php"]');
  if (backButton) {
    backButton.addEventListener('click', function(e) {
      // Reset edit mode when navigating away
      resetSessionEditMode();
      
      // Log before navigating
      logInteraction('back_to_project', this);
    });
  }
  
  // Also handle browser's back button
  window.addEventListener('beforeunload', function() {
    // Reset edit mode when leaving the page
    resetSessionEditMode();
  });
  
  // Log page load complete
  logInteraction('page_loaded', document.body);

  // Add beforeunload event to confirm leaving if there are unsaved changes
  window.addEventListener('beforeunload', function(e) {
    // Only prompt if in edit mode
    if (editMode) {
      // Check if there are unsaved changes
      if (
        taskPriority?.value !== originalValues.priority ||
        startDate?.value !== originalValues.startDate ||
        endDate?.value !== originalValues.endDate ||
        taskDescription?.innerHTML !== originalValues.description ||
        document.getElementById('tagName')?.value !== originalValues.tagName ||
        document.getElementById('tagColor')?.value !== originalValues.tagColor
      ) {
        // Standard message for unsaved changes (browser will show its own message)
        const confirmationMessage = 'Bạn có thay đổi chưa được lưu. Bạn có chắc chắn muốn rời khỏi trang này?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
      }
    }
  });
  
  // ----- Member assignment functionality -----
  const memberDropdown = document.getElementById('memberDropdown');
  const memberDisplay = document.getElementById('memberDisplay');
  const addMemberBtn = document.getElementById('addMemberBtn');
  const addMoreMembersBtn = document.getElementById('addMoreMembersBtn');
  const memberList = document.getElementById('memberList');
  const memberSearchInput = document.getElementById('memberSearchInput');
  
  // Project members data
  let projectMembers = [];
  let assignedMembers = [];
  
  // Function to toggle member dropdown
  function toggleMemberDropdown() {
    if (!memberDropdown) return;
    
    memberDropdown.classList.toggle('show');
    if (memberDropdown.classList.contains('show')) {
      loadProjectMembers();
      if (memberSearchInput) {
        setTimeout(() => memberSearchInput.focus(), 100);
      }
    }
  }
  
  // Close dropdown when clicking outside
  window.addEventListener('click', function(event) {
    if (memberDropdown && !event.target.closest('.member-dropdown')) {
      memberDropdown.classList.remove('show');
    }
  });
  
  // Load assigned members when page loads
  function loadTaskAssignments() {
    if (!taskId) return;
    
    fetch(`../../../api/task/GetTaskAssignments.php?task_id=${taskId}`)
      .then(response => {
        if (!response.ok) throw new Error('Lỗi khi tải thông tin thành viên được giao');
        return response.json();
      })
      .then(data => {
        if (data.success && data.assignments) {
          // Initialize assigned members from API data
          assignedMembers = data.assignments.map(a => parseInt(a.UserID));
          
          // Update UI with assigned members
          if (projectMembers.length > 0) {
            updateMemberDisplay();
          }
        } else {
          console.warn('No assignment data or unsuccessful response:', data);
        }
      })
      .catch(error => {
        console.error('Error loading task assignments:', error);
      });
  }
  
  // Toggle dropdown when clicking on the member display or add buttons
  if (memberDisplay) {
    // Don't open dropdown when clicking on any existing member display
    memberDisplay.addEventListener('click', function(event) {
      if (event.target.closest('.member-badge')) {
        event.stopPropagation();
        return;
      }
      
      // Handle remove-member click explicitly
      if (event.target.closest('.remove-member')) {
        const memberId = parseInt(event.target.closest('.member-badge').dataset.memberId);
        toggleMemberAssignment(memberId);
        event.stopPropagation();
        return;
      }
      
      // Only open when clicking on the add button or empty space
      if (event.target.closest('#addMemberBtn') || event.target.closest('#addMoreMembersBtn')) {
        toggleMemberDropdown();
        event.stopPropagation();
      }
    });
  }
  
  if (addMemberBtn) {
    addMemberBtn.addEventListener('click', function(event) {
      toggleMemberDropdown();
      event.stopPropagation();
    });
  }
  
  if (addMoreMembersBtn) {
    addMoreMembersBtn.addEventListener('click', function(event) {
      toggleMemberDropdown();
      event.stopPropagation();
    });
  }
  
  // Search functionality
  if (memberSearchInput) {
    memberSearchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      renderMemberList(searchTerm);
    });
  }
  
  // Load project members from API
  function loadProjectMembers() {
    if (!memberList) return;
    
    // Set loading state
    memberList.innerHTML = '<div class="no-members-message">Đang tải danh sách thành viên...</div>';
    
    fetch(`../../../api/project/GetProjectMembers.php?project_id=${projectId}`)
      .then(response => {
        if (!response.ok) throw new Error('Lỗi khi tải thành viên');
        return response.json();
      })
      .then(data => {
        if (data.success) {
          projectMembers = data.members || [];
          
          // If we don't have assigned members yet and there are member badges in the DOM, get them
          if (assignedMembers.length === 0) {
            const memberBadges = document.querySelectorAll('.member-badge');
            if (memberBadges.length > 0) {
              assignedMembers = Array.from(memberBadges).map(badge => parseInt(badge.dataset.memberId));
            }
            
            // If still no assigned members, try to load from API
            if (assignedMembers.length === 0) {
              loadTaskAssignments();
            }
          }
          
          renderMemberList();
        } else {
          memberList.innerHTML = `<div class="no-members-message">Lỗi: ${data.message || 'Không thể tải thành viên'}</div>`;
        }
      })
      .catch(error => {
        console.error('Error loading members:', error);
        memberList.innerHTML = '<div class="no-members-message">Lỗi khi tải thành viên. Vui lòng thử lại.</div>';
      });
  }
  
  // Render member list with optional search filter
  function renderMemberList(searchTerm = '') {
    if (!memberList) return;
    
    if (projectMembers.length === 0) {
      memberList.innerHTML = '<div class="no-members-message">Không có thành viên trong dự án</div>';
      return;
    }
    
    // Filter members by search term if provided
    const filteredMembers = searchTerm 
      ? projectMembers.filter(member => 
          member.FullName.toLowerCase().includes(searchTerm) ||
          (member.Email && member.Email.toLowerCase().includes(searchTerm))
        )
      : projectMembers;
    
    if (filteredMembers.length === 0) {
      memberList.innerHTML = '<div class="no-members-message">Không tìm thấy thành viên nào</div>';
      return;
    }
    
    // Generate member items HTML
    const membersHtml = filteredMembers.map(member => {
      const isSelected = assignedMembers.includes(parseInt(member.UserID));
      return `
        <div class="member-item ${isSelected ? 'selected' : ''}" data-member-id="${member.UserID}">
          <img src="../../../${member.Avatar}" alt="${member.FullName}">
          <span class="member-name">${member.FullName}</span>
          <svg class="member-check" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
          </svg>
        </div>
      `;
    }).join('');
    
    memberList.innerHTML = membersHtml;
    
    // Add click event listeners to member items
    document.querySelectorAll('.member-item').forEach(item => {
      item.addEventListener('click', function() {
        const memberId = parseInt(this.dataset.memberId);
        toggleMemberAssignment(memberId);
      });
    });
  }
  
  // Assign/unassign a member to the task
  function toggleMemberAssignment(memberId) {
    // Check if member is already assigned
    const isAssigned = assignedMembers.includes(memberId);
    
    if (isAssigned) {
      // Unassign member
      assignedMembers = assignedMembers.filter(id => id !== memberId);
      updateMemberAssignment(memberId, false);
    } else {
      // Assign member (without removing others)
      assignedMembers.push(memberId);
      updateMemberAssignment(memberId, true);
    }
    
    // Update the UI
    renderMemberList();
    updateMemberDisplay();
  }
  
  // Update member display in the UI
  function updateMemberDisplay() {
    if (!memberDisplay) return;
    
    if (assignedMembers.length === 0) {
      memberDisplay.innerHTML = `
        <button id="addMemberBtn" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Thêm thành viên
        </button>
      `;
      
      // Re-attach click event to the new button
      const newAddMemberBtn = document.getElementById('addMemberBtn');
      if (newAddMemberBtn) {
        newAddMemberBtn.addEventListener('click', function(event) {
          toggleMemberDropdown();
          event.stopPropagation();
        });
      }
    } else {
      // Generate HTML for all assigned members
      let assigneesHtml = '';
      
      for (const memberId of assignedMembers) {
        const memberData = projectMembers.find(m => parseInt(m.UserID) === memberId);
        if (memberData) {
          assigneesHtml += `
            <div class="member-badge" data-member-id="${memberData.UserID}">
              <img src="../../../${memberData.Avatar}" alt="${memberData.FullName}">
              <span>${memberData.FullName}</span>
              <span class="remove-member">×</span>
            </div>
          `;
        }
      }
      
      // Add the "Add more" button
      assigneesHtml += `
        <button id="addMoreMembersBtn" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium ml-2">
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Thêm
        </button>
      `;
      
      memberDisplay.innerHTML = assigneesHtml;
      
      // Re-attach click events for remove buttons
      document.querySelectorAll('.remove-member').forEach(btn => {
        btn.addEventListener('click', function(event) {
          const memberId = parseInt(this.closest('.member-badge').dataset.memberId);
          toggleMemberAssignment(memberId);
          event.stopPropagation();
        });
      });
      
      // Re-attach click event to the add more button
      const newAddMoreBtn = document.getElementById('addMoreMembersBtn');
      if (newAddMoreBtn) {
        newAddMoreBtn.addEventListener('click', function(event) {
          toggleMemberDropdown();
          event.stopPropagation();
        });
      }
    }
  }
  
  // Send member assignment update to the server
  function updateMemberAssignment(memberId, isAssigning) {
    if (!taskId) return;
    
    // Disable interactions during API call
    document.querySelectorAll('.member-item, .remove-member').forEach(el => {
      el.style.pointerEvents = 'none';
    });
    
    // Show a loading indicator in the member display
    if (memberDisplay) memberDisplay.classList.add('opacity-50');
    
    const action = isAssigning ? 'assign' : 'unassign';
    
    fetch('../../../api/task/UpdateTaskAssignment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        task_id: taskId,
        user_id: memberId,
        action: action
      })
    })
    .then(response => {
      if (!response.ok) throw new Error('Lỗi khi cập nhật phân công');
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Update activity list with the new assignment
        const actionText = isAssigning 
          ? `đã giao nhiệm vụ cho ${projectMembers.find(m => parseInt(m.UserID) === memberId)?.FullName || 'thành viên'}`
          : 'đã bỏ giao nhiệm vụ';
        addNewActivity(actionText);
        
        // Show success notification
        showNotification(isAssigning ? 'Đã giao nhiệm vụ thành công' : 'Đã bỏ giao nhiệm vụ thành công');
        
        // Log the interaction
        logInteraction(isAssigning ? 'assign_member' : 'unassign_member', memberDisplay);
      } else {
        // Revert the UI changes
        if (isAssigning) {
          assignedMembers = assignedMembers.filter(id => id !== memberId);
        } else {
          assignedMembers.push(memberId);
        }
        renderMemberList();
        updateMemberDisplay();
        
        // Show error
        alert('Lỗi: ' + (data.message || 'Không thể cập nhật phân công'));
      }
    })
    .catch(error => {
      console.error('Error updating assignment:', error);
      
      // Revert the UI changes
      if (isAssigning) {
        assignedMembers = assignedMembers.filter(id => id !== memberId);
      } else {
        assignedMembers.push(memberId);
      }
      renderMemberList();
      updateMemberDisplay();
      
      alert('Lỗi: ' + error.message);
    })
    .finally(() => {
      // Re-enable interactions
      document.querySelectorAll('.member-item, .remove-member').forEach(el => {
        el.style.pointerEvents = '';
      });
      if (memberDisplay) memberDisplay.classList.remove('opacity-50');
    });
  }
  
  // Load task assignments when page loads
  loadTaskAssignments();
});