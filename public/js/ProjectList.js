document.addEventListener("DOMContentLoaded", function () {
  fetch("/app/Views/projects/GetProjectsByUser.php")
    .then(res => res.json())
    .then(projects => {
      const grid = document.getElementById("projectGrid");
      // Lấy card chứa nút "Thêm dự án"
      const addBtnCard = document.getElementById("openProjectModalBtnContainer");
      grid.innerHTML = "";
      // Đưa nút "Thêm dự án" lên đầu
      grid.appendChild(addBtnCard);
      projects.forEach(p => {
        const card = document.createElement("div");
        card.className = "project-card rounded-lg shadow overflow-hidden flex flex-col h-64";
        if (p.BackgroundUrl) {
          card.style.backgroundImage = `url('${p.BackgroundUrl}')`;
          card.style.backgroundSize = "cover";
          card.style.backgroundPosition = "center";
          card.style.color = "#0A1A44";
          card.style.position = "relative";
          card.innerHTML = `
            <div class="project-bg h-2/5" style="background-image: url('${
              p.BackgroundUrl
            }'); background-size:cover; background-position:center;"></div>
            <div class="project-info flex-1 bg-white p-4 flex flex-col">
              <div class="flex items-center mb-2">
                <img src="../../../${p.OwnerAvatar}" class="w-8 h-8 rounded-full mr-2 border" />
                <span class="font-semibold">${p.OwnerName}</span>
              </div>
              <h3 class="text-lg font-bold mb-1">${p.ProjectName}</h3>
              <p class="text-gray-600 text-sm mb-2">${p.ProjectDescription || ""}</p>
              <div class="mt-auto">
                <div class="w-full h-2 bg-gray-200 rounded-full mb-2">
                  <div class="h-2 bg-green-500 rounded-full" style="width: ${p.progress}%"></div>
                </div>
                <span class="text-xs text-gray-500">Tiến độ: ${p.progress}%</span>
              </div>
            </div>
          `;
        } else {
          card.innerHTML = ` 
            <div class="project-bg h-2/5" style="background-color:#A8B5D6;"></div>
            <div class="project-info flex-1 bg-white p-4 flex flex-col">
              <div class="flex items-center mb-2">
                <img src="../../../${p.OwnerAvatar}" class="w-8 h-8 rounded-full mr-2 border" />
                <span class="font-semibold">${p.OwnerName}</span>
              </div>
              <h3 class="text-lg font-bold mb-1">${p.ProjectName}</h3>
              <p class="text-gray-600 text-sm mb-2">${p.ProjectDescription || ""}</p>
              <div class="mt-auto">
                <div class="w-full h-2 bg-gray-200 rounded-full mb-2">
                  <div class="h-2 bg-green-500 rounded-full" style="width: ${p.progress}%"></div>
                </div>
                <span class="text-xs text-gray-500">Tiến độ: ${p.progress}%</span>
              </div>
            </div>
          `;
        }
        card.style.cursor = "pointer";
        card.onclick = () =>
          (window.location.href = `/app/Views/dashboard/ProjectDetail.php?id=${p.ProjectID}`);
        grid.appendChild(card);
      });
    });
});
