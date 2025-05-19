<?php
// Redirect to standardized endpoint
$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$projectID = isset($_GET["projectID"]) ? (int) $_GET["projectID"] : 0;

header("Location: DeleteMemberProcess.php?id=$id&projectID=$projectID");
exit();
?> 