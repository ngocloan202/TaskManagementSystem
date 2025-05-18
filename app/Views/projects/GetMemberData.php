<?php
// File: app/Views/projects/get_member_data.php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Validate request
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// Fetch member data
$stmt = $connect->prepare("
    SELECT pm.ProjectMembersID, pm.UserID, pm.RoleInProject, pm.JoinedAt,
           p.ProjectID, u.FullName
    FROM ProjectMembers pm 
    JOIN Users u ON pm.UserID = u.UserID
    JOIN Project p ON pm.ProjectID = p.ProjectID
    WHERE pm.ProjectMembersID = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Member not found']);
    exit;
}

// Return JSON data
header('Content-Type: application/json');
echo json_encode($member); 