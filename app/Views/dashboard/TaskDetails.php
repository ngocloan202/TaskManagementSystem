<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Reset edit mode on page load to prevent getting stuck in edit mode after navigation
if (isset($_SESSION['edit_mode'])) {
  unset($_SESSION['edit_mode']);
}

// Check if ActivityLog table exists, create it if it doesn't
try {
  $tableCheckResult = $connect->query("SHOW TABLES LIKE 'ActivityLog'");
  if ($tableCheckResult && $tableCheckResult->num_rows === 0) {
    // Table doesn't exist, create it
    $createTableSQL = "
      CREATE TABLE IF NOT EXISTS ActivityLog (
        ActivityID INT AUTO_INCREMENT PRIMARY KEY,
        UserID INT NOT NULL,
        ActivityType VARCHAR(50) NOT NULL,
        RelatedID INT NOT NULL,
        ActivityTime DATETIME NOT NULL,
        Details TEXT,
        FOREIGN KEY (UserID) REFERENCES Users(UserID)
      )
    ";
    $connect->query($createTableSQL);
    error_log("Created ActivityLog table");
  }
} catch (Exception $e) {
  error_log("Error checking/creating ActivityLog table: " . $e->getMessage());
  // Continue execution even if table check/creation fails
}

$title = "Task Details | CubeFlow";
$currentPage = "projects";

// Initialize variables to avoid undefined variable errors
$task = null;
$assignee = null;
$activities = [];

try {
  // Get task ID and project ID from URL
  $taskId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
  $projectId = isset($_GET["project_id"]) ? intval($_GET["project_id"]) : 0;

  if ($taskId <= 0 || $projectId <= 0) {
    throw new Exception("Invalid task information");
  }

  // Check permission to view task (user must be a project member or admin)
  $userID = $_SESSION['user_id'] ?? 0;
  $isAdmin = $_SESSION['role'] === 'ADMIN';

  if (!$isAdmin) {
    // Check if user is a member of the project
    $memberCheckStmt = $connect->prepare("
      SELECT COUNT(*) as isMember 
      FROM ProjectMembers 
      WHERE ProjectID = ? AND UserID = ?
    ");
    
    if (!$memberCheckStmt) {
      throw new Exception("Query error: " . $connect->error);
    }
    
    $memberCheckStmt->bind_param("ii", $projectId, $userID);
    $memberCheckStmt->execute();
    $isMemberResult = $memberCheckStmt->get_result()->fetch_assoc();
    
    if (!$isMemberResult || $isMemberResult['isMember'] == 0) {
      throw new Exception("You don't have permission to view this task");
    }
  }

  // Get task details
  $taskQuery = $connect->prepare("
    SELECT 
      t.TaskID,
      t.TaskTitle,
      t.TaskDescription,
      t.TagName,
      t.TagColor,
      t.Priority,
      DATE_FORMAT(t.StartDate, '%Y-%m-%d') AS StartDate,
      DATE_FORMAT(t.EndDate, '%Y-%m-%d') AS EndDate,
      ts.StatusName,
      p.ProjectName,
      p.ProjectID
    FROM Task t
    JOIN TaskStatus ts ON t.TaskStatusID = ts.TaskStatusID
    JOIN Project p ON t.ProjectID = p.ProjectID
    WHERE t.TaskID = ? AND t.ProjectID = ?
  ");

  if (!$taskQuery) {
    throw new Exception("Task query error: " . $connect->error);
  }

  $taskQuery->bind_param("ii", $taskId, $projectId);
  $taskQuery->execute();
  $taskResult = $taskQuery->get_result();
  
  if ($taskResult->num_rows === 0) {
    throw new Exception("Task not found");
  }
  
  $task = $taskResult->fetch_assoc();

  // Log task view event
  try {
    // Avoid logging repeatedly if user refreshes the page
    $lastViewKey = "last_view_task_{$userID}_{$taskId}";
    $currentTime = time();
    $lastViewTime = $_SESSION[$lastViewKey] ?? 0;
    
    // Only log if 5 minutes have passed since last view
    if ($currentTime - $lastViewTime > 300) {
      $viewTime = date('Y-m-d H:i:s');
      $details = json_encode([
        'from' => $isAdmin ? 'admin_view' : 'user_view',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'page' => 'task_details',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
      ]);
      
      $logQuery = $connect->prepare("
        INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
        VALUES (?, 'task_viewed', ?, ?, ?)
      ");
      
      if ($logQuery) {
        $logQuery->bind_param('iiss', $userID, $taskId, $viewTime, $details);
        $logQuery->execute();
        
        // Save last view time to session
        $_SESSION[$lastViewKey] = $currentTime;
        
        error_log("Logged task view event: User $userID viewed task $taskId at $viewTime");
      } else {
        error_log("Failed to prepare task view log query: " . $connect->error);
      }
    }
  } catch (Exception $e) {
    error_log("Error logging task view: " . $e->getMessage());
    // Continue execution even if logging fails
  }

  // Get information about assigned users
  try {
    $assigneeSQL = "
      SELECT 
        u.UserID,
        u.FullName,
        u.Avatar
      FROM Users u
      JOIN TaskAssignment ta ON u.UserID = ta.UserID 
      WHERE ta.TaskID = ?
    ";

    
    $assigneeQuery = $connect->prepare($assigneeSQL);

    if (!$assigneeQuery) {
      error_log("SQL Error in TaskDetails.php (assigneeQuery): " . $connect->error . " - SQL: " . $assigneeSQL);
      
      // Try a direct query to check if we can get any user from the table
      $directQuery = "SELECT TaskID, UserID FROM TaskAssignment WHERE TaskID = $taskId LIMIT 1";
      $directResult = $connect->query($directQuery);
      if ($directResult && $directResult->num_rows > 0) {
        $directRow = $directResult->fetch_assoc();
        error_log("Direct query found: TaskID={$directRow['TaskID']}, UserID={$directRow['UserID']}");
        
        // Try to get user info directly
        $userId = $directRow['UserID'];
        $userResult = $connect->query("SELECT UserID, FullName, Avatar FROM Users WHERE UserID = $userId LIMIT 1");
        if ($userResult && $userResult->num_rows > 0) {
          $assignee = $userResult->fetch_assoc();
          error_log("Found user directly: " . $assignee['FullName']);
        }
      } else {
        error_log("No assignment found for task $taskId using direct query: $directQuery");
      }
    } else {
      $assigneeQuery->bind_param("i", $taskId);
      $assigneeQuery->execute();
      
      // Check for execution errors
      if ($assigneeQuery->error) {
        error_log("Execution error for assignee query: " . $assigneeQuery->error);
      } else {
        $assigneeResult = $assigneeQuery->get_result();
        $assignees = [];
        while ($row = $assigneeResult->fetch_assoc()) {
          $assignees[] = $row;
        }
        
        // Log whether we found assignees
        if (count($assignees) > 0) {
          error_log("Found " . count($assignees) . " assignees for task $taskId");
        } else {
          error_log("No assignees found for task $taskId");
        }
      }
    }
  } catch (Exception $e) {
    error_log("Error getting assignee: " . $e->getMessage());
    // Continue execution even if assignee query fails
  }

  // Get recent activities for this task
  try {
    $activitySQL = "
      SELECT 
        a.ActivityTime,
        a.ActivityType,
        a.Details,
        u.FullName,
        u.Avatar
      FROM ActivityLog a
      JOIN Users u ON a.UserID = u.UserID
      WHERE a.RelatedID = ? AND a.ActivityType LIKE 'task%'
      ORDER BY a.ActivityTime DESC
      LIMIT 10
    ";

    $activityQuery = $connect->prepare($activitySQL);

    if (!$activityQuery) {
      error_log("SQL Error in TaskDetails.php (activityQuery): " . $connect->error . " - SQL: " . $activitySQL);
      
      // Check if ActivityLog table exists
      $checkTable = $connect->query("SHOW TABLES LIKE 'ActivityLog'");
      if ($checkTable && $checkTable->num_rows === 0) {
        error_log("Table 'ActivityLog' does not exist in the database");
      } else {
        error_log("ActivityLog table exists but query preparation failed");
      }
    } else {
      $activityQuery->bind_param("i", $taskId);
      $result = $activityQuery->execute();
      
      if (!$result) {
        error_log("Failed to execute activity query: " . $activityQuery->error);
      } else {
        $activityResult = $activityQuery->get_result();
        while ($row = $activityResult->fetch_assoc()) {
          $activities[] = $row;
        }
        error_log("Found " . count($activities) . " activities for task $taskId");
      }
    }
  } catch (Exception $e) {
    error_log("Error getting activities: " . $e->getMessage());
    // Continue execution even if activity query fails
  }
} catch (Exception $e) {
  // Handle exceptions gracefully
  die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="../../../public/css/tailwind.css" />
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
    .custom-textarea {
      width: 100%;
      height: 200px;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1rem;
    }
    /* Edit mode styles */
    .edit-mode {
      border: 2px solid #4f46e5 !important;
      background-color: #f9fafb !important;
    }
    /* Member dropdown styles */
    .member-dropdown {
      position: relative;
      display: inline-block;
    }
    .member-dropdown-content {
      display: none;
      position: absolute;
      background-color: white;
      min-width: 250px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      border-radius: 0.5rem;
      padding: 0.5rem 0;
      z-index: 100;
      right: 0;
      max-height: 300px;
      overflow-y: auto;
    }
    .member-dropdown-content.show {
      display: block;
    }
    .member-item {
      padding: 0.5rem 1rem;
      display: flex;
      align-items: center;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .member-item:hover {
      background-color: #f3f4f6;
    }
    .member-item img {
      width: 32px;
      height: 32px;
      border-radius: 9999px;
      margin-right: 0.75rem;
    }
    .member-item .member-name {
      flex: 1;
    }
    .member-item .member-check {
      width: 20px;
      height: 20px;
      color: #4f46e5;
      opacity: 0;
    }
    .member-item.selected .member-check {
      opacity: 1;
    }
    .member-badge {
      display: inline-flex;
      align-items: center;
      background-color: #e5e7eb;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      transition: background-color 0.2s;
    }
    .member-badge:hover {
      background-color: #d1d5db;
    }
    .member-badge img {
      width: 24px;
      height: 24px;
      border-radius: 9999px;
      margin-right: 0.5rem;
    }
    .member-badge .remove-member {
      margin-left: 0.5rem;
      color: #6b7280;
      cursor: pointer;
    }
    .member-badge .remove-member:hover {
      color: #ef4444;
    }
    .member-search {
      padding: 0.5rem 1rem;
      border-bottom: 1px solid #e5e7eb;
    }
    .member-search input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 0.25rem;
      outline: none;
    }
    .member-search input:focus {
      border-color: #4f46e5;
    }
    .no-members-message {
      padding: 1rem;
      text-align: center;
      color: #6b7280;
    }
  </style>
</head>
<body style="background-color: #f0f2f5;">
  <div class="flex h-screen">
    <!-- Include Sidebar -->
    <?php include_once "../components/Sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Include Header/Topbar -->
      <?php include_once "../components/Header.php"; ?>
      
      <!-- Task Details Content -->
      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <!-- Breadcrumb -->
        <div class="flex items-center mb-6 text-gray-600">
          <a href="ProjectDetail.php?id=<?= $projectId ?>" class="text-indigo-600 font-bold text-xl">Project</a>
          <span class="mx-2">›</span>
          <span class="font-bold text-xl"><?= htmlspecialchars($task['ProjectName']) ?></span>
        </div>

        <!-- Task Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <a href="ProjectDetail.php?id=<?= $projectId ?>" class="text-indigo-600 flex items-center font-medium">
              <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Back
            </a>
            <?php if (!$isAdmin): ?>
            <div class="space-x-2">
              <button id="btnDelete" class="bg-red-600 hover:bg-orange-200 text-white px-4 py-2 rounded-md font-semibold">Delete</button>
              <button id="btnEdit" class="bg-indigo-600 hover:bg-[#2970FF] text-white px-4 py-2 rounded-md font-semibold">Edit</button>
              <button id="btnSave" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-semibold hidden">Save</button>
              <button id="btnCancel" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-semibold hidden">Cancel</button>
            </div>
            <?php endif; ?>
          </div>
          
          <h1 class="text-2xl font-bold"><?= htmlspecialchars($task['TaskTitle']) ?></h1>
          <div class="text-gray-600 mt-2">in list <span id="taskStatus" class="text-indigo-600 font-bold"><?= htmlspecialchars($task['StatusName']) ?></span></div>
          
          <!-- Task Info -->
          <div class="mt-6 grid grid-cols-2 gap-6">
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"></path>
                  </svg>
                  <span>Tag</span>
                </div>
                <?php if (!$isAdmin && isset($_SESSION['edit_mode']) && $_SESSION['edit_mode']): ?>
                <div id="tagContainer" class="flex items-center">
                  <input id="tagName" type="text" value="<?= htmlspecialchars($task['TagName'] ?? '') ?>" placeholder="Tag name" class="mr-2 border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent edit-mode" style="max-width: 120px;">
                  <input id="tagColor" type="color" value="<?= htmlspecialchars($task['TagColor'] ?? '#3B82F6') ?>" class="h-8 w-8 border-0 rounded cursor-pointer edit-mode">
                  <div id="tagPreview" class="ml-2 px-3 py-1 rounded-full text-sm text-white" style="background-color: <?= htmlspecialchars($task['TagColor'] ?? '#3B82F6') ?>">
                    <?= htmlspecialchars($task['TagName'] ?? 'New tag') ?>
                  </div>
                </div>
                <?php else: ?>
                <div id="tagContainer">
                <?php if (!empty($task['TagName'])): ?>
                <span class="px-3 py-1 rounded-full text-sm text-white font-semibold" style="background-color: <?= htmlspecialchars($task['TagColor']) ?>">
                  <?= htmlspecialchars($task['TagName']) ?>
                </span>
                <?php else: ?>
                <span class="text-gray-500">No tag</span>
                <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Priority</span>
                </div>
                <div class="relative">
                  <select id="taskPriority" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
                    <option <?= $task['Priority'] === '' ? 'selected' : '' ?>>Select</option>
                    <option <?= $task['Priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option <?= $task['Priority'] === 'High' ? 'selected' : '' ?>>High</option>
                    <option <?= $task['Priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option <?= $task['Priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Date</span>
                </div>
                <input id="startDate" type="date" value="<?= $task['StartDate'] ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
                <span class="mx-2">-</span>
                <input id="endDate" type="date" value="<?= $task['EndDate'] ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'readonly' : '' ?>>
              </div>
            </div>
            
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Members</span>
                </div>
                
                <?php if (!$isAdmin): ?>
                <div class="member-dropdown">
                  <div id="memberDisplay" class="flex flex-wrap items-center cursor-pointer">
                    <?php if (!empty($assignees)): ?>
                      <?php foreach ($assignees as $member): ?>
                      <div class="member-badge" data-member-id="<?= $member['UserID'] ?>">
                        <img src="../../../<?= htmlspecialchars($member['Avatar']) ?>" alt="<?= htmlspecialchars($member['FullName']) ?>">
                        <span><?= htmlspecialchars($member['FullName']) ?></span>
                        <span class="remove-member">×</span>
                      </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                    <button id="addMemberBtn" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                      <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      Add Member
                    </button>
                    <?php endif; ?>
                    
                    <!-- Always show "Add More" button when we have members -->
                    <?php if (!empty($assignees)): ?>
                    <button id="addMoreMembersBtn" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium ml-2">
                      <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      Add More
                    </button>
                    <?php endif; ?>
                  </div>
                  
                  <div id="memberDropdown" class="member-dropdown-content">
                    <div class="member-search">
                      <input type="text" id="memberSearchInput" placeholder="Search members...">
                    </div>
                    <div id="memberList" class="member-list">
                      <div class="no-members-message">Loading member list...</div>
                    </div>
                  </div>
                </div>
                <?php else: ?>
                <div class="flex flex-wrap items-center">
                  <?php if (!empty($assignees)): ?>
                    <?php foreach ($assignees as $member): ?>
                      <div class="flex items-center mr-3 mb-2">
                        <img src="../../../<?= htmlspecialchars($member['Avatar']) ?>" alt="<?= htmlspecialchars($member['FullName']) ?>" class="w-8 h-8 rounded-full">
                        <span class="ml-2"><?= htmlspecialchars($member['FullName']) ?></span>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                  <span class="text-gray-500">Not assigned to anyone</span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Status Update Buttons (always visible for non-admin) -->
          <?php if (!$isAdmin): ?>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <h3 class="text-lg font-medium mb-2">Update Status</h3>
            <div class="flex space-x-2">
              <button id="btnMarkTodo" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                To Do
              </button>
              <button id="btnMarkInProgress" class="px-3 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                In Progress
              </button>
              <button id="btnMarkCompleted" class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                Completed
              </button>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Task Description -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 class="text-xl font-bold mb-4">Task Description</h2>
          <div id="taskDescription" class="custom-textarea p-4 bg-gray-50 rounded-lg" <?= $isAdmin || !isset($_SESSION['edit_mode']) ? 'contenteditable="false"' : 'contenteditable="true"' ?>>
            <?= nl2br(htmlspecialchars($task['TaskDescription'] ?? 'No description')) ?>
          </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-bold mb-4">Recent Activity</h2>
          <div id="activityList" class="space-y-4">
            <?php if (count($activities) > 0): ?>
              <?php foreach ($activities as $activity): ?>
                <div class="flex items-start">
                  <img src="../../../<?= htmlspecialchars($activity['Avatar']) ?>" alt="User" class="w-8 h-8 rounded-full mr-3">
                  <div>
                    <p class="font-medium"><?= htmlspecialchars($activity['FullName']) ?></p>
                    <p class="text-gray-700">
                      <?php
                      $details = json_decode($activity['Details'], true);
                      $activityTypeMap = [
                        'task_created' => 'created task',
                        'task_updated' => 'updated task',
                        'task_assigned' => 'assigned task to',
                        'task_unassigned' => 'unassigned task from',
                        'task_status_changed' => 'changed task status',
                        'task_detail_viewed' => 'viewed task',
                        'task_priority_changed' => 'changed task priority',
                        'task_date_changed' => 'changed task date'
                      ];
                      echo $activityTypeMap[$activity['ActivityType']] ?? $activity['ActivityType'];
                      ?>
                    </p>
                    <p class="text-gray-500 text-sm">
                      <?= date('H:i - d/m/Y', strtotime($activity['ActivityTime'])) ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="flex items-start">
                <div>
                  <p class="text-gray-500 text-sm">No recent activity</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <!-- Success notification element -->
  <div id="notification" class="fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 hidden">
    <div class="flex items-center">
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <span id="notificationMessage">Updated successfully!</span>
    </div>
  </div>

  <!-- Data Transfer Script - Pass PHP data to JavaScript -->
  <script>
    // Task data
    window.TASK_DATA = {
      taskId: <?= $taskId ?? 'null' ?>,
      projectId: <?= $projectId ?? 'null' ?>,
      tagName: <?= json_encode($task['TagName'] ?? '') ?>,
      tagColor: <?= json_encode($task['TagColor'] ?? '#3B82F6') ?>,
      priority: <?= json_encode($task['Priority'] ?? '') ?>,
      startDate: <?= json_encode($task['StartDate'] ?? '') ?>,
      endDate: <?= json_encode($task['EndDate'] ?? '') ?>,
      description: <?= json_encode($task['TaskDescription'] ?? '') ?>,
      statusName: <?= json_encode($task['StatusName'] ?? '') ?>,
      isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
      currentUser: {
        id: <?= $_SESSION['user_id'] ?? 'null' ?>,
        name: <?= json_encode($_SESSION['full_name'] ?? 'User') ?>,
        avatar: <?= json_encode($_SESSION['avatar'] ?? 'public/uploads/default-avatar.png') ?>
      }
    };
  </script>
  
  <!-- Include JavaScript files -->
  <script src="../../../public/js/modules/taskEditMode.js"></script>
  <script src="../../../public/js/modules/taskStatus.js"></script>
  <script src="../../../public/js/modules/taskNotification.js"></script>
  <script src="../../../public/js/modules/taskActivityLogger.js"></script>
  <script src="../../../public/js/modules/taskMemberAssignment.js"></script>
  <script src="../../../public/js/TaskDetails.js"></script>
</body>
</html>