// TaskStatisticsManager.js - Quản lý và hiển thị thống kê công việc
function fetchTaskStats() {
    // Sử dụng fetch API để lấy dữ liệu từ endpoint
    fetch('../../../api/admin/FetchTaskStatistics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Cập nhật UI với số lượng công việc
            document.getElementById('taskCount').textContent = data.count;
        })
        .catch(error => {
            // Silent error handling without console.error
            document.getElementById('taskCount').textContent = 'Error';
        });
}

// Gọi hàm khi trang được tải
document.addEventListener('DOMContentLoaded', fetchTaskStats); 