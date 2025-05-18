// ThemeManager.js - Quản lý giao diện và chủ đề dashboard

/**
 * Quản lý giao diện và chủ đề của dashboard
 */
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('dashboard-theme') || 'light';
        this.initTheme();
        
        // Xóa nút theme-toggle nếu đã tồn tại
        this.removeThemeToggleButton();
    }
    
    /**
     * Khởi tạo giao diện theo chủ đề đã lưu
     */
    initTheme() {
        // Chỉ áp dụng theme, không tạo nút toggle
        this.applyTheme(this.currentTheme);
    }
    
    /**
     * Xóa nút theme-toggle nếu đã tồn tại
     */
    removeThemeToggleButton() {
        const themeToggleButton = document.getElementById('theme-toggle');
        if (themeToggleButton) {
            themeToggleButton.remove();
        }
    }
    
    /**
     * Chuyển đổi giữa các chủ đề
     * @param {string} theme - Chủ đề muốn chuyển đổi ('light' hoặc 'dark')
     */
    toggleTheme(theme) {
        this.currentTheme = theme;
        localStorage.setItem('dashboard-theme', theme);
        this.applyTheme(theme);
    }
    
    /**
     * Áp dụng các class và style cho chủ đề
     * @param {string} theme - Chủ đề muốn áp dụng ('light' hoặc 'dark')
     */
    applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.classList.add('dark-theme');
            
            // Áp dụng các class dark mode
            document.body.classList.remove('bg-gray-100');
            document.body.classList.add('bg-gray-900', 'text-white');
            
            // Cập nhật các card
            document.querySelectorAll('.bg-white').forEach(el => {
                el.classList.remove('bg-white');
                el.classList.add('bg-gray-800');
            });
            
            // Cập nhật text color
            document.querySelectorAll('.text-gray-900').forEach(el => {
                el.classList.remove('text-gray-900');
                el.classList.add('text-gray-100');
            });
            
            document.querySelectorAll('.text-gray-700').forEach(el => {
                el.classList.remove('text-gray-700');
                el.classList.add('text-gray-300');
            });
            
            // Cập nhật các thẻ quản lý
            document.querySelectorAll('.management-card').forEach(el => {
                el.classList.remove('bg-blue-50');
                el.classList.add('bg-blue-900');
                
                // Cập nhật text trong thẻ
                const title = el.querySelector('h3');
                if (title) {
                    title.classList.remove('text-blue-700');
                    title.classList.add('text-blue-300');
                }
                
                const description = el.querySelector('p');
                if (description) {
                    description.classList.remove('text-gray-600');
                    description.classList.add('text-gray-400');
                }
            });
        } else {
            html.classList.remove('dark-theme');
            
            // Áp dụng các class light mode
            document.body.classList.add('bg-gray-100');
            document.body.classList.remove('bg-gray-900', 'text-white');
            
            // Cập nhật các card
            document.querySelectorAll('.bg-gray-800').forEach(el => {
                el.classList.add('bg-white');
                el.classList.remove('bg-gray-800');
            });
            
            // Cập nhật text color
            document.querySelectorAll('.text-gray-100').forEach(el => {
                el.classList.add('text-gray-900');
                el.classList.remove('text-gray-100');
            });
            
            document.querySelectorAll('.text-gray-300').forEach(el => {
                el.classList.add('text-gray-700');
                el.classList.remove('text-gray-300');
            });
            
            // Cập nhật các thẻ quản lý
            document.querySelectorAll('.management-card').forEach(el => {
                el.classList.add('bg-blue-50');
                el.classList.remove('bg-blue-900');
                
                // Cập nhật text trong thẻ
                const title = el.querySelector('h3');
                if (title) {
                    title.classList.add('text-blue-700');
                    title.classList.remove('text-blue-300');
                }
                
                const description = el.querySelector('p');
                if (description) {
                    description.classList.add('text-gray-600');
                    description.classList.remove('text-gray-400');
                }
            });
        }
    }
}

// Khởi tạo ThemeManager khi trang được tải
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
}); 