document.addEventListener('DOMContentLoaded', function() {
  // Function to resize the input based on content
  function resizeProjectNameInput() {
    const sizer = document.getElementById('projectNameSizer');
    const input = document.getElementById('projectNameInput');
    
    if (!sizer || !input) return;
    
    // Set the sizer's font properties to match the input
    const inputStyle = window.getComputedStyle(input);
    sizer.style.fontSize = inputStyle.fontSize;
    sizer.style.fontFamily = inputStyle.fontFamily;
    sizer.style.fontWeight = inputStyle.fontWeight;
    
    // Set sizer content and get its width
    sizer.textContent = input.value || projectNameDisplay.textContent.trim();
    
    // Add some extra width for padding and comfortable editing
    const extraWidth = 30;  // in pixels
    
    // Set input width based on text width plus padding
    input.style.width = (sizer.offsetWidth + extraWidth) + 'px';
  }
  
  // Toggle edit mode for project name
  const projectNameDisplay = document.getElementById('projectNameDisplay');
  const editProjectNameBtn = document.getElementById('editProjectNameBtn');
  const projectNameEditForm = document.getElementById('projectNameEditForm');
  const projectNameInput = document.getElementById('projectNameInput');
  const saveProjectNameBtn = document.getElementById('saveProjectNameBtn');
  const cancelProjectNameBtn = document.getElementById('cancelProjectNameBtn');
  const projectNameError = document.getElementById('projectNameError');
  
  if (editProjectNameBtn) {
    editProjectNameBtn.addEventListener('click', function() {
      projectNameDisplay.classList.add('hidden');
      projectNameEditForm.classList.remove('hidden');
      
      // Set the input value to current project name
      projectNameInput.value = projectNameDisplay.textContent.trim();
      
      // Resize the input based on current text
      resizeProjectNameInput();
      
      // Focus and select the text after a small delay
      setTimeout(() => {
        projectNameInput.focus();
        projectNameInput.select();
      }, 50);
    });
  }
  
  // Make the input field resize automatically as you type
  if (projectNameInput) {
    projectNameInput.addEventListener('input', resizeProjectNameInput);
  }
  
  // Cancel editing
  if (cancelProjectNameBtn) {
    cancelProjectNameBtn.addEventListener('click', function() {
      projectNameDisplay.classList.remove('hidden');
      projectNameEditForm.classList.add('hidden');
      projectNameError.classList.add('hidden');
      // Reset input to original value
      projectNameInput.value = projectNameDisplay.textContent.trim();
    });
  }
  
  // Save project name
  if (saveProjectNameBtn) {
    saveProjectNameBtn.addEventListener('click', function() {
      const newName = projectNameInput.value.trim();
      
      // Validation
      if (!newName) {
        projectNameError.textContent = 'Tên dự án không được để trống';
        projectNameError.classList.remove('hidden');
        return;
      }
      
      // Show loading state
      saveProjectNameBtn.textContent = 'Đang lưu...';
      saveProjectNameBtn.disabled = true;
      
      // Get project ID from the page
      const projectId = document.getElementById('projectDetailPage').dataset.projectId;
      
      // Send to API
      fetch('../../../api/project/UpdateProjectName.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id: projectId,
          project_name: newName
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update the displayed name in all locations
          projectNameDisplay.textContent = newName;
          document.getElementById('breadcrumbProjectName').textContent = newName;
          
          // Update the project name in the sidebar
          const sidebarProjectNames = document.querySelectorAll('.sidebar-project-name');
          sidebarProjectNames.forEach(element => {
            if (element.dataset.projectId == projectId) {
              element.textContent = newName;
            }
          });
          
          // Update document title
          document.title = newName + " | CubeFlow";
          
          // Exit edit mode
          projectNameDisplay.classList.remove('hidden');
          projectNameEditForm.classList.add('hidden');
          
          // Show success feedback - more visible notification
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center';
          notification.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Đã cập nhật tên dự án thành công!</span>
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
          projectNameError.textContent = data.message || 'Không thể cập nhật tên dự án';
          projectNameError.classList.remove('hidden');
        }
      })
      .catch(error => {
        console.error('Error updating project name:', error);
        projectNameError.textContent = 'Lỗi: Không thể kết nối với máy chủ';
        projectNameError.classList.remove('hidden');
      })
      .finally(() => {
        // Reset button
        saveProjectNameBtn.textContent = 'Lưu';
        saveProjectNameBtn.disabled = false;
      });
    });
  }
}); 