// UserStatisticsManager.js - Quản lý và hiển thị thống kê người dùng
function fetchUserStats() {
    // Sử dụng fetch API để lấy dữ liệu từ endpoint
    fetch('../../../api/admin/FetchUserStatistics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Cập nhật UI với số lượng người dùng
            document.getElementById('userCount').textContent = data.count;
        })
        .catch(error => {
            console.error('Error fetching user stats:', error);
            document.getElementById('userCount').textContent = 'Error';
        });
}

// Gọi hàm khi trang được tải
document.addEventListener('DOMContentLoaded', fetchUserStats); 