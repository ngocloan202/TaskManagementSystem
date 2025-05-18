// Project Detail Page JavaScript

// Global variables
let lastUrl = "";

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", function () {
  initializeMemberDialog();
  initializeTaskDialog();
});

// Member Dialog Functions
function initializeMemberDialog() {
  const btnMember = document.getElementById("btnMember");
  if (btnMember) {
    btnMember.addEventListener("click", () => {
      document.getElementById("memberDialog").classList.remove("hidden");
      refreshMemberDialog();
    });
  }

  // Close dialog handlers
  const closeMemberDialog = document.getElementById("closeMemberDialog");
  if (closeMemberDialog) {
    closeMemberDialog.addEventListener("click", () => {
      document.getElementById("memberDialog").classList.add("hidden");
    });
  }

  const memberDialog = document.getElementById("memberDialog");
  if (memberDialog) {
    memberDialog.addEventListener("click", e => {
      if (e.target === memberDialog) {
        memberDialog.classList.add("hidden");
      }
    });
  }

  // Escape key handler
  document.addEventListener("keydown", e => {
    if (
      e.key === "Escape" &&
      !document.getElementById("memberDialog").classList.contains("hidden")
    ) {
      document.getElementById("memberDialog").classList.add("hidden");
    }
  });

  // Check for redirects periodically
  setInterval(checkForRedirects, 500);
}

function refreshMemberDialog() {
  const dialogContent = document.getElementById("memberDialogContent");
  if (!dialogContent) return;

  // Show loading indicator
  dialogContent.innerHTML =
    '<div class="flex justify-center items-center h-[60vh]"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div></div>';

  // Get project ID from the page
  const projectId = document.querySelector('input[name="projectId"]')?.value;

  // Fetch the content
  fetch(`../projects/DialogManageMembers.php?projectID=${projectId}&embed=1`)
    .then(response => response.text())
    .then(html => {
      dialogContent.innerHTML = html;
      attachMemberDialogEvents();
      refreshProjectHeader();
    })
    .catch(error => {
      dialogContent.innerHTML = `<div class="text-red-500 p-4">Đã xảy ra lỗi khi tải nội dung: ${error.message}</div>`;
    });
}

function refreshProjectHeader() {
  const projectId = document.querySelector('input[name="projectId"]')?.value;

  fetch(`ProjectHeaderPartial.php?id=${projectId}`)
    .then(response => response.text())
    .then(html => {
      const tempContainer = document.createElement("div");
      tempContainer.innerHTML = html;

      const newAvatars = tempContainer.querySelector(".flex.items-center.-space-x-2");
      const currentAvatarsContainer = document.querySelector(".flex.items-center.-space-x-2");

      if (newAvatars && currentAvatarsContainer) {
        const button = currentAvatarsContainer.querySelector("#btnMember");
        currentAvatarsContainer.innerHTML = newAvatars.innerHTML;
        if (button) currentAvatarsContainer.appendChild(button);
      }
    })
    .catch(error => {
      console.error("Error refreshing project header:", error);
    });
}

function attachMemberDialogEvents() {
  document.addEventListener("memberDataChanged", () => {
    refreshMemberDialog();
  });

  // Edit buttons
  const editButtons = document.querySelectorAll(".edit-member-btn");
  editButtons.forEach(button => {
    button.addEventListener("click", e => {
      e.preventDefault();
      const memberId = button.getAttribute("data-member-id");
      openModal("memberModal", memberId);
    });
  });

  // Add member button
  const addMemberBtn = document.getElementById("btnAddMember");
  if (addMemberBtn) {
    addMemberBtn.addEventListener("click", e => {
      e.preventDefault();
      openModal("memberModal", 0);
    });
  }
}

function checkForRedirects() {
  const memberDialog = document.getElementById("memberDialog");
  if (!memberDialog || memberDialog.classList.contains("hidden")) return;

  const url = new URL(window.location.href);
  const params = new URLSearchParams(url.search);

  if (params.has("memberAction") && lastUrl !== window.location.href) {
    lastUrl = window.location.href;
    refreshMemberDialog();

    const newUrl =
      window.location.pathname + window.location.search.replace(/&?memberAction=[^&]*/, "");
    window.history.replaceState({}, document.title, newUrl);
  }
}

// Modal Functions
function openModal(modalId, memberId = 0) {
  const modal = document.getElementById("memberModal");
  if (!modal) {
    console.error("Modal not found!");
    return;
  }

  // Set title
  const modalTitle = document.getElementById("memberModalLabel");
  if (modalTitle) {
    modalTitle.textContent = memberId > 0 ? "Chỉnh sửa thành viên" : "Thêm thành viên";
  }

  // Update form
  const form = document.getElementById("memberForm");
  const memberIdInput = document.getElementById("projectMemberId");

  if (form && memberIdInput) {
    memberIdInput.value = memberId;

    if (memberId > 0) {
      const roleSelect = document.getElementById("roleSelect");
      if (roleSelect) {
        const memberRow = document.querySelector(`tr[data-member-id="${memberId}"]`);
        if (memberRow) {
          const roleId = memberRow.getAttribute("data-role-id");
          if (roleId) {
            roleSelect.value = roleId;
          }
        }
      }
    } else {
      form.reset();
      const projectIdField = form.querySelector('input[name="projectId"]');
      if (projectIdField) {
        projectIdField.value = document.querySelector('input[name="projectId"]')?.value;
      }
    }
  }

  modal.style.display = "flex";
  document.body.style.overflow = "hidden";
}

function toggleModal() {
  const modal = document.getElementById("memberModal");
  if (!modal) {
    console.error("Modal not found!");
    return;
  }

  const currentDisplay = modal.style.display;
  modal.style.display = currentDisplay === "none" || currentDisplay === "" ? "flex" : "none";
  document.body.style.overflow = modal.style.display === "flex" ? "hidden" : "auto";
}

// Task Dialog Functions
function initializeTaskDialog() {
  const closeButton = document.querySelector(
    '#createTaskDialog button[class*="hover:bg-indigo-500"]'
  );
  if (closeButton) {
    closeButton.addEventListener("click", () => {
      document.getElementById("createTaskDialog").classList.add("hidden");
    });
  }
}

function addTask(statusName) {
  document.getElementById("createTaskDialog").classList.remove("hidden");
  document.getElementById("statusField").value = statusName;
}

// Notification Function
function showNotification(type, message) {
  const notificationDiv = document.createElement("div");
  notificationDiv.className = `fixed top-4 right-4 z-[9999] px-4 py-3 rounded-lg shadow-lg transition-all duration-300 max-w-md ${
    type === "success" ? "bg-green-500" : "bg-red-500"
  } text-white`;

  const closeBtn = document.createElement("button");
  closeBtn.className = "absolute top-1 right-1 text-white";
  closeBtn.innerHTML = "×";
  closeBtn.onclick = () => {
    if (document.body.contains(notificationDiv)) {
      document.body.removeChild(notificationDiv);
    }
  };

  const messageSpan = document.createElement("span");
  messageSpan.textContent = message;

  notificationDiv.appendChild(closeBtn);
  notificationDiv.appendChild(messageSpan);
  document.body.appendChild(notificationDiv);

  setTimeout(() => {
    if (document.body.contains(notificationDiv)) {
      document.body.removeChild(notificationDiv);
    }
  }, 5000);
}

// Make functions globally available
window.showNotification = showNotification;
window.openModal = openModal;
window.toggleModal = toggleModal;
window.addTask = addTask;
