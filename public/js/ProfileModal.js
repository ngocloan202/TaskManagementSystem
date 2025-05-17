document.addEventListener('DOMContentLoaded', function () {
    const profileModal = document.getElementById('profileModal');
    const saveBtn = document.getElementById('btnProfileSave');
    const editBtn = document.getElementById('btnProfileEdit');
    const inputs = document.querySelectorAll('#profileForm input:not([type="hidden"])');
    const closeBtn = document.getElementById('closeProfileModal');

    function closeModal() {
        profileModal.classList.add('hidden');
        saveBtn.classList.add('hidden');
        editBtn.classList.remove('hidden');
        inputs.forEach(input => input.disabled = true);
    }

    // Open profile modal
    document.getElementById('openProfile').addEventListener('click', function (e) {
        e.preventDefault();
        profileModal.classList.remove('hidden');
        saveBtn.classList.add('hidden');
        editBtn.classList.remove('hidden');
        inputs.forEach(input => input.disabled = true);
    });

    // Handle edit button click
    editBtn.addEventListener('click', function () {
        saveBtn.classList.remove('hidden');
        editBtn.classList.add('hidden');
        inputs.forEach(input => input.disabled = false);
    });

    // Close button handler
    closeBtn.addEventListener('click', closeModal);

    // Outside click handler
    profileModal.addEventListener('click', function (e) {
        if (e.target === profileModal) {
            closeModal();
        }
    });
});