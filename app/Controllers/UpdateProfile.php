<?php
require_once __DIR__.'/../../config/SessionInit.php';
require_once __DIR__.'/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error: Must use POST';
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo 'error: Not logged in';
    exit;
}
if (empty($_POST['email']) || empty($_POST['phone'])) {
    echo 'error: Missing data';
    exit;
}

$userId = $_SESSION['user_id'];
$email  = $_POST['email'];
$phone  = $_POST['phone'];
$avatar = $_POST['avatar'] ?? $_SESSION['avatar'];

$stmt = $connect->prepare(
   "UPDATE Users SET Email=?, PhoneNumber=?, Avatar=? WHERE UserID=?"
);
$stmt->bind_param('sssi',$email,$phone,$avatar,$userId);
if ($stmt->execute()) {
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['avatar'] = $avatar;
    echo 'success';
} else {
    echo 'error: '.$stmt->error;
}
