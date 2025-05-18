<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0"
    />
    <title>Thêm nhiệm vụ</title>
    <!-- Không cần thêm link CSS ở đây vì đã được include trong file chính -->
  </head>
  <body>
    <!-- Dialog content -->
    <div class="fixed inset-0 bg-black bg-opacity-20 flex items-center justify-center">
      <!-- Modal container -->
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="flex justify-between bg-[#0A1A44] text-white px-6 py-4 rounded-t-2xl">
          <h2 class="text-lg font-semibold">Thêm nhiệm vụ mới</h2>
          <button class="hover:bg-indigo-500 p-1 rounded-full focus:outline-none close-dialog">
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

        <!-- Body -->
        <form class="px-6 pb-6 pt-4 space-y-4" id="taskForm">
          <input type="hidden" name="projectId" value="<?= $projectId ?>">
          <input type="hidden" name="statusName" id="statusField" value="">

          <!-- Tên nhiệm vụ -->
          <div
            class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300"
          >
            <!-- Icon  -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M4 7h16v3H4V7zm2-4h12v2H6V3zM4 10h16v10H4V10z" />
            </svg>
            <input
              type="text"
              name="taskName"
              placeholder="Tên nhiệm vụ"
              class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700 font-medium"
              required
            />
          </div>

          <!-- Tag -->
          <div
            class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300 font-medium"
          >
            <!-- Icon bookmark  -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M6 2h12a2 2 0 012 2v18l-8-5-8 5V4a2 2 0 012-2z" />
            </svg>
            <input
              type="text"
              name="tag"
              placeholder="Tag"
              class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700"
            />
          </div>

          <!-- Ngày hạn định -->
          <div
            class="flex items-center bg-indigo-50 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-indigo-300 font-medium"
          >
            <!-- Icon calendar  -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-6 h-6 text-indigo-500 flex-shrink-0 fill-current"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
            </svg>
            <input
              type="date"
              name="dueDate"
              class="ml-3 w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-700"
              required
            />
          </div>

          <!-- Chọn màu -->
          <div>
            <p class="text-indigo-600 text-lg font-semibold">Chọn màu:</p>
            <div class="flex space-x-3 mt-2">
              <button
                type="button"
                class="w-8 h-8 bg-pink-500 rounded-full border-2 border-transparent focus:outline-none color-btn"
                data-color="pink-500"
              ></button>
              <button
                type="button"
                class="w-8 h-8 bg-red-600 rounded-full border-2 border-transparent focus:outline-none color-btn"
                data-color="red-600"
              ></button>
              <button
                type="button"
                class="w-8 h-8 bg-green-500 rounded-full border-2 border-transparent focus:outline-none color-btn"
                data-color="green-500"
              ></button>
              <button
                type="button"
                class="w-8 h-8 bg-blue-400 rounded-full border-2 border-transparent focus:outline-none color-btn"
                data-color="blue-400"
              ></button>
              <button
                type="button"
                class="w-8 h-8 bg-orange-500 rounded-full border-2 border-transparent focus:outline-none color-btn"
                data-color="orange-500"
              ></button>
              <input type="hidden" name="color" id="colorField" value="blue-400">
            </div>
          </div>

          <!-- Nút Thêm nhiệm vụ -->
          <button
            type="submit"
            class="mt-4 w-full bg-[#0A1A44] hover:bg-blue-800 text-white font-semibold rounded-xl py-3 transition"
          >
            Thêm nhiệm vụ
          </button>
        </form>
      </div>
    </div>

    <script>
      // Thiết lập trạng thái cho form
      function setTaskStatus(status) {
        document.getElementById('statusField').value = status;
      }

      // Xử lý chọn màu
      document.querySelectorAll('.color-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          // Xóa border cho tất cả các nút
          document.querySelectorAll('.color-btn').forEach(b => {
            b.classList.remove('border-gray-800');
            b.classList.add('border-transparent');
          });
          
          // Thêm border cho nút được chọn
          this.classList.remove('border-transparent');
          this.classList.add('border-gray-800');
          
          // Cập nhật giá trị màu
          document.getElementById('colorField').value = this.getAttribute('data-color');
        });
      });

      // Xử lý đóng dialog
      document.querySelectorAll('.close-dialog').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          document.getElementById('createTaskDialog').classList.add('hidden');
        });
      });

      // Xử lý submit form
      document.getElementById('taskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Tạo FormData từ form
        const formData = new FormData(this);
        
        // Gửi ajax request để tạo task mới
        fetch('CreateTask.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Đóng dialog
            document.getElementById('createTaskDialog').classList.add('hidden');
            // Reload trang để cập nhật danh sách
            window.location.reload();
          } else {
            alert('Có lỗi xảy ra: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Đã xảy ra lỗi khi tạo nhiệm vụ');
        });
      });
    </script>
  </body>
</html>