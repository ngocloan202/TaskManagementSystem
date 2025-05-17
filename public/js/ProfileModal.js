document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("profileModal");
  const form = document.getElementById("profileForm");
  const inputs = form.querySelectorAll('input:not([type="hidden"])');
  const openBtn = document.getElementById("openProfile");
  const editBtn = document.getElementById("btnProfileEdit");
  const saveBtn = document.getElementById("btnProfileSave");
  const closeBtn = document.getElementById("closeProfileModal");

  // Set initial readonly state
  function initializeForm() {
    inputs.forEach(input => {
      input.readOnly = true;
      input.classList.add("bg-gray-50", "cursor-not-allowed");
    });
  }

  // Reset state function
  function resetState() {
    inputs.forEach(input => {
      input.readOnly = true;
      input.classList.add("bg-gray-50", "cursor-not-allowed");
    });
    editBtn.classList.remove("hidden");
    saveBtn.classList.add("hidden");
  }

  // Initialize form when page loads
  initializeForm();

  // Open modal
  openBtn.addEventListener("click", e => {
    e.preventDefault();
    modal.classList.remove("hidden");
    resetState();
  });

  // Edit button click
  editBtn.addEventListener("click", () => {
    inputs.forEach(input => {
      input.readOnly = false;
      input.classList.remove("bg-gray-50", "cursor-not-allowed");
    });
    editBtn.classList.add("hidden");
    saveBtn.classList.remove("hidden");
  });

  closeBtn.addEventListener("click", closeModal);

  profileModal.addEventListener("click", function (e) {
    if (e.target === profileModal) {
      closeModal();
    }
  });
});
