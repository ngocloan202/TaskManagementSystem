<?php
// Redirect to new standardized filename
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header("Location: GetMemberByID.php?id=$id");
exit;
?> 