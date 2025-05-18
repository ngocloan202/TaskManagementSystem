// ProjectStatisticsManager.js - Quản lý và hiển thị thống kê dự án
function fetchProjectStats() {
    // Sử dụng fetch API để lấy dữ liệu từ endpoint
    fetch('../../../api/admin/FetchProjectStatistics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Cập nhật UI với số lượng dự án
            document.getElementById('projectCount').textContent = data.count;
        })
        .catch(error => {
            console.error('Error fetching project stats:', error);
            document.getElementById('projectCount').textContent = 'Error';
        });
}

// Gọi hàm khi trang được tải
document.addEventListener('DOMContentLoaded', fetchProjectStats); 