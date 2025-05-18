function setTaskStatus(status) {
    document.getElementById('statusField').value = status;
  }

  document.addEventListener('DOMContentLoaded', function() {
    const colorButtons = document.querySelectorAll('.color-btn');

    colorButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        colorButtons.forEach(b => {
          b.classList.remove('ring-2', 'ring-offset-2', 'ring-gray-800');
        });
        
        this.classList.add('ring-2', 'ring-offset-2', 'ring-gray-800');
        document.getElementById('colorField').value = this.getAttribute('data-color');
      });
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