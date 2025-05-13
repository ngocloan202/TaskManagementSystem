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
      href="dashboard.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === 'dashboard' ? 'bg-indigo-50 text-gray-800' : 'hover:bg-indigo-50 text-gray-800' ?>"
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
      href="projects.php"
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === 'projects' ? 'bg-indigo-50 text-gray-800' : 'hover:bg-indigo-50 text-gray-800' ?>"
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
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === 'activities' ? 'bg-indigo-50 text-gray-800' : 'hover:bg-indigo-50 text-gray-800' ?>"
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
      class="flex items-center w-full px-4 py-3 rounded-lg <?= $currentPage === 'schedule' ? 'bg-indigo-50 text-gray-800' : 'hover:bg-indigo-50 text-gray-800' ?>"
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
  <!-- Settings -->
  <nav class="mt-auto mb-6 w-full">
    <a 
      href="settings.php" 
      class="menuItem hover:bg-[#E8E9FF] space-x-3 w-full <?= $currentPage === 'settings' ? 'bg-[#E8E9FF]' : '' ?>"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        aria-hidden="true"
        data-slot="icon"
        class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0"
      >
        <path
          fill-rule="evenodd"
          d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 0 1-.517.608 7.45 7.45 0 0 0-.478.198.798.798 0 0 1-.796-.064l-.453-.324a1.875 1.875 0 0 0-2.416.2l-.243.243a1.875 1.875 0 0 0-.2 2.416l.324.453a.798.798 0 0 1 .064.796 7.448 7.448 0 0 0-.198.478.798.798 0 0 1-.608.517l-.55.092a1.875 1.875 0 0 0-1.566 1.849v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 0 1-.064.796l-.324.453a1.875 1.875 0 0 0 .2 2.416l.243.243c.648.648 1.67.733 2.416.2l.453-.324a.798.798 0 0 1 .796-.064c.157.071.316.137.478.198.267.1.47.327.517.608l.092.55c.15.903.932 1.566 1.849 1.566h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 0 1 .517-.608 7.52 7.52 0 0 0 .478-.198.798.798 0 0 1 .796.064l.453.324a1.875 1.875 0 0 0 2.416-.2l.243-.243c.648-.648.733-1.67.2-2.416l-.324-.453a.798.798 0 0 1-.064-.796c.071-.157.137-.316.198-.478.1-.267.327-.47.608-.517l.55-.091a1.875 1.875 0 0 0 1.566-1.85v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 0 1-.608-.517 7.507 7.507 0 0 0-.198-.478.798.798 0 0 1 .064-.796l.324-.453a1.875 1.875 0 0 0-.2-2.416l-.243-.243a1.875 1.875 0 0 0-2.416-.2l-.453.324a.798.798 0 0 1-.796.064 7.462 7.462 0 0 0-.478-.198.798.798 0 0 1-.517-.608l-.091-.55a1.875 1.875 0 0 0-1.85-1.566h-.344ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"
          clip-rule="evenodd"
        ></path>
      </svg>
      <span>Cài đặt</span>
    </a>
  </nav>
</aside> 