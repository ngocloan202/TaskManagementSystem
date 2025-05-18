<link rel="stylesheet" href="../../../public/css/customColorButton.css">

  <!-- Dialog content -->
  <div class="fixed inset-0 bg-opacity-50 flex items-center justify-center z-50" 
       style="background-color: rgba(0, 0, 0, 0.4);">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
      <!-- Header -->
      <div class="flex justify-between items-center bg-[#0A1A44] text-white px-6 py-4 rounded-t-2xl">
        <h3 class="text-lg font-semibold">Thêm nhiệm vụ mới</h3>
        <button class="hover:bg-indigo-500 p-1 rounded-full focus:outline-none close-dialog transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <form class="px-6 pb-6 pt-4 space-y-4" id="taskForm">
        <input type="hidden" name="projectId" value="<?= $projectId ?>">
        <input type="hidden" name="statusName" id="statusField" value="">

        <!-- Tên nhiệm vụ -->
        <div
          class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300">
          <!-- Icon  -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
            viewBox="0 0 24 24" fill="currentColor">
            <path d="M4 7h16v3H4V7zm2-4h12v2H6V3zM4 10h16v10H4V10z" />
          </svg>
          <input type="text" name="taskName" placeholder="Tên nhiệm vụ"
            class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700 font-medium"
            required />
        </div>

        <!-- Tag -->
        <div
          class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300 font-medium">
          <!-- Icon bookmark  -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
            viewBox="0 0 24 24" fill="currentColor">
            <path d="M6 2h12a2 2 0 012 2v18l-8-5-8 5V4a2 2 0 012-2z" />
          </svg>
          <input type="text" name="tag" placeholder="Tag"
            class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700" />
        </div>

        <!-- Chọn màu -->
        <div>
          <p class="text-indigo-600 text-lg font-semibold">Chọn màu:</p>
          <div class="flex space-x-3 mt-2">
            <button type="button"
              class="color-btn w-8 h-8 rounded-full" style="background-color: #F472B6"
              data-color="#F472B6"></button>
            <button type="button"
              class="color-btn w-8 h-8 rounded-full" style="background-color: #F87171"
              data-color="#F87171"></button>
            <button type="button"
              class="color-btn w-8 h-8 rounded-full" style="background-color: #34D399"
              data-color="#34D399"></button>
            <button type="button"
              class="color-btn w-8 h-8 rounded-full" style="background-color: #60A5FA"
              data-color="#60A5FA"></button>
            <button type="button"
              class="color-btn w-8 h-8 rounded-full" style="background-color: #FBBF24"
              data-color="#FBBF24"></button>
            <input type="hidden" name="color" id="colorField" value="#60A5FA">
          </div>
        </div>
        <script src="../../../public/js/TaskFormHandler.js"></script>


        <!-- Nút Thêm nhiệm vụ -->
        <button type="submit"
          class="mt-4 w-full bg-[#0A1A44] hover:bg-blue-800 text-white font-semibold rounded-xl py-3 transition">
          Tạo
        </button>
      </form>
    </div>
  </div>