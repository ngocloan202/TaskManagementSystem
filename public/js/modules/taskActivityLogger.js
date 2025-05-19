// Task Activity and Interaction Logging
window.initTaskActivityLogger = function(taskData) {
  // Activity type mapping for display
  const activityTypeMap = {
    'task_created': 'đã tạo nhiệm vụ',
    'task_updated': 'đã cập nhật nhiệm vụ',
    'task_assigned': 'đã giao nhiệm vụ cho',
    'task_unassigned': 'đã hủy giao nhiệm vụ cho',
    'task_status_changed': 'đã thay đổi trạng thái từ',
    'task_detail_viewed': 'đã xem nhiệm vụ',
    'task_viewed': 'đã xem nhiệm vụ',
    'task_priority_changed': 'đã thay đổi ưu tiên từ',
    'task_date_changed': 'đã thay đổi ngày nhiệm vụ',
    'task_tag_changed': 'đã thay đổi tag thành'
  };

  // Track recent activities to prevent duplicates
  const recentActivities = [];
  const MAX_RECENT_ACTIVITY_MEMORY = 10;
  const DUPLICATE_PREVENTION_TIMEOUT = 3000; // 3 seconds

  // Function to add a new activity to the activity list
  function addNewActivity(actionText, details = null) {
    const activityList = document.getElementById('activityList');
    if (!activityList) return;
    
    // Check for duplicates in recent activities
    const activityKey = `${actionText}_${new Date().getTime()}`;
    const isDuplicate = recentActivities.some(activity => {
      // If same text and added within last 3 seconds, consider it duplicate
      return activity.text === actionText && 
             (new Date().getTime() - activity.time) < DUPLICATE_PREVENTION_TIMEOUT;
    });
    
    // Skip if it's a duplicate
    if (isDuplicate) return;
    
    // Add to recent activities track
    recentActivities.push({
      text: actionText,
      time: new Date().getTime()
    });
    
    // Keep recent activities list manageable
    if (recentActivities.length > MAX_RECENT_ACTIVITY_MEMORY) {
      recentActivities.shift();
    }
    
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
    
    // Limit to 10 most recent activities
    const activityItems = activityList.querySelectorAll('.flex.items-start');
    if (activityItems.length > 10) {
      // Remove older activities (beyond the 10th one)
      for (let i = 10; i < activityItems.length; i++) {
        activityItems[i].remove();
      }
    }
  }
  
  // Track processed events to prevent duplicates
  const processedEvents = new Set();
  
  // Function to log interaction events
  function logInteraction(interactionType, element, additionalData = {}) {
    // Only log if we have task information
    if (!taskData.taskId) return;
    
    // Create a unique event ID to prevent duplicates
    const eventId = `${interactionType}_${new Date().getTime()}`;
    
    // Skip if this exact event was processed in last 3 seconds
    if (processedEvents.has(interactionType)) {
      return;
    }
    
    // Mark this interaction type as processed
    processedEvents.add(interactionType);
    
    // Clear the processed event after a timeout
    setTimeout(() => {
      processedEvents.delete(interactionType);
    }, DUPLICATE_PREVENTION_TIMEOUT);
    
    // Skip adding UI activity for page_loaded events
    const shouldUpdateUI = !['page_loaded', 'view_description'].includes(interactionType);
    
    // Prepare data for logging
    const data = {
      task_id: taskData.taskId,
      interaction_type: interactionType,
      element_id: element?.id || '',
      element_type: element?.tagName || '',
      timestamp: new Date().toISOString(),
      ...additionalData
    };
    
    // Get translated activity text if available
    if (shouldUpdateUI && typeof interactionType === 'string') {
      // Map client-side event types to activity types
      const serverActivityType = {
        'view_description': 'task_detail_viewed',
        'change_priority': 'task_priority_changed',
        'change_date': 'task_date_changed',
        'member_added': 'task_assigned',
        'member_removed': 'task_unassigned',
        'status_changed': 'task_status_changed',
        'change_tag_name': 'task_tag_changed'
      }[interactionType] || interactionType;
      
      // Base display text
      let displayText = activityTypeMap[serverActivityType] || 'đã cập nhật nhiệm vụ';
      
      // Add specific details based on the activity type
      if (serverActivityType === 'task_assigned' && additionalData.assignedTo) {
        displayText += ` ${additionalData.assignedTo}`;
      } 
      else if (serverActivityType === 'task_unassigned' && additionalData.unassignedFrom) {
        displayText += ` ${additionalData.unassignedFrom}`;
      }
      else if (serverActivityType === 'task_status_changed' && additionalData.oldStatus && additionalData.newStatus) {
        displayText += ` "${additionalData.oldStatus}" thành "${additionalData.newStatus}"`;
      }
      else if (serverActivityType === 'task_priority_changed' && additionalData.oldPriority && additionalData.newPriority) {
        displayText += ` "${additionalData.oldPriority}" thành "${additionalData.newPriority}"`;
      }
      else if (serverActivityType === 'task_tag_changed' && additionalData.tagName) {
        displayText += ` "${additionalData.tagName}"`;
      }
      
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