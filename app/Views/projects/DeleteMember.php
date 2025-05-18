<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Validate request
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;
$currentUserID = $_SESSION["user_id"] ?? 0;

if ($id <= 0 || $projectID <= 0) {
    die("Invalid request parameters");
}

// Check if the current user is a project owner (only owners can delete members)
$ownerCheckStmt = $connect->prepare("
    SELECT COUNT(*) AS isOwner 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
");
$ownerCheckStmt->bind_param('ii', $projectID, $currentUserID);
$ownerCheckStmt->execute();
$ownerResult = $ownerCheckStmt->get_result()->fetch_assoc();

if ($ownerResult['isOwner'] == 0) {
    die("Permission denied: Only project owners can delete members");
}

// Check if trying to delete yourself (owners shouldn't delete themselves)
$targetUserStmt = $connect->prepare("
    SELECT UserID 
    FROM ProjectMembers 
    WHERE ProjectMembersID = ?
");
$targetUserStmt->bind_param('i', $id);
$targetUserStmt->execute();
$targetUser = $targetUserStmt->get_result()->fetch_assoc();

if ($targetUser['UserID'] == $currentUserID) {
    die("You cannot remove yourself from the project");
}

// Check if the member exists and belongs to the specified project
$checkStmt = $connect->prepare("
    SELECT COUNT(*) AS count 
    FROM ProjectMembers 
    WHERE ProjectMembersID = ? AND ProjectID = ?
");
$checkStmt->bind_param('ii', $id, $projectID);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();

if ($result['count'] == 0) {
    die("Member not found or does not belong to the specified project");
}

// Delete the member
$deleteStmt = $connect->prepare("DELETE FROM ProjectMembers WHERE ProjectMembersID = ?");
$deleteStmt->bind_param('i', $id);
$deleteStmt->execute();

// Redirect back to the referrer or members page
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "DialogManageMembers.php?projectID={$projectID}";
header("Location: {$referrer}");
exit; 