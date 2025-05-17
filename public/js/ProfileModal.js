document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("profileModal");
  const form = document.getElementById("profileForm");
  const inputs = form.querySelectorAll('input:not([type="hidden"]):not([name="project_count"])');  const btnOpen = document.getElementById("openProfile");
  const btnEdit = document.getElementById("btnProfileEdit");
  const btnSave = document.getElementById("btnProfileSave");
  const closeBtn = document.getElementById("closeProfileModal");

  // 1. Định nghĩa hàm đóng modal
  function closeModal() {
    modal.classList.add("hidden");
    // Optionally reset về readonly / nút Sửa hiện, Lưu ẩn
    inputs.forEach(i => {
      i.readOnly = true;
      i.classList.add("bg-gray-50", "cursor-not-allowed");
    });
    btnEdit.classList.remove("hidden");
    btnSave.classList.add("hidden");
  }

  // 2. Hàm reset trạng thái form (readonly + nút)
  function resetState() {
    inputs.forEach(input => {
      input.readOnly = true;
      input.classList.add("bg-gray-50", "cursor-not-allowed");
    });
    btnEdit.classList.remove("hidden");
    btnSave.classList.add("hidden");
  }

  // 3. Initialize
  inputs.forEach(input => {
    input.readOnly = true;
    input.classList.add("bg-gray-50", "cursor-not-allowed");
  });

  // 4. Mở modal
  btnOpen.addEventListener("click", e => {
    e.preventDefault();
    modal.classList.remove("hidden");
    resetState();
  });

  // 5. Nhấn Sửa
  btnEdit.addEventListener("click", () => {
    inputs.forEach(i => {
      i.readOnly = false;
      i.classList.remove("bg-gray-50", "cursor-not-allowed");
    });
    btnEdit.classList.add("hidden");
    btnSave.classList.remove("hidden");
  });

  // 6. Đóng modal khi bấm nút X
  closeBtn.addEventListener("click", closeModal);

  // 7. Đóng modal khi click ra ngoài backdrop
  modal.addEventListener("click", e => {
    if (e.target === modal) {
      closeModal();
    }
  });
});
