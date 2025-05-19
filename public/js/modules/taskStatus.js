// Task Status Management
window.initTaskStatusManager = function(taskData) {
  // Status elements
  const taskStatus = document.getElementById('taskStatus');
  const btnMarkTodo = document.getElementById('btnMarkTodo');
  const btnMarkInProgress = document.getElementById('btnMarkInProgress');
  const btnMarkCompleted = document.getElementById('btnMarkCompleted');
  
  // Track status update in progress
  let statusUpdateInProgress = false;

  // Function to update task status
  function updateTaskStatus(statusId, statusName, buttonElement) {
    if (!taskData.taskId || statusUpdateInProgress) return;
    
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
      task_id: taskData.taskId,
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
        if (taskStatus) taskStatus.textContent = statusName;
        window.taskNotification.show(`Đã cập nhật trạng thái thành "${statusName}"!`);
        window.taskInteractionLogger.log('status_updated', document.body);
        
        // Add new activity to the activity list
        window.taskActivityLogger.addNewActivity('đã thay đổi trạng thái nhiệm vụ thành ' + statusName);
      } else {
        alert('Lỗi: ' + (result.message || 'Không thể cập nhật trạng thái'));
      }
    })
    .catch(error => {
      // Removed console.error statement
      
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
  
  // Set up event listeners for the status buttons
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
  
  return {
    updateTaskStatus
  };
}
