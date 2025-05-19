// Task Notification System
window.initTaskNotification = function() {
  const notification = document.getElementById('notification');
  const notificationMessage = document.getElementById('notificationMessage');
  
  // Function to show notification
  function show(message) {
    if (!notification || !notificationMessage) return;
    
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
  
  return {
    show
  };
}
