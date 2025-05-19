function setTaskStatus(status) {
  document.getElementById("statusField").value = status;
}

document.addEventListener("DOMContentLoaded", () => {
  const colorButtons = document.querySelectorAll(".color-btn");
  const hiddenField = document.getElementById("colorField");

  colorButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      colorButtons.forEach(b => b.classList.remove("selected"));

      btn.classList.add("selected");
      hiddenField.value = btn.getAttribute("data-color");
    });
  });
});

document.querySelectorAll(".close-dialog").forEach(btn => {
  btn.addEventListener("click", function (e) {
    e.preventDefault();
    document.getElementById("createTaskDialog").classList.add("hidden");
  });
});

document.getElementById("taskForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch("/app/Views/tasks/CreateTask.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById("createTaskDialog").classList.add("hidden");
        window.location.reload();
      } else {
        alert("Có lỗi xảy ra: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Đã xảy ra lỗi khi tạo nhiệm vụ");
    });
});

function addTask(statusName) {
  document.getElementById("createTaskDialog").classList.remove("hidden");
  document.getElementById("statusField").value = statusName;
}
