<?php
// File: app/Views/projects/GetMemberByID.php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Set headers
header("Content-Type: application/json");

// Validate request parameters
$memberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($memberId <= 0) {
    echo json_encode(['error' => 'Invalid member ID']);
    exit;
}

// Fetch member data
$stmt = $connect->prepare("
    SELECT pm.ProjectMembersID, pm.UserID, pm.RoleInProject, pm.JoinedAt,
           p.ProjectID, p.ProjectName, u.FullName, u.Username, u.Avatar
    FROM ProjectMembers pm 
    JOIN Users u ON pm.UserID = u.UserID
    JOIN Project p ON pm.ProjectID = p.ProjectID
    WHERE pm.ProjectMembersID = ?
");

$stmt->bind_param('i', $memberId);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    echo json_encode(['error' => 'Member not found']);
    exit;
}

// Return member data
echo json_encode($member);
?> 