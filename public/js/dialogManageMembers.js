
class MemberManager {
    constructor() {
        window.projectID = this.getProjectID();
        
        // Set up event listeners for add member button
        this.setupAddButtonListener();
        window.deleteMember = (memberId, userName) => {
            this.deleteMember(memberId, userName);
        };
        
        // Initialize modal events if modal is already visible
        const modal = document.getElementById('memberModal');
        if (modal && !modal.classList.contains('hidden')) {
            this.setupModalEvents();
        }
    }
    
    getProjectID() {
        const projectIdInput = document.querySelector('#memberForm input[name="projectId"]');
        if (projectIdInput && projectIdInput.value) {
            return projectIdInput.value;
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const projectID = urlParams.get('projectID') || urlParams.get('id');
        if (projectID) {
            return projectID;
        }
        
        const body = document.body;
        if (body.dataset.projectId) {
            return body.dataset.projectId;
        }
        
        return 0;
    }

    setupAddButtonListener() {
        const addMemberBtn = document.getElementById('btnAddMember');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', () => this.showAddMemberModal());
        }
    }
    
    // Set up modal event handlers
    setupModalEvents() {
        const modal = document.getElementById('memberModal');
        const form = document.getElementById('memberForm');
        
        const closeBtn = document.getElementById('closeMemberModal');
        if (closeBtn) {
            closeBtn.onclick = null;
            
            closeBtn.addEventListener('click', () => {
                this.hideModal();
            });
        }

        const cancelBtn = document.getElementById('cancelMemberModal');
        if (cancelBtn) {
            // Remove old event handlers if any
            cancelBtn.onclick = null;
            
            // Add new event handler
            cancelBtn.addEventListener('click', () => {
                this.hideModal();
            });
        }
        
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.hideModal();
                }
            };
        }
        
        if (form) {
            form.onsubmit = (e) => this.handleFormSubmit(e, form);
        }
    }
    
    showAddMemberModal() {
        const modal = document.getElementById('memberModal');
        const form = document.getElementById('memberForm');
        
        if (!modal || !form) {
            return;
        }
        
        form.reset();
        
        const projectIdField = form.querySelector('input[name="projectId"]');
        if (projectIdField) {
            projectIdField.value = window.projectID;
        }

        // Set title
        const modalTitle = document.getElementById('memberModalLabel');
        if (modalTitle) {
            modalTitle.textContent = "Thêm thành viên";
        }

        this.setupModalEvents();
        
        this.showModal(modal);
    }

    showModal(modal) {
        modal = modal || document.getElementById('memberModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    hideModal() {
        const modal = document.getElementById('memberModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

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

    async deleteMember(memberId, userName) {
        if (!memberId) {
            return;
        }

        try {
            const confirmed = await showConfirmDialog(`Bạn có chắc chắn muốn xóa thành viên ${userName || ''} khỏi dự án này?`);
            
            if (!confirmed) return;

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

