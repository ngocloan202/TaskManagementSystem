<?php
/**
 * Sidebar Component
 * 
 * @param string $currentPage Trang hiện tại để highlight menu tương ứng
 */
?>

<!-- Sidebar -->
<aside class="bg-white w-64 border-r text-black font-semibold flex flex-col">
  <!-- Branding -->
  <div class="px-6 py-2 flex items-center space-x-3 bg-[#3C40C6] text-white h-14">
    <div class="w-10 h-10 rounded-full overflow-hidden">
      <img
        src="../../images/cubeflow-logo.png"
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
        : "hover:bg-indigo-50 text-gray-800" ?>"
    >
      <!-- Home Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0"
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
    <a
      href="ProjectDetail.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === "projects"
        ? "bg-indigo-50 text-gray-800"
        : "hover:bg-indigo-50 text-gray-800" ?>"
    >
      <!-- Projects Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0"
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
    </a>
    <a
      href="activities.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === "activities"
        ? "bg-indigo-50 text-gray-800"
        : "hover:bg-indigo-50 text-gray-800" ?>"
    >
      <!-- Activity Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0"
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
        : "hover:bg-indigo-50 text-gray-800" ?>"
    >
      <!-- Schedule Icon -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0"
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
      class="menuItem hover:bg-[#E8E9FF] space-x-3 w-full <?= $currentPage === "help"
        ? "bg-[#E8E9FF]"
        : "" ?>"
    >
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0">
  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 0 1-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 0 1-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 0 1-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584ZM12 18a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"></path>
</svg>
      <span>Trợ giúp</span>
    </a>
  </nav>
</aside> 
