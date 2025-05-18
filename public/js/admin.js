// Biến toàn cục để lưu trữ ID của timeout và interval
let deleteModalTimeout;
let countdownInterval;
let successAlertTimeout;
let errorAlertTimeout;
let successCountdownInterval;
let errorCountdownInterval;

// Khởi tạo các chức năng khi DOM đã tải xong
document.addEventListener('DOMContentLoaded', function() {
    // Hiệu ứng hover cho các thẻ quản lý
    const cards = document.querySelectorAll('.management-card');
    if (cards.length > 0) {
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('bg-blue-100');
            });
            card.addEventListener('mouseleave', () => {
                card.classList.remove('bg-blue-100');
            });
        });
    }
    
    // Xử lý modal xác nhận xóa
    setupDeleteConfirmation();
    
    // Xử lý thông báo thành công và lỗi
    setupNotifications();
    
    // Xử lý sắp xếp bảng
    setupTableSorting();
    
    // Xử lý modal thêm người dùng
    setupUserModals();
});

// Thiết lập các modal cho quản lý người dùng
function setupUserModals() {
    const addBtn = document.getElementById('addUserBtn');
    const addModal = document.getElementById('addUserModal');
    const closeAddModal = document.getElementById('closeAddModal');
    const cancelAdd = document.getElementById('cancelAdd');
    
    // Xử lý mở modal thêm người dùng
    if (addBtn && addModal) {
        addBtn.addEventListener('click', function() {
            addModal.classList.remove('hidden');
        });
    }
    
    // Xử lý đóng modal thêm người dùng
    if (closeAddModal && addModal) {
        closeAddModal.addEventListener('click', function() {
            addModal.classList.add('hidden');
        });
    }
    
    if (cancelAdd && addModal) {
        cancelAdd.addEventListener('click', function() {
            addModal.classList.add('hidden');
        });
    }
}

// Thiết lập các chức năng xác nhận xóa
function setupDeleteConfirmation() {
    const deleteModal = document.getElementById('deleteConfirmModal');
    const cancelDelete = document.getElementById('cancelDelete');
    const confirmDelete = document.getElementById('confirmDelete');
    
    if (!deleteModal) return;
    
    // Đăng ký sự kiện cho nút Hủy
    if (cancelDelete) {
        cancelDelete.addEventListener('click', function() {
            clearTimeout(deleteModalTimeout);
            clearInterval(countdownInterval);
            deleteModal.classList.add('hidden');
        });
    }
    
    // Đăng ký sự kiện cho modal khi rê chuột vào
    deleteModal.addEventListener('mouseenter', function() {
        clearTimeout(deleteModalTimeout);
        clearInterval(countdownInterval);
    });
    
    // Đăng ký sự kiện cho nút Xóa
    if (confirmDelete) {
        confirmDelete.addEventListener('click', function() {
            clearTimeout(deleteModalTimeout);
            clearInterval(countdownInterval);
        });
    }
}

// Hiển thị modal xác nhận xóa dự án
function showDeleteConfirm(projectId, projectName) {
    const modal = document.getElementById('deleteConfirmModal');
    const confirmText = document.getElementById('deleteConfirmText');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    if (!modal || !confirmText || !confirmDeleteBtn) return;
    
    // Xóa timeout cũ nếu có
    if (deleteModalTimeout) {
        clearTimeout(deleteModalTimeout);
    }
    
    // Xóa interval cũ nếu có
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Cập nhật nội dung modal
    confirmText.textContent = `Bạn có chắc chắn muốn xóa dự án "${projectName}" không?`;
    confirmDeleteBtn.href = `Projects.php?action=delete&id=${projectId}`;
    
    // Hiển thị modal
    modal.classList.remove('hidden');
}

// Hiển thị modal xác nhận xóa người dùng
function confirmDelete(userId, username) {
    if (confirm(`Bạn có chắc chắn muốn xóa người dùng "${username}" không?`)) {
        window.location.href = `Users.php?action=delete&id=${userId}`;
    }
}

// Hiển thị modal xác nhận xóa dự án
function confirmDeleteProject(projectId, projectName) {
    if (confirm(`Bạn có chắc chắn muốn xóa dự án "${projectName}" không?`)) {
        window.location.href = `Projects.php?action=delete&id=${projectId}`;
    }
}

// Thiết lập các thông báo
function setupNotifications() {
    // Xử lý thông báo thành công tự động đóng
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        handleCountdown(successAlert, 'successCountdown', 'successAlertTimeout', 'successCountdownInterval');
    }
    
    // Xử lý thông báo lỗi tự động đóng
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        handleCountdown(errorAlert, 'errorCountdown', 'errorAlertTimeout', 'errorCountdownInterval');
    }
}

// Xử lý đếm ngược thông báo
function handleCountdown(alertElement, countdownId, timeoutVar, intervalVar) {
    const countdownElement = document.getElementById(countdownId);
    if (!countdownElement) return;
    
    let secondsLeft = 3;
    
    // Thiết lập interval để đếm ngược
    window[intervalVar] = setInterval(function() {
        secondsLeft -= 1;
        countdownElement.textContent = secondsLeft;
        
        if (secondsLeft <= 0) {
            clearInterval(window[intervalVar]);
        }
    }, 1000);
    
    // Thiết lập timeout để tự động ẩn thông báo sau 3 giây
    window[timeoutVar] = setTimeout(function() {
        alertElement.style.display = 'none';
        clearInterval(window[intervalVar]);
    }, 3000);
    
    // Tạm dừng đếm ngược khi di chuột vào thông báo
    alertElement.addEventListener('mouseenter', function() {
        clearTimeout(window[timeoutVar]);
        clearInterval(window[intervalVar]);
        countdownElement.textContent = "dừng";
    });
    
    // Tiếp tục đếm ngược khi di chuột ra khỏi thông báo
    alertElement.addEventListener('mouseleave', function() {
        if (countdownElement.textContent === "dừng") {
            secondsLeft = 3;
            countdownElement.textContent = secondsLeft;
            
            window[intervalVar] = setInterval(function() {
                secondsLeft -= 1;
                countdownElement.textContent = secondsLeft;
                
                if (secondsLeft <= 0) {
                    clearInterval(window[intervalVar]);
                }
            }, 1000);
            
            window[timeoutVar] = setTimeout(function() {
                alertElement.style.display = 'none';
                clearInterval(window[intervalVar]);
            }, 3000);
        }
    });
}

// Thiết lập sắp xếp bảng
function setupTableSorting() {
    // Check for project table
    let table = document.getElementById('projectTable');
    if (!table) {
        // If project table not found, try user table
        table = document.getElementById('userTable');
        if (!table) return;
    }
    
    const headers = table.querySelectorAll('th.sortable');
    if (headers.length === 0) return;
    
    // Biến lưu trạng thái sắp xếp
    let currentSort = {
        column: 'id',
        direction: table.id === 'userTable' ? 'asc' : 'desc'
    };
    
    // Lấy trạng thái sắp xếp từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const sortColumn = urlParams.get('sort') || 'id';
    const sortDirection = urlParams.get('order') || (table.id === 'userTable' ? 'asc' : 'desc');
    
    currentSort.column = sortColumn;
    currentSort.direction = sortDirection;
    
    // Đánh dấu cột đang sắp xếp
    const sortHeader = document.querySelector(`th[data-sort="${sortColumn}"]`);
    if (sortHeader) {
        sortHeader.classList.add(sortDirection);
    }
    
    // Thêm sự kiện click cho các tiêu đề cột
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            
            // Xác định hướng sắp xếp
            const direction = 
                column === currentSort.column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            
            // Cập nhật giao diện
            headers.forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            this.classList.add(direction);
            
            // Cập nhật trạng thái sắp xếp
            currentSort.column = column;
            currentSort.direction = direction;
            
            // Chuyển đến URL có tham số sắp xếp
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', column);
            urlParams.set('order', direction);
            
            // Giữ lại tham số tìm kiếm và phân trang nếu có
            window.location.search = urlParams.toString();
        });
    });
} 