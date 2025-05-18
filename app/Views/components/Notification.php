<?php
/**
 * Helper function to set notification messages
 * 
 * @param string $type Type of notification ('success' or 'error')
 * @param string $message The message to display
 * @param bool $redirect Whether to redirect after setting the message
 * @param string $redirectUrl URL to redirect to (optional)
 * @return void
 */
function setNotification($type, $message, $redirect = false, $redirectUrl = '') {
    if ($type === 'success') {
        $_SESSION['success'] = $message;
    } else if ($type === 'error') {
        $_SESSION['error'] = $message;
    }
    
    if ($redirect && !empty($redirectUrl)) {
        header("Location: " . $redirectUrl);
        exit();
    }
}

// Extract any success or error messages from session
$successMessage = '';
$errorMessage = '';

if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!-- Link to external CSS and JavaScript files -->
<link rel="stylesheet" href="../../../public/css/notification.css">

<!-- Notification boxes -->
<div id="successNotification" class="notification-box notification-success">
  <div class="notification-content">
    <div class="notification-icon">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <div class="notification-message" id="successMessage"></div>
  </div>
  <div class="notification-close" onclick="hideNotification('successNotification')">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
  </div>
</div>

<div id="errorNotification" class="notification-box notification-error">
  <div class="notification-content">
    <div class="notification-icon">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <div class="notification-message" id="errorMessage"></div>
  </div>
  <div class="notification-close" onclick="hideNotification('errorNotification')">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
  </div>
</div>

<!-- Pass PHP variables to JavaScript -->
<script>
  var successMessage = <?= json_encode($successMessage) ?>;
  var errorMessage = <?= json_encode($errorMessage) ?>;
</script>

<!-- Include external JavaScript file -->
<script src="../../../public/js/notification.js"></script> 