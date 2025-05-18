<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Validate request
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;

if ($id <= 0 || $projectID <= 0) {
    die("Invalid request parameters");
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