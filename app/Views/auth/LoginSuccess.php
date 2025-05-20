<?php
session_start();
$message = $_SESSION["success"] ?? "Login successful!";
unset($_SESSION["success"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="2;url=../dashboard/index.php"> <!-- Redirect after 2s -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Successful</title>
  <link rel="stylesheet" href="../../../public/css/tailwind.css">
</head>
<body class="flex items-center justify-center min-h-screen bg-[#EEF0FF]">
  <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded shadow-md text-center">
    <p class="font-semibold text-lg"><?= htmlspecialchars($message) ?></p>
    <p class="mt-1 text-sm text-gray-700">Redirecting to homepage...</p>
  </div>
</body>
</html>
