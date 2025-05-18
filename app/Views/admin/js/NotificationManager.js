// NotificationManager.js - Quản lý hiển thị thông báo trong dashboard

/**
 * Quản lý hiển thị các thông báo trong dashboard
 */
class NotificationManager {
    constructor() {
        this.container = null;
        this.notificationCount = 0;
        this.initContainer();
    }
    
    /**
     * Khởi tạo container cho thông báo
     */
    initContainer() {
        // Kiểm tra nếu container đã tồn tại
        if (document.getElementById('notification-container')) {
            this.container = document.getElementById('notification-container');
            return;
        }
        
        // Tạo container mới
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'fixed top-4 right-4 z-50 flex flex-col space-y-2';
        document.body.appendChild(this.container);
    }
    
    /**
     * Hiển thị thông báo thành công
     * @param {string} message - Nội dung thông báo
     * @param {number} duration - Thời gian hiển thị (ms)
     */
    showSuccess(message, duration = 3000) {
        this.showNotification(message, 'success', duration);
    }
    
    /**
     * Hiển thị thông báo lỗi
     * @param {string} message - Nội dung thông báo
     * @param {number} duration - Thời gian hiển thị (ms)
     */
    showError(message, duration = 5000) {
        this.showNotification(message, 'error', duration);
    }
    
    /**
     * Hiển thị thông báo cảnh báo
     * @param {string} message - Nội dung thông báo
     * @param {number} duration - Thời gian hiển thị (ms)
     */
    showWarning(message, duration = 4000) {
        this.showNotification(message, 'warning', duration);
    }
    
    /**
     * Hiển thị thông báo thông tin
     * @param {string} message - Nội dung thông báo
     * @param {number} duration - Thời gian hiển thị (ms)
     */
    showInfo(message, duration = 3000) {
        this.showNotification(message, 'info', duration);
    }
    
    /**
     * Tạo và hiển thị thông báo
     * @param {string} message - Nội dung thông báo
     * @param {string} type - Loại thông báo (success, error, warning, info)
     * @param {number} duration - Thời gian hiển thị (ms)
     */
    showNotification(message, type, duration) {
        const id = `notification-${Date.now()}-${this.notificationCount++}`;
        
        // Xác định class dựa vào loại thông báo
        let bgColor, textColor, icon;
        switch (type) {
            case 'success':
                bgColor = 'bg-green-100';
                textColor = 'text-green-800';
                icon = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                break;
            case 'error':
                bgColor = 'bg-red-100';
                textColor = 'text-red-800';
                icon = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                break;
            case 'warning':
                bgColor = 'bg-yellow-100';
                textColor = 'text-yellow-800';
                icon = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                break;
            case 'info':
            default:
                bgColor = 'bg-blue-100';
                textColor = 'text-blue-800';
                icon = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
        }
        
        // Tạo element thông báo
        const notification = document.createElement('div');
        notification.id = id;
        notification.className = `${bgColor} ${textColor} px-4 py-3 rounded-md shadow-md flex items-center transition-all duration-300 transform translate-x-full opacity-0`;
        notification.innerHTML = `
            ${icon}
            <div class="flex-grow">${message}</div>
            <button class="ml-4 text-gray-500 hover:text-gray-800" onclick="document.getElementById('${id}').remove()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        `;
        
        // Thêm vào container
        this.container.appendChild(notification);
        
        // Hiệu ứng hiển thị
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);
        
        // Tự động ẩn sau thời gian duration
        setTimeout(() => {
            if (document.getElementById(id)) {
                notification.style.transform = 'translateX(full)';
                notification.style.opacity = '0';
                
                // Xóa element sau khi ẩn
                setTimeout(() => {
                    if (document.getElementById(id)) {
                        document.getElementById(id).remove();
                    }
                }, 300);
            }
        }, duration);
    }
    
    /**
     * Khởi tạo việc hiển thị thông báo từ session flash
     */
    initSessionFlash() {
        // Kiểm tra xem có thông báo flash từ PHP không
        const successFlash = document.getElementById('flash-success');
        const errorFlash = document.getElementById('flash-error');
        
        if (successFlash && successFlash.textContent.trim()) {
            this.showSuccess(successFlash.textContent);
            successFlash.remove();
        }
        
        if (errorFlash && errorFlash.textContent.trim()) {
            this.showError(errorFlash.textContent);
            errorFlash.remove();
        }
    }
}

// Khởi tạo và xuất instance của NotificationManager để sử dụng toàn cục
window.notificationManager = new NotificationManager();

// Khởi tạo xử lý session flash khi trang được tải
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager.initSessionFlash();
}); 