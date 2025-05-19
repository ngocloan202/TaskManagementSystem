document.addEventListener('DOMContentLoaded', function() {
  // Project Description editing functionality
  const projectDescriptionDisplay = document.getElementById('projectDescriptionDisplay');
  const editProjectDescriptionBtn = document.getElementById('editProjectDescriptionBtn');
  const projectDescriptionEditForm = document.getElementById('projectDescriptionEditForm');
  const projectDescriptionInput = document.getElementById('projectDescriptionInput');
  const saveProjectDescriptionBtn = document.getElementById('saveProjectDescriptionBtn');
  const cancelProjectDescriptionBtn = document.getElementById('cancelProjectDescriptionBtn');
  const projectDescriptionError = document.getElementById('projectDescriptionError');
  
  // Toggle edit mode for project description
  if (editProjectDescriptionBtn) {
    editProjectDescriptionBtn.addEventListener('click', function() {
      projectDescriptionDisplay.classList.add('hidden');
      projectDescriptionEditForm.classList.remove('hidden');
      
      // Set the textarea value to current project description
      projectDescriptionInput.value = projectDescriptionDisplay.textContent.trim();
      
      // Focus the textarea
      setTimeout(() => {
        projectDescriptionInput.focus();
      }, 50);
    });
  }
  
  // Cancel editing description
  if (cancelProjectDescriptionBtn) {
    cancelProjectDescriptionBtn.addEventListener('click', function() {
      projectDescriptionDisplay.classList.remove('hidden');
      projectDescriptionEditForm.classList.add('hidden');
      projectDescriptionError.classList.add('hidden');
      // Reset input to original value
      projectDescriptionInput.value = projectDescriptionDisplay.textContent.trim();
    });
  }
  
  // Save project description
  if (saveProjectDescriptionBtn) {
    saveProjectDescriptionBtn.addEventListener('click', function() {
      const newDescription = projectDescriptionInput.value.trim();
      
      // Show loading state
      saveProjectDescriptionBtn.textContent = 'Đang lưu...';
      saveProjectDescriptionBtn.disabled = true;
      
      // Get project ID from the page
      const projectId = document.getElementById('projectDetailPage').dataset.projectId;
      
      // Send to API
      fetch('../../../api/project/UpdateProjectDescription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id: projectId,
          project_description: newDescription
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update the displayed description
          projectDescriptionDisplay.textContent = newDescription;
          
          // Exit edit mode
          projectDescriptionDisplay.classList.remove('hidden');
          projectDescriptionEditForm.classList.add('hidden');
          
          // Show success feedback
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center';
          notification.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Đã cập nhật mô tả dự án thành công!</span>
          `;
          document.body.appendChild(notification);
          
          // Remove notification after 3 seconds with fade effect
          setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease-out';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
          }, 3000);
          
        } else {
          // Show error
          projectDescriptionError.textContent = data.message || 'Không thể cập nhật mô tả dự án';
          projectDescriptionError.classList.remove('hidden');
        }
      })
      .catch(error => {
        console.error('Error updating project description:', error);
        projectDescriptionError.textContent = 'Lỗi: Không thể kết nối với máy chủ';
        projectDescriptionError.classList.remove('hidden');
      })
      .finally(() => {
        // Reset button
        saveProjectDescriptionBtn.textContent = 'Lưu';
        saveProjectDescriptionBtn.disabled = false;
      });
    });
  }
}); 