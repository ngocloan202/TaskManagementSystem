// Notification functions
function showNotification(type, message) {
  if (!message) return;
  
  const notificationId = type === 'success' ? 'successNotification' : 'errorNotification';
  const messageId = type === 'success' ? 'successMessage' : 'errorMessage';
  
  document.getElementById(messageId).textContent = message;
  document.getElementById(notificationId).classList.add('show');
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideNotification(notificationId);
  }, 5000);
}

function hideNotification(notificationId) {
  document.getElementById(notificationId).classList.remove('show');
}

// Check for session messages on page load
document.addEventListener('DOMContentLoaded', function() {
  // These variables will be populated by PHP in the notification component
  if (typeof successMessage !== 'undefined' && successMessage) {
    showNotification('success', successMessage);
  }
  
  if (typeof errorMessage !== 'undefined' && errorMessage) {
    showNotification('error', errorMessage);
  }
}); 