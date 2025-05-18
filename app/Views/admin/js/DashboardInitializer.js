// DashboardInitializer.js - Khởi tạo và quản lý chung cho dashboard admin

/**
 * Khởi tạo các chức năng cho dashboard admin
 */
function initializeDashboard() {
    // Thiết lập các biến toàn cục cần thiết
    window.adminDashboard = {
        lastUpdated: null,
        refreshInterval: 60000, // Mặc định refresh sau 1 phút
        isUpdating: false
    };
    
    // Thêm event listeners cho các sự kiện trang
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    // Khởi tạo các chức năng dashboard
    setLastUpdatedTime();
    initializeRefreshButton();
}

/**
 * Xử lý sự kiện khi tab được kích hoạt hoặc ẩn đi
 */
function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
        // Khi tab được kích hoạt, tự động refresh dữ liệu
        refreshAllStatistics();
    }
}

/**
 * Thiết lập thời gian cập nhật cuối cùng
 */
function setLastUpdatedTime() {
    window.adminDashboard.lastUpdated = new Date();
    
    // Tạo phần tử hiển thị thời gian cập nhật nếu chưa có
    if (!document.getElementById('last-updated')) {
        const timeElement = document.createElement('div');
        timeElement.id = 'last-updated';
        timeElement.className = 'text-xs text-gray-500 mt-6 text-center';
        timeElement.innerHTML = `Cập nhật lần cuối: ${formatDateTime(window.adminDashboard.lastUpdated)}`;
        
        // Thêm vào cuối dashboard
        const mainContent = document.querySelector('main .max-w-7xl');
        if (mainContent) {
            mainContent.appendChild(timeElement);
        }
    } else {
        document.getElementById('last-updated').innerHTML = `Cập nhật lần cuối: ${formatDateTime(window.adminDashboard.lastUpdated)}`;
    }
}

/**
 * Khởi tạo nút refresh cho dashboard
 */
function initializeRefreshButton() {
    // Kiểm tra xem nút đã tồn tại chưa
    if (!document.getElementById('refresh-dashboard')) {
        const refreshButton = document.createElement('button');
        refreshButton.id = 'refresh-dashboard';
        refreshButton.className = 'ml-2 p-2 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-700';
        refreshButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>';
        refreshButton.title = 'Làm mới dữ liệu';
        
        // Thêm nút vào tiêu đề
        const dashboardTitle = document.querySelector('h1.text-2xl');
        if (dashboardTitle) {
            dashboardTitle.style.display = 'flex';
            dashboardTitle.style.alignItems = 'center';
            dashboardTitle.appendChild(refreshButton);
            
            // Thêm event listener
            refreshButton.addEventListener('click', refreshAllStatistics);
        }
    }
}

/**
 * Cập nhật tất cả thống kê
 */
function refreshAllStatistics() {
    if (window.adminDashboard.isUpdating) return;
    
    window.adminDashboard.isUpdating = true;
    
    // Reset UI loading state
    document.querySelectorAll('#userCount, #projectCount, #taskCount').forEach(element => {
        element.innerHTML = '<span class="text-gray-400">Đang tải...</span>';
    });
    
    // Gọi các hàm cập nhật thống kê từ các file khác
    if (typeof fetchUserStats === 'function') fetchUserStats();
    if (typeof fetchProjectStats === 'function') fetchProjectStats();
    if (typeof fetchTaskStats === 'function') fetchTaskStats();
    
    // Cập nhật thời gian làm mới
    setLastUpdatedTime();
    
    window.adminDashboard.isUpdating = false;
}

/**
 * Định dạng ngày tháng giờ
 */
function formatDateTime(date) {
    return date.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

// Khởi tạo dashboard khi trang được load
document.addEventListener('DOMContentLoaded', initializeDashboard); 