 // Simple toggle logic
 document.addEventListener('DOMContentLoaded', function() {
    const volunteerTable = document.getElementById('volunteerTable');
    const reliefTable = document.getElementById('reliefTable');
    const toggleBtn = document.getElementById('toggleBtn');
    const tableTitle = document.getElementById('tableTitle');
    const toggleIcon = document.getElementById('toggleIcon');
    let showVolunteers = true;


    toggleBtn.addEventListener('click', function() {
      showVolunteers = !showVolunteers;
      if (showVolunteers) {
        volunteerTable.style.display = '';
        reliefTable.style.display = 'none';
        tableTitle.textContent = 'Volunteer Registrations';
        tableTitle.style.color='var(--sikh-navy)';
        toggleIcon.className = 'bi bi-arrow-repeat me-1';
      } else {
        volunteerTable.style.display = 'none';
        reliefTable.style.display = '';
        tableTitle.textContent = 'Relief Requests';
        tableTitle.style.color='var(--sikh-navy)';
        toggleIcon.className = 'bi bi-arrow-repeat me-1';
      }
    });
  });