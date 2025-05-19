document.addEventListener('DOMContentLoaded', () => {
  // Truy cập dữ liệu task từ biến global
  const taskData = window.TASK_DATA || {};
  
  // Initialize modules and make them globally available
  window.taskNotification = window.initTaskNotification();
  window.taskInteractionLogger = window.initTaskActivityLogger(taskData);
  window.taskActivityLogger = window.taskInteractionLogger; // For compatibility
  
  const taskEditMode = window.initTaskEditMode(taskData);
  const taskStatusManager = window.initTaskStatusManager(taskData);
  const taskMemberAssignment = window.initTaskMemberAssignment(taskData);
  
  // Update the save button to log task updates
  const btnSave = document.getElementById('btnSave');
  if (btnSave) {
    const originalClick = btnSave.onclick;
    btnSave.onclick = function(e) {
      // Log task update event with details about what was changed
      const tagNameInput = document.getElementById('tagName');
      const tagName = tagNameInput ? tagNameInput.value : '';
      
      const additionalData = {};
      
      // If tag was changed
      if (tagName && tagName !== taskData.tagName) {
        additionalData.tagName = tagName;
        window.taskInteractionLogger.log('change_tag_name', this, additionalData);
      } else {
        window.taskInteractionLogger.log('task_updated', this);
      }
      
      // Call original click handler if it exists
      if (typeof originalClick === 'function') {
        return originalClick.call(this, e);
      }
    };
  }
  
  // Track member assignment changes
  document.addEventListener('memberAssigned', function(e) {
    const additionalData = {
      assignedTo: e.detail?.memberName || 'thành viên'
    };
    window.taskInteractionLogger.log('member_added', e.detail || {}, additionalData);
  });
  
  document.addEventListener('memberRemoved', function(e) {
    const additionalData = {
      unassignedFrom: e.detail?.memberName || 'thành viên'
    };
    window.taskInteractionLogger.log('member_removed', e.detail || {}, additionalData);
  });
  
  // Track status changes
  document.addEventListener('statusChanged', function(e) {
    const additionalData = {
      oldStatus: e.detail?.oldStatus || taskData.statusName,
      newStatus: e.detail?.newStatus || 'trạng thái mới'
    };
    window.taskInteractionLogger.log('status_changed', e.detail || {}, additionalData);
  });
  
  // Track clicks on task description
  const taskDescription = document.getElementById('taskDescription');
  if (taskDescription) {
    taskDescription.addEventListener('click', function() {
      if (!document.getElementById('btnSave')?.classList.contains('hidden')) {
        // In edit mode, don't log
        return;
      }
      window.taskInteractionLogger.log('view_description', this);
    });
  }
  
  // Track clicks on task fields
  const taskPriority = document.getElementById('taskPriority');
  if (taskPriority) {
    taskPriority.addEventListener('change', function() {
      const additionalData = {
        oldPriority: taskData.priority || 'không có',
        newPriority: this.value
      };
      window.taskInteractionLogger.log('change_priority', this, additionalData);
    });
  }
  
  // Track date field interactions
  const dateFields = document.querySelectorAll('input[type="date"]');
  dateFields.forEach(field => {
    field.addEventListener('change', function() {
      const fieldName = this.id === 'startDate' ? 'ngày bắt đầu' : 'ngày kết thúc';
      const oldValue = this.id === 'startDate' ? taskData.startDate : taskData.endDate;
      const additionalData = {
        fieldName: fieldName,
        oldValue: oldValue,
        newValue: this.value
      };
      window.taskInteractionLogger.log('change_date', this, additionalData);
    });
  });
  
  // Add click tracking for the back button
  const backButton = document.querySelector('a[href^="ProjectDetail.php"]');
  if (backButton) {
    backButton.addEventListener('click', function(e) {
      // Reset edit mode when navigating away
      taskEditMode.resetSessionEditMode();
      
      // Log before navigating
      window.taskInteractionLogger.log('back_to_project', this);
    });
  }
  
  // Log page load complete - but don't add it to the visible activity list
  window.taskInteractionLogger.log('page_loaded', document.body);
});