// Quản lý modal thêm/sửa thành viên
class MemberManager {
    constructor() {
        // Lấy projectID từ nhiều nguồn khác nhau
        window.projectID = this.getProjectID();
        
        // Thiết lập event listeners cho nút thêm thành viên
        this.setupAddButtonListener();
        
        // Đăng ký sự kiện xóa toàn cục
        window.deleteMember = (memberId, userName) => {
            this.deleteMember(memberId, userName);
        };
        
        // Khởi tạo modal events ngay nếu modal đã hiển thị sẵn
        const modal = document.getElementById('memberModal');
        if (modal && !modal.classList.contains('hidden')) {
            this.setupModalEvents();
        }
    }
    
    // Lấy projectID từ nhiều nguồn khác nhau
    getProjectID() {
        // 1. Từ form thêm thành viên
        const projectIdInput = document.querySelector('#memberForm input[name="projectId"]');
        if (projectIdInput && projectIdInput.value) {
            return projectIdInput.value;
        }
        
        // 2. Từ URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const projectID = urlParams.get('projectID') || urlParams.get('id');
        if (projectID) {
            return projectID;
        }
        
        // 3. Từ data attribute trong body
        const body = document.body;
        if (body.dataset.projectId) {
            return body.dataset.projectId;
        }
        
        return 0;
    }

    setupAddButtonListener() {
        // Nút mở modal
        const addMemberBtn = document.getElementById('btnAddMember');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', () => this.showAddMemberModal());
        }
    }
    
    // Thiết lập các event handlers cho modal
    setupModalEvents() {
        const modal = document.getElementById('memberModal');
        const form = document.getElementById('memberForm');
        
        // Nút đóng modal (X)
        const closeBtn = document.getElementById('closeMemberModal');
        if (closeBtn) {
            // Xóa bỏ event handlers cũ nếu có
            closeBtn.onclick = null;
            
            // Gắn event handler mới
            closeBtn.addEventListener('click', () => {
                this.hideModal();
            });
        }

        // Nút Hủy
        const cancelBtn = document.getElementById('cancelMemberModal');
        if (cancelBtn) {
            // Xóa bỏ event handlers cũ nếu có
            cancelBtn.onclick = null;
            
            // Gắn event handler mới
            cancelBtn.addEventListener('click', () => {
                this.hideModal();
            });
        }
        
        // Đóng modal khi click ra ngoài
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.hideModal();
                }
            };
        }
        
        // Xử lý submit form
        if (form) {
            form.onsubmit = (e) => this.handleFormSubmit(e, form);
        }
    }
    
    // Hiển thị modal thêm thành viên mới
    showAddMemberModal() {
        const modal = document.getElementById('memberModal');
        const form = document.getElementById('memberForm');
        
        if (!modal || !form) {
            return;
        }
        
        // Reset form
        form.reset();
        
        // Set projectId
        const projectIdField = form.querySelector('input[name="projectId"]');
        if (projectIdField) {
            projectIdField.value = window.projectID;
        }

        // Set title
        const modalTitle = document.getElementById('memberModalLabel');
        if (modalTitle) {
            modalTitle.textContent = "Thêm thành viên";
        }

        // Thiết lập events
        this.setupModalEvents();
        
        // Hiển thị modal
        this.showModal(modal);
    }

    // Hiển thị modal
    showModal(modal) {
        modal = modal || document.getElementById('memberModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    // Ẩn modal
    hideModal() {
        const modal = document.getElementById('memberModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    // Xử lý submit form
    async handleFormSubmit(e, form) {
        e.preventDefault();
        if (!form) return;

        const formData = new FormData(form);

        try {
            const response = await fetch('../projects/AddMemberProcess.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('success', data.message);
                this.hideModal();
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
        if (!memberId) {
            return;
        }

        try {
            const confirmed = await showConfirmDialog(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`);
            
            if (!confirmed) return;

            // Lấy projectID từ hàm helper
            const projectID = this.getProjectID();
            
            if (!projectID || projectID === '0' || projectID === 0) {
                this.showNotification('error', 'Không tìm thấy mã dự án!');
                return;
            }
            
            const formData = new FormData();
            formData.append('projectId', projectID);
            formData.append('projectMemberId', memberId);

            const response = await fetch('../projects/DeleteMemberProcess.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('success', data.message);
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

document.addEventListener('DOMContentLoaded', () => {    window.memberManager = new MemberManager();});

function showConfirmDialog(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmDeleteModal');
        const msg = document.getElementById('confirmDeleteMessage');
        const btnOk = document.getElementById('confirmDeleteBtn');
        const btnCancel = document.getElementById('cancelDeleteBtn');

        if (!modal || !msg || !btnOk || !btnCancel) {
            resolve(false);
            return;
        }

        msg.textContent = message;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        const cleanup = () => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            btnOk.onclick = null;
            btnCancel.onclick = null;
        };

        btnOk.onclick = () => { 
            cleanup(); 
            resolve(true); 
        };
        
        btnCancel.onclick = () => { 
            cleanup(); 
            resolve(false); 
        };
    });
}

