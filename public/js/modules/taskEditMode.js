// Task Edit Mode Management
window.initTaskEditMode = function(taskData) {
  // Elements
  const btnEdit = document.getElementById('btnEdit');
  const btnSave = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');
  const btnDelete = document.getElementById('btnDelete');
  
  // Editable form elements
  const taskPriority = document.getElementById('taskPriority');
  const startDate = document.getElementById('startDate');
  const endDate = document.getElementById('endDate');
  const taskDescription = document.getElementById('taskDescription');
  
  // Track edit mode and original values
  let editMode = false;
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
    fetch('../../../api/task/SetEditMode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ edit_mode: false })
    }).catch(err => {
      // Error handling without console.error
    });
  }
  
  // Reset session edit mode on init
  resetSessionEditMode();
  
  // Function to toggle edit mode
  function toggleEditMode(enabled) {
    editMode = enabled;
    
    // Set session edit mode via fetch call
    fetch('../../../api/task/SetEditMode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ edit_mode: enabled })
    }).catch(err => {
      // Error handling without console.error
    });
    
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
      
      // Tag edit mode
      updateTagContainerForEditMode(true);
    } else {
      if (taskPriority) taskPriority.classList.remove('edit-mode');
      if (startDate) startDate.classList.remove('edit-mode');
      if (endDate) endDate.classList.remove('edit-mode');
      if (taskDescription) taskDescription.classList.remove('edit-mode');
      
      // Tag normal mode
      updateTagContainerForEditMode(false);
    }
  }
  
  // Function to save task changes
  function saveTaskChanges() {
    if (!taskData.taskId) return;
    
    // Disable buttons and show loading
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="animate-pulse">Đang lưu...</span>';
    btnCancel.disabled = true;
    
    // Get values from inputs
    const tagNameInput = document.getElementById('tagName');
    const tagColorInput = document.getElementById('tagColor');
    
    // Fallback to originalValues if elements can't be found
    const tagName = tagNameInput ? tagNameInput.value : originalValues.tagName;
    const tagColor = tagColorInput ? tagColorInput.value : originalValues.tagColor;
    
    const formData = {
      task_id: taskData.taskId,
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
        // Reset edit mode
        toggleEditMode(false);
        resetSessionEditMode();
        
        // Update taskData with new values
        taskData.tagName = formData.tag_name;
        taskData.tagColor = formData.tag_color;
        taskData.priority = formData.priority;
        taskData.startDate = formData.start_date;
        taskData.endDate = formData.end_date;
        taskData.description = formData.description;
        
        window.taskNotification.show('Đã cập nhật nhiệm vụ thành công!');
        window.taskInteractionLogger.log('task_updated', document.body);
        
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
      
      // Removed console.error statement
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
        window.taskInteractionLogger.log('change_tag_name', this);
      });
      
      tagColorInput.addEventListener('input', function() {
        const newColor = this.value;
        // Removed console.log statement
        tagPreview.style.backgroundColor = newColor;
        // Immediately update the value in original values to ensure it persists
        originalValues.tagColor = newColor;
        
        // Update taskData to ensure the color is available when toggling edit mode
        taskData.tagColor = newColor;
        
        window.taskInteractionLogger.log('change_tag_color', this);
      });
    } else {
      // Hiển thị tag thông thường
      tagContainer.innerHTML = '';
      tagContainer.className = 'flex items-center'; // Reset class
      
      if (currentTagName) {
        // Nếu có tag
        const tagSpan = document.createElement('span');
        tagSpan.className = 'px-3 py-1 rounded-full text-sm text-white font-semibold';
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
  
  // Set up event listeners
  if (btnEdit) {
    btnEdit.addEventListener('click', function() {
      // Save original values before entering edit mode
      originalValues = {
        priority: taskPriority?.value || '',
        startDate: startDate?.value || '',
        endDate: endDate?.value || '',
        description: taskDescription?.innerHTML || '',
        tagName: taskData.tagName || '',
        tagColor: taskData.tagColor || '#3B82F6'
      };
      
      toggleEditMode(true);
      window.taskInteractionLogger.log('edit_mode_enabled', this);
    });
  }
  
  if (btnCancel) {
    btnCancel.addEventListener('click', function() {
      // Restore original values
      if (taskPriority) taskPriority.value = originalValues.priority;
      if (startDate) startDate.value = originalValues.startDate;
      if (endDate) endDate.value = originalValues.endDate;
      if (taskDescription) taskDescription.innerHTML = originalValues.description;
      
      const tagNameInput = document.getElementById('tagName');
      const tagColorInput = document.getElementById('tagColor');
      const tagPreview = document.getElementById('tagPreview');
      
      if (tagNameInput) tagNameInput.value = originalValues.tagName;
      if (tagColorInput) {
        tagColorInput.value = originalValues.tagColor;
        if (tagPreview) tagPreview.style.backgroundColor = originalValues.tagColor;
      }
      if (tagPreview) tagPreview.textContent = originalValues.tagName || 'Tag mới';
      
      toggleEditMode(false);
      window.taskInteractionLogger.log('edit_cancelled', this);
    });
  }
  
  if (btnSave) {
    btnSave.addEventListener('click', function() {
      saveTaskChanges();
    });
  }
  
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
  
  // Also handle browser's back button
  window.addEventListener('beforeunload', function() {
    // Reset edit mode when leaving the page
    resetSessionEditMode();
  });
  
  return {
    resetSessionEditMode,
    toggleEditMode,
    saveTaskChanges
  };
}
