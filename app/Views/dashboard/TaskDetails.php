<?php
  $title = "Chi tiết dự án";
  $currentPage = "projects";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dự án</title>
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
    </style>
</head>
<body style="background-color: #f0f2f5;">
    <div class="flex h-screen">
      <?php include_once('../components/Sidebar.php'); ?>

      <div class="flex-1 flex flex-col overflow-hidden">
      <?php include_once('../components/Header.php'); ?>
      </div>
    </div>
</body>
</html>