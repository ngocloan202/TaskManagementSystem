document.addEventListener("DOMContentLoaded", function() {
  fetch('/app/Views/projects/GetProjectsByUser.php')
    .then(res => res.json())
    .then(projects => {
      console.log(projects);
      const grid = document.getElementById('projectGrid');
      const addBtn = grid.lastElementChild;
      grid.innerHTML = '';
      projects.forEach(p => {
        const card = document.createElement('div');
        card.className = "bg-white rounded-lg shadow flex flex-col h-64 p-4";
        card.innerHTML = `
          <div class="flex-1">
            <div class="flex items-center mb-2">
              <img src="../../../${p.OwnerAvatar}" class="w-8 h-8 rounded-full mr-2 border" />
              <span class="font-semibold">${p.OwnerName}</span>
            </div>
            <h3 class="text-lg font-bold mb-1">${p.ProjectName}</h3>
            <p class="text-gray-600 text-sm mb-2">${p.ProjectDescription || ''}</p>
          </div>
          <div class="mt-auto">
            <div class="w-full h-2 bg-gray-200 rounded-full mb-2">
              <div class="h-2 bg-green-500 rounded-full" style="width: ${p.progress}%"></div>
            </div>
            <span class="text-xs text-gray-500">Tiến độ: ${p.progress}%</span>
          </div>
        `;
        card.style.cursor = "pointer";
        card.onclick = () => window.location.href = `/app/Views/dashboard/ProjectDetail.php?id=${p.ProjectID}`;
        grid.appendChild(card);
      });
      grid.appendChild(addBtn);
    });
});
