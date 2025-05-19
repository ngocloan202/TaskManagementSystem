document.addEventListener('DOMContentLoaded', function() {
  const deleteProjectBtn = document.getElementById('deleteProjectBtn');
  const deleteProjectModal = document.getElementById('deleteProjectModal');
  const deleteProjectName = document.getElementById('deleteProjectName');
  const confirmDeleteProject = document.getElementById('confirmDeleteProject');
  const cancelDeleteProject = document.getElementById('cancelDeleteProject');
  
  // Get project ID and name from the page
  const projectDetailPage = document.getElementById('projectDetailPage');
  const projectId = projectDetailPage.dataset.projectId;
  const projectNameDisplay = document.getElementById('projectNameDisplay');
  
  // Show delete confirmation modal
  if (deleteProjectBtn) {
    deleteProjectBtn.addEventListener('click', function() {
      // Set project name in the confirmation message
      deleteProjectName.textContent = projectNameDisplay.textContent.trim();
      // Show modal
      deleteProjectModal.classList.remove('hidden');
    });
  }
  
  // Cancel delete
  if (cancelDeleteProject) {
    cancelDeleteProject.addEventListener('click', function() {
      deleteProjectModal.classList.add('hidden');
    });
    
    // Also close modal when clicking outside
    deleteProjectModal.addEventListener('click', function(e) {
      if (e.target === deleteProjectModal) {
        deleteProjectModal.classList.add('hidden');
      }
    });
  }
  
  // Confirm delete
  if (confirmDeleteProject) {
    confirmDeleteProject.addEventListener('click', function() {
      // Show loading state
      confirmDeleteProject.textContent = 'Đang xóa...';
      confirmDeleteProject.disabled = true;
      
      // Send to API
      fetch('../../../api/project/DeleteProject.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id: projectId
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Show success notification before redirecting
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center';
          notification.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>${data.message}</span>
          `;
          document.body.appendChild(notification);
          
          // Redirect after a short delay
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 1500);
          
        } else {
          // Show error
          deleteProjectModal.classList.add('hidden');
          
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center';
          notification.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>${data.message}</span>
          `;
          document.body.appendChild(notification);
          
          // Remove notification after 3 seconds with fade effect
          setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease-out';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
          }, 3000);
        }
      })
      .catch(error => {
        console.error('Error deleting project:', error);
        
        // Show error notification
        deleteProjectModal.classList.add('hidden');
        
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center';
        notification.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <span>Lỗi: Không thể kết nối với máy chủ</span>
        `;
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds with fade effect
        setTimeout(() => {
          notification.style.transition = 'opacity 0.5s ease-out';
          notification.style.opacity = '0';
          setTimeout(() => notification.remove(), 500);
        }, 3000);
      })
      .finally(() => {
        // Reset button
        confirmDeleteProject.textContent = 'Xóa dự án';
        confirmDeleteProject.disabled = false;
      });
    });
  }
}); 