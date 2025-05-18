function setTaskStatus(status) {
    document.getElementById('statusField').value = status;
  }

  document.querySelectorAll('.color-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.color-btn').forEach(b => {
        b.classList.remove('border-gray-800');
        b.classList.add('border-transparent');
      });
      
      this.classList.remove('border-transparent');
      this.classList.add('border-gray-800');
      
      document.getElementById('colorField').value = this.getAttribute('data-color');
    });
  });

  document.querySelectorAll('.close-dialog').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('createTaskDialog').classList.add('hidden');
    });
  });

  document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    // Debug logging
    console.log('Form data:');
    for (let pair of formData.entries()) {
      console.log(pair[0] + ': ' + pair[1]);
    }

    fetch('/app/Views/tasks/CreateTask.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('createTaskDialog').classList.add('hidden');
        window.location.reload();
      } else {
        alert('Có lỗi xảy ra: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Đã xảy ra lỗi khi tạo nhiệm vụ');
    });
  });

  function addTask(statusName) {
    document.getElementById('createTaskDialog').classList.remove('hidden');
    document.getElementById('statusField').value = statusName;
  }