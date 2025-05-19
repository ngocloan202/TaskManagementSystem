class ProjectManager {
    constructor() {
        this.lastUrl = "";
        this.initializeMemberDialog();
        this.initializeTaskDialog();
    }

    initializeMemberDialog() {
        this.btnMember = document.getElementById("btnMember");
        if (this.btnMember) {
            this.btnMember.addEventListener("click", () => {
                this.openMemberDialog();
            });
        }

        this.closeMemberDialog = document.getElementById("closeMemberDialog");
        if (this.closeMemberDialog) {
            this.closeMemberDialog.addEventListener("click", () => {
                this.closeMemberModal();
            });
        }

        this.memberDialog = document.getElementById("memberDialog");
        if (this.memberDialog) {
            this.memberDialog.addEventListener("click", e => {
                if (e.target === this.memberDialog) {
                    this.closeMemberModal();
                }
            });
        }

        document.addEventListener("keydown", e => {
            if (
                e.key === "Escape" &&
                !this.memberDialog.classList.contains("hidden")
            ) {
                this.closeMemberModal();
            }
        });

        setInterval(() => this.checkForRedirects(), 500);
    }

    openMemberDialog() {
        this.memberDialog.classList.remove("hidden");
        this.refreshMemberDialog();
    }

    closeMemberModal() {
        if (!this.memberModal) return;
        this.memberModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    refreshMemberDialog() {
        this.dialogContent = document.getElementById("memberDialogContent");
        if (!this.dialogContent) return;

        this.dialogContent.innerHTML = this.getLoadingSpinner();

        const projectId = document.querySelector('input[name="projectId"]')?.value;

        fetch(`../projects/DialogManageMembers.php?projectID=${projectId}&embed=1`)
            .then(response => response.text())
            .then(html => {
                this.dialogContent.innerHTML = html;
                this.attachMemberDialogEvents();
                this.refreshProjectHeader();
            })
            .catch(error => {
                this.dialogContent.innerHTML = `<div class="text-red-500 p-4">Đã xảy ra lỗi khi tải nội dung: ${error.message}</div>`;
            });
    }

    refreshProjectHeader() {
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

    attachMemberDialogEvents() {
        document.addEventListener("memberDataChanged", () => {
            this.refreshMemberDialog();
        });

        const editButtons = document.querySelectorAll(".edit-member-btn");
        editButtons.forEach(button => {
            button.addEventListener("click", e => {
                e.preventDefault();
                const memberId = button.getAttribute("data-member-id");
                this.openMemberModal(memberId);
            });
        });

        const addMemberBtn = document.getElementById("btnAddMember");
        if (addMemberBtn) {
            addMemberBtn.addEventListener("click", e => {
                e.preventDefault();
                this.openMemberModal(0);
            });
        }
    }

    checkForRedirects() {
        if (!this.memberDialog || this.memberDialog.classList.contains("hidden")) return;

        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        if (params.has("memberAction") && this.lastUrl !== window.location.href) {
            this.lastUrl = window.location.href;
            this.refreshMemberDialog();

            const newUrl =
                window.location.pathname + window.location.search.replace(/&?memberAction=[^&]*/, "");
            window.history.replaceState({}, document.title, newUrl);
        }
    }

    openMemberModal(memberId = 0) {
        this.memberModal = document.getElementById("memberModal");
        if (!this.memberModal) {
            console.error("Modal not found!");
            return;
        }

        const modalTitle = document.getElementById("memberModalLabel");
        if (modalTitle) {
            modalTitle.textContent = memberId > 0 ? "Chỉnh sửa thành viên" : "Thêm thành viên";
        }

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

        this.memberModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (form) {
            form.onsubmit = (e) => this.handleMemberFormSubmit(e);
        }
    }

    async handleMemberFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        const memberId = formData.get('projectMemberId');
        let endpoint = '../projects/AddMemberProcess.php';
        if (memberId && parseInt(memberId) > 0) {
            endpoint = '../projects/EditMemberProcess.php';
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('success', data.message);
                this.closeMemberModal();
                setTimeout(() => this.refreshMemberDialog(), 1200);
            } else {
                this.showNotification('error', data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            this.showNotification('error', 'Lỗi kết nối: ' + error.message);
        }
    }

    showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-[10000] ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white max-w-md`;

        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-3">
                    ${type === 'success' 
                        ? '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
                        : '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                    }
                </div>
                <div>${message}</div>
                <button class="ml-auto -mr-1 text-white hover:text-gray-100" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }

    async deleteMember(memberId, userName) {
        if (!memberId) return;
        if (!confirm(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('projectId', document.querySelector('input[name="projectId"]')?.value);
        formData.append('projectMemberId', memberId);

        try {
            const response = await fetch('../projects/DeleteMemberProcess.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('success', data.message);
                setTimeout(() => this.refreshMemberDialog(), 1000);
            } else {
                this.showNotification('error', data.message || 'Có lỗi xảy ra khi xóa thành viên');
            }
        } catch (error) {
            this.showNotification('error', 'Lỗi kết nối: ' + error.message);
        }
    }

    getLoadingSpinner() {
        return `<div class="flex justify-center items-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </div>`;
    }

    initializeTaskDialog() {
        this.closeButton = document.querySelector(
            '#createTaskDialog button[class*="hover:bg-indigo-500"]'
        );
        if (this.closeButton) {
            this.closeButton.addEventListener("click", () => {
                this.closeTaskDialog();
            });
        }
    }

    openTaskDialog() {
        if (!this.taskDialog) return;
        this.taskDialog.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeTaskDialog() {
        if (!this.taskDialog) return;
        this.taskDialog.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.projectManager = new ProjectManager();
});

window.deleteMember = (memberId, userName) => {
    window.projectManager?.deleteMember(memberId, userName);
};
window.openMemberModal = (memberId = 0) => {
    window.projectManager?.openMemberModal(memberId);
};
