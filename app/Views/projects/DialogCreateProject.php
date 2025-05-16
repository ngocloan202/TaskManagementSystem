
    <!-- Dialog Thêm dự án -->
<div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
  <!-- Header -->
  <div class="flex items-center justify-between bg-[#0A1A44] text-white px-6 py-4 rounded-t-2xl">
    <h3 class="text-lg font-medium">Thêm dự án</h3>
    <button id="closeProjectModalBtn" class="hover:bg-indigo-500 p-1 rounded-full focus:outline-none">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
  </div>

  <!-- Body / Form -->
  <form action="/CreateProjectProcess.php" method="POST" class="px-6 pb-6 pt-4 space-y-4">
    <!-- Tên dự án -->
    <div class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
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
      <input
        type="text"
        name="projectName"
        placeholder="Tên đề án"
        class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700"
      />
    </div>

    <!-- Mô tả dự án -->
    <div class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M7.491 5.992a.75.75 0 0 1 .75-.75h12a.75.75 0 1 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM7.49 11.995a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM7.491 17.994a.75.75 0 0 1 .75-.75h12a.75.75 0 1 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM2.24 3.745a.75.75 0 0 1 .75-.75h1.125a.75.75 0 0 1 .75.75v3h.375a.75.75 0 0 1 0 1.5H2.99a.75.75 0 0 1 0-1.5h.375v-2.25H2.99a.75.75 0 0 1-.75-.75ZM2.79 10.602a.75.75 0 0 1 0-1.06 1.875 1.875 0 1 1 2.652 2.651l-.55.55h.35a.75.75 0 0 1 0 1.5h-2.16a.75.75 0 0 1-.53-1.281l1.83-1.83a.375.375 0 0 0-.53-.53.75.75 0 0 1-1.062 0ZM2.24 15.745a.75.75 0 0 1 .75-.75h1.125a1.875 1.875 0 0 1 1.501 2.999 1.875 1.875 0 0 1-1.501 3H2.99a.75.75 0 0 1 0-1.501h1.125a.375.375 0 0 0 .036-.748H3.74a.75.75 0 0 1-.75-.75v-.002a.75.75 0 0 1 .75-.75h.411a.375.375 0 0 0-.036-.748H2.99a.75.75 0 0 1-.75-.75Z"
        />
      </svg>

      <input
        type="text"
        name="description"
        placeholder="Mô tả dự án"
        class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700"
      />
    </div>

    <!-- Link ảnh dự án -->
    <div class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z"
        />
      </svg>

      <input
        type="url"
        name="image"
        placeholder="Link ảnh dự án"
        class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700"
      />
    </div>

    <!-- Nút Thêm dự án -->
    <button
      type="submit"
      class="mt-2 w-full bg-[#0A1A44] hover:bg-indigo-400 text-white font-semibold rounded-xl py-3 transition"
    >
      Thêm dự án
    </button>
  </form>
</div>

