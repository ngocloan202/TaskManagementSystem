<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Check admin role
check_role("ADMIN");

$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);
$currentPage = "admin_dashboard";

// Determine current tab
$activeTab = $_GET['tab'] ?? 'overview';

// Get user list if on users tab
$users = [];
if ($activeTab == 'users') {
    $usersQuery = "SELECT * FROM Users ORDER BY UserID DESC LIMIT 10";
    $usersResult = $connect->query($usersQuery);
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Get project list if on projects tab
$projects = [];
if ($activeTab == 'projects') {
    $projectsQuery = "SELECT p.*, u.Username, u.FullName, 
                     (SELECT COUNT(*) FROM ProjectMembers WHERE ProjectID = p.ProjectID) as MemberCount,
                     (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID) as TaskCount,
                     (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) as CompletedTaskCount
                     FROM Project p
                     LEFT JOIN Users u ON p.CreatedBy = u.UserID
                     ORDER BY p.ProjectID DESC
                     LIMIT 10";
    $projectsResult = $connect->query($projectsQuery);
    if ($projectsResult) {
        while ($row = $projectsResult->fetch_assoc()) {
            $projects[] = $row;
        }
    }
}

// Count total projects, users and tasks
$totalProjectsQuery = "SELECT COUNT(*) as total FROM Project";
$totalUsersQuery = "SELECT COUNT(*) as total FROM Users";
$totalTasksQuery = "SELECT COUNT(*) as total FROM Task";

$totalProjectsResult = $connect->query($totalProjectsQuery);
$totalUsersResult = $connect->query($totalUsersQuery);
$totalTasksResult = $connect->query($totalTasksQuery);

$totalProjects = $totalProjectsResult ? $totalProjectsResult->fetch_assoc()['total'] : 0;
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['total'] : 0;
$totalTasks = $totalTasksResult ? $totalTasksResult->fetch_assoc()['total'] : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <style>
        .menuItem {
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .menuItem:last-child {
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-value {
            height: 100%;
            background-color: #3b82f6;
            border-radius: 4px;
        }
        
        .tab-active {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Flash messages from session -->
    <?php if ($flashSuccess): ?>
        <div id="flash-success" class="hidden"><?= $flashSuccess ?></div>
    <?php endif; ?>
    
    <?php if ($flashError): ?>
        <div id="flash-error" class="hidden"><?= $flashError ?></div>
    <?php endif; ?>
    
    <div class="flex h-screen">
        <?php include "../components/Sidebar.php"; ?>
        
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-auto">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Admin Dashboard</h1>
                    
                    <!-- Admin Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Total Users -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Total Users</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalUsers) ?>
                            </p>
                        </div>
                        
                        <!-- Total Projects -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Total Projects</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalProjects) ?>
                            </p>
                        </div>
                        
                        <!-- Total Tasks -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Total Tasks</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalTasks) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="p-6">
                            <!-- System Management -->
                            <h2 class="text-xl font-semibold mb-6">System Management</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- User Management -->
                                <a href="Users.php" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                    <h3 class="text-lg font-medium text-blue-700 mb-1">User Management</h3>
                                    <p class="text-sm text-gray-600">View and manage all users in the system</p>
                                </a>
                                
                                <!-- Project Management -->
                                <a href="Projects.php" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                    <h3 class="text-lg font-medium text-blue-700 mb-1">Project Management</h3>
                                    <p class="text-sm text-gray-600">View and manage all projects in the system</p>
                                </a>
                                
                                <!-- System Settings -->
                                <a href="settings.php" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                    <h3 class="text-lg font-medium text-blue-700 mb-1">System Settings</h3>
                                    <p class="text-sm text-gray-600">Configure and manage system settings</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hover effect for management cards
            const cards = document.querySelectorAll('.management-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.classList.add('bg-blue-100');
                });
                card.addEventListener('mouseleave', () => {
                    card.classList.remove('bg-blue-100');
                });
            });
            
            // Show flash messages
            const flashSuccess = document.getElementById('flash-success');
            const flashError = document.getElementById('flash-error');
            
            if (flashSuccess && flashSuccess.textContent.trim() !== '') {
                showNotification(flashSuccess.textContent, 'success');
            }
            
            if (flashError && flashError.textContent.trim() !== '') {
                showNotification(flashError.textContent, 'error');
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 
                    'bg-red-100 border-l-4 border-red-500 text-red-700'
                }`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'translate-x-full');
                    notification.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 5000);
            }
        });
        
        // Confirm delete user
        function confirmDelete(userId, username) {
            if (confirm(`Are you sure you want to delete user "${username}"?`)) {
                window.location.href = `Users.php?action=delete&id=${userId}`;
            }
        }
        
        // Confirm delete project
        function confirmDeleteProject(projectId, projectName) {
            if (confirm(`Are you sure you want to delete project "${projectName}"?`)) {
                window.location.href = `Projects.php?action=delete&id=${projectId}`;
            }
        }
    </script>
</body>
</html> 