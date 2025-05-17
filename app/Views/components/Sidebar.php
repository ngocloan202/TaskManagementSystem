<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/Database.php";

$userId = $_SESSION["user_id"] ?? null;
$projects = [];
if ($userId) {
  // Fetch projects this user is a member of
  $statement = $connect->prepare(
    "SELECT p.ProjectID, p.ProjectName
         FROM ProjectMembers pm, Project p
         WHERE pm.ProjectID = p.ProjectID and pm.UserID = ?"
  );
  $statement->bind_param("i", $userId);
  $statement->execute();
  $result = $statement->get_result();
  while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
  }
  $statement->close();
}
?>

<!-- Sidebar -->
<aside class="bg-white w-64 border-r text-black font-semibold flex flex-col h-screen overflow-y-auto">
  <!-- Branding -->
  <div class="px-6 py-2 flex items-center space-x-3 bg-[#0A1A44] text-white h-14">
    <div class="w-10 h-10 rounded-full overflow-hidden">
      <img
        src="../../../public/images/cubeflow-logo.png"
        alt="CubeFlow Logo"
        class="w-full h-full object-cover"
      />
    </div>
    <span class="text-xl font-semibold">CubeFlow</span>
  </div>
  <!-- Navigation Menu -->
  <nav class="flex-1 mt-6 px-2 space-y-2">
    <a
      href="HomePage.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === "homepage"
        ? "bg-indigo-50 text-gray-800"
        : "hover:bg-indigo-200 text-gray-800" ?>"
    >
      <!-- Home Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 mr-3 flex-shrink-0" style="color: #0A1A44;"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"
        />
        <path
          d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"
        />
      </svg>
      <span>Tổng quan</span>
    </a>

    <!-- Collapsible "Dự án" -->
    <div>
      <button id="projectToggle" class="flex items-center w-full px-4 py-3 rounded-lg hover:bg-indigo-200 text-gray-800 focus:outline-none">
        <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 mr-3 flex-shrink-0" style="color: #0A1A44;"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          d="M11.644 1.59a.75.75 0 0 1 .712 0l9.75 5.25a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.712 0l-9.75-5.25a.75.75 0 0 1 0-1.32l9.75-5.25Z"
        />
        <path
          d="m3.265 10.602 7.668 4.129a2.25 2.25 0 0 0 2.134 0l7.668-4.13 1.37.739a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.71 0l-9.75-5.25a.75.75 0 0 1 0-1.32l1.37-.738Z"
        />
        <path
          d="m10.933 19.231-7.668-4.13-1.37.739a.75.75 0 0 0 0 1.32l9.75 5.25c.221.12.489.12.71 0l9.75-5.25a.75.75 0 0 0 0-1.32l-1.37-.738-7.668 4.13a2.25 2.25 0 0 1-2.134-.001Z"
        />
      </svg>
      <span>Dự án</span>
        <svg id="projectArrow" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3 ml-3 text-indigo-600 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <ul id="projectList" class="ml-6 mt-1 space-y-1 hidden">
        <?php foreach ($projects as $p): ?>
          <li>
            <a href="../dashboard/ProjectDetail.php?id=<?= $p["ProjectID"] ?>"
               class="flex items-center px-4 py-2 rounded hover:bg-[#FFE2D2] text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg"
               viewBox="0 0 24 24"
               fill="currentColor"
               class="w-5 h-5 flex-shrink-0 mr-2"
               aria-hidden="true"
               data-slot="icon"
               style="color: #F15A29">
            <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h15ZM1.5 10.146V6a3 3 0 0 1 3-3h5.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 0 1 3 3v1.146A4.483 4.483 0 0 0 19.5 9h-15a4.483 4.483 0 0 0-3 1.146Z"/>
          </svg>
              <span class="project-name flex-1 min-w-0"><?= htmlspecialchars($p["ProjectName"]) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?>
          <li class="px-4 py-2 text-gray-500">Chưa có dự án</li>
        <?php endif; ?>
      </ul>
    </div>

    <a
      href="activities.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === "activities"
        ? "bg-indigo-50 text-gray-800"
        : "hover:bg-indigo-200 text-gray-800" ?>"
    >
      <!-- Activity Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 mr-3 flex-shrink-0" style="color: #0A1A44;"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
        />
      </svg>
      <span>Hoạt động</span>
    </a>
    <a
      href="schedule.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === "schedule"
        ? "bg-indigo-50 text-gray-800"
        : "hover:bg-indigo-200 text-gray-800" ?>"
    >
      <!-- Schedule Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 mr-3 flex-shrink-0" style="color: #0A1A44;"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z"
        />
      </svg>
      <span>Lịch trình</span>
    </a>
  </nav>
  <!-- Help -->
  <nav class="mt-auto mb-6 w-full">
    <a 
      href="help.php" 
      class="menuItem hover:bg-[#A8B5D6] space-x-3 w-full <?= $currentPage === "help"
        ? "bg-[#A8B5D6]"
        : "" ?>"
    >
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="w-6 h-6 mr-3 flex-shrink-0" style="color: #0A1A44;">
  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"></path>
</svg>
      <span>Trợ giúp</span>
    </a>
  </nav>
</aside> 

<script>
  document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('projectToggle');
    const list = document.getElementById('projectList');
    const arrow = document.getElementById('projectArrow');
    btn.addEventListener('click', function(){
      list.classList.toggle('hidden');
      arrow.classList.toggle('rotate-180');
    });
  });
</script>

<style>
.project-name {
  word-break: break-word;
  white-space: normal;
  display: block;
}
</style>
