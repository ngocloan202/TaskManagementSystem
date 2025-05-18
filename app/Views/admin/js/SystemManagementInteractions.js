// SystemManagementInteractions.js - Quản lý tương tác người dùng với các thẻ quản lý hệ thống

// Thêm hiệu ứng khi hover vào các thẻ quản lý
function initManagementCardInteractions() {
    const managementCards = document.querySelectorAll('.management-card');
    
    managementCards.forEach(card => {
        // Thêm hiệu ứng shadow khi hover
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-md');
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-md');
            this.style.transform = 'translateY(0)';
        });
        
        // Thêm hiệu ứng khi click
        card.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(0)';
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-2px)';
        });
    });
}

// Gọi hàm khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    initManagementCardInteractions();
}); 