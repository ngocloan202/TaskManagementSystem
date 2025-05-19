// Task Activity and Interaction Logging
window.initTaskActivityLogger = function(taskData) {
  // Activity type mapping for display
  const activityTypeMap = {
    'task_created': 'đã tạo nhiệm vụ',
    'task_updated': 'đã cập nhật nhiệm vụ',
    'task_assigned': 'đã giao nhiệm vụ',
    'task_unassigned': 'đã hủy giao nhiệm vụ',
    'task_status_changed': 'đã thay đổi trạng thái nhiệm vụ',
    'task_comment': 'đã bình luận',
    'task_detail_viewed': 'đã xem nhiệm vụ',
    'task_priority_changed': 'đã thay đổi ưu tiên nhiệm vụ',
    'task_date_changed': 'đã thay đổi ngày nhiệm vụ',
    'task_navigation': 'đã rời khỏi nhiệm vụ'
  };

  // Function to add a new activity to the activity list
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
  
  // Function to log interaction events
  function logInteraction(interactionType, element) {
    // Only log if we have task information
    if (!taskData.taskId) return;
    
    // Skip adding UI activity for page_loaded events
    const shouldUpdateUI = !['page_loaded', 'view_description'].includes(interactionType);
    
    // Prepare data for logging
    const data = {
      task_id: taskData.taskId,
      interaction_type: interactionType,
      element_id: element?.id || '',
      element_type: element?.tagName || '',
      timestamp: new Date().toISOString()
    };
    
    // Get translated activity text if available
    if (shouldUpdateUI && typeof interactionType === 'string') {
      // Map client-side event types to activity types
      const serverActivityType = {
        'view_description': 'task_detail_viewed',
        'change_priority': 'task_priority_changed',
        'change_date': 'task_date_changed',
        'back_to_project': 'task_navigation',
        'member_added': 'task_assigned',
        'member_removed': 'task_unassigned',
        'status_changed': 'task_status_changed'
      }[interactionType] || interactionType;
      
      // Get the display text
      const displayText = activityTypeMap[serverActivityType] || 'đã cập nhật nhiệm vụ';
      
      // Update UI
      addNewActivity(displayText);
    }
    
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
      // Removed console.log statement
    })
    .catch(error => {
      // Removed console.error statement
    });
  }
  
  return {
    addNewActivity,
    logInteraction,
    log: logInteraction // Add 'log' as an alias for 'logInteraction'
  };
} 