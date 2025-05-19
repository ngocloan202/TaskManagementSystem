// Quản lý modal thêm/sửa thành viên
class MemberManager {
    constructor() {
        this.modal = document.getElementById('memberModal');
        this.form = document.getElementById('memberForm');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Nút thêm thành viên
        const addMemberBtn = document.getElementById('btnAddMember');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', () => this.showAddMemberModal());
        }

        // Nút đóng modal
        const closeBtn = this.modal?.querySelector('button[onclick="toggleModal()"]');
        if (closeBtn) {
            closeBtn.onclick = (e) => {
                e.preventDefault();
                this.hideModal();
            };
        }

        // Xử lý submit form
        if (this.form) {
            this.form.onsubmit = (e) => this.handleFormSubmit(e);
        }

        // Xử lý tìm kiếm
        const searchInput = document.getElementById('searchMember');
        const searchBtn = document.getElementById('btnSearch');
        if (searchInput && searchBtn) {
            searchBtn.addEventListener('click', () => this.handleSearch(searchInput.value));
        }
    }

    // Hiển thị modal thêm thành viên mới
    showAddMemberModal() {
        if (!this.modal) return;
        
        // Reset form
        this.form?.reset();
        
        // Set projectId
        const projectIdField = this.form?.querySelector('input[name="projectId"]');
        if (projectIdField) {
            projectIdField.value = window.projectID;
        }

        // Set title
        const modalTitle = document.getElementById('memberModalLabel');
        if (modalTitle) {
            modalTitle.textContent = "Thêm thành viên";
        }

        // Hiển thị modal
        this.showModal();
    }

    // Hiển thị modal
    showModal() {
        if (!this.modal) return;
        this.modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Ẩn modal
    hideModal() {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Xử lý submit form
    async handleFormSubmit(e) {
        e.preventDefault();
        if (!this.form) return;

        const formData = new FormData(this.form);
        
        try {
            const response = await fetch('/app/Views/projects/AddMemberProcess.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                this.showNotification('success', data.message);
                this.hideModal();
                // Reload trang sau 1.5s
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showNotification('error', data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            this.showNotification('error', 'Lỗi kết nối: ' + error.message);
        }
    }

    // Xử lý xóa thành viên
    async deleteMember(memberId, userName) {
        if (!memberId) return;
        
        if (!confirm(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('projectId', window.projectID);
        formData.append('projectMemberId', memberId);

        try {
            const response = await fetch('DeleteMemberProcess.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                this.showNotification('success', data.message);
                // Xóa row khỏi table
                const memberRow = document.querySelector(`tr[data-member-id="${memberId}"]`);
                if (memberRow) {
                    memberRow.remove();
                }
            } else {
                this.showNotification('error', data.message || 'Có lỗi xảy ra khi xóa thành viên');
            }
        } catch (error) {
            this.showNotification('error', 'Lỗi kết nối: ' + error.message);
        }
    }

    handleSearch(query) {
        const rows = document.querySelectorAll('tbody tr');
        const searchTerm = query.toLowerCase();

        rows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const username = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const role = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            const matches = name.includes(searchTerm) || 
                          username.includes(searchTerm) || 
                          role.includes(searchTerm);

            row.style.display = matches ? '' : 'none';
        });
    }

    // Hiển thị thông báo
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
}

// Khởi tạo khi DOM đã load xong
document.addEventListener('DOMContentLoaded', () => {
    window.memberManager = new MemberManager();
});

// Export các hàm cần thiết ra window
window.deleteMember = (memberId, userName) => {
    window.memberManager?.deleteMember(memberId, userName);
};