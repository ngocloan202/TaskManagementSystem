document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("profileModal");
  const form = document.getElementById("profileForm");
  const inputs = form.querySelectorAll('input:not([type="hidden"]):not([name="project_count"])');
  const btnOpen = document.getElementById("openProfile");
  const btnEdit = document.getElementById("btnProfileEdit");
  const btnSave = document.getElementById("btnProfileSave");
  const closeBtn = document.getElementById("closeProfileModal");
  const btnCancel = document.getElementById("btnProfileCancel");
  const confirmModal = document.getElementById("confirmSaveModal");
  const btnConfirmSave = document.getElementById("btnConfirmSave");
  const btnCancelSave = document.getElementById("btnCancelSave");

  // Lưu giá trị ban đầu để hoàn tác
  let initialValues = {};
  function storeInitialValues() {
    inputs.forEach(input => {
      initialValues[input.name] = input.value;
    });
  }
  function restoreInitialValues() {
    inputs.forEach(input => {
      if (initialValues[input.name] !== undefined) {
        input.value = initialValues[input.name];
      }
    });
  }

  // Hàm kiểm tra có thay đổi không
  function hasChanged() {
    return Array.from(inputs).some(input => input.value !== initialValues[input.name]);
  }
  // Hàm cập nhật trạng thái nút Lưu
  function updateSaveButtonState() {
    btnSave.disabled = !hasChanged();
    btnSave.classList.toggle('opacity-50', btnSave.disabled);
    btnSave.classList.toggle('cursor-not-allowed', btnSave.disabled);
  }

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
    btnCancel.classList.add("hidden");
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
    storeInitialValues();
    inputs.forEach(i => {
      i.readOnly = false;
      i.classList.remove("bg-gray-50", "cursor-not-allowed");
    });
    btnEdit.classList.add("hidden");
    btnSave.classList.remove("hidden");
    btnCancel.classList.remove("hidden");
    updateSaveButtonState(); // Disable save at first
    // Lắng nghe thay đổi
    inputs.forEach(input => {
      input.addEventListener('input', updateSaveButtonState);
    });
  });

  // 5.1 Nhấn Hủy
  btnCancel.addEventListener("click", () => {
    restoreInitialValues();
    resetState();
    btnCancel.classList.add("hidden");
    // Bỏ lắng nghe
    inputs.forEach(input => {
      input.removeEventListener('input', updateSaveButtonState);
    });
  });

  // 5.2 Nhấn Lưu: Hiện modal xác nhận
  btnSave.addEventListener("click", (e) => {
    if (btnSave.disabled) return; // Không cho lưu nếu chưa thay đổi
    e.preventDefault();
    const formData = new FormData(form);
    confirmModal.classList.remove("hidden");
  });

  // 5.3 Xác nhận lưu (Có)
  btnConfirmSave.addEventListener("click", () => {
    // Gửi AJAX tới UpdateProfile.php
    const formData = new FormData(form);
    fetch('/app/Controllers/UpdateProfile.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.text())
      .then(text => {
        if (text.trim() === 'success') {
          // Cập nhật lại initialValues
          storeInitialValues();
          resetState();
          btnCancel.classList.add("hidden");
          // Bỏ lắng nghe
          inputs.forEach(input => {
            input.removeEventListener('input', updateSaveButtonState);
          });
          // cập nhật UI ngay (nếu có avatar mới)
          if (formData.get('avatar')) {
            document.getElementById('profileAvatar').src = formData.get('avatar');
            const profileBtnImg = document.querySelector('#profileBtn img');
            if (profileBtnImg) profileBtnImg.src = formData.get('avatar');
          }
          // Sau khi lưu thành công:
          const successModal = document.getElementById('profileSuccessModal');
          if (successModal) {
            successModal.classList.remove('hidden');
            // Đóng modal khi bấm nút Đóng
            document.getElementById('closeProfileSuccessModal').onclick = () => {
              successModal.classList.add('hidden');
            };
          }
        } else {
          alert('Lỗi khi lưu: ' + text);
        }
        confirmModal.classList.add("hidden");
      })
      .catch(() => {
        alert('Không thể kết nối server');
        confirmModal.classList.add("hidden");
      });
  });

  // 5.4 Không lưu (Không)
  btnCancelSave.addEventListener("click", () => {
    restoreInitialValues();
    resetState();
    btnCancel.classList.add("hidden");
    confirmModal.classList.add("hidden");
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