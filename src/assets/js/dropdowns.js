// Funkcja do przełączania widoczności dropdownu
function toggleDropdown(dropdownId) {
  const dropdown = document.getElementById(dropdownId);
  const isVisible = dropdown.classList.contains('show');
  
  // Zamykanie wszystkich dropdownów
  const allDropdowns = document.querySelectorAll('.dropdown-menu');
  allDropdowns.forEach(d => {
    d.classList.remove('show');
  });
  
  // Jeśli dropdown nie był widoczny, pokaż go
  if (!isVisible) {
    dropdown.classList.add('show');
  }
}

// Obsługa kliknięcia poza dropdownem
document.addEventListener('click', function(event) {
  const dropdowns = document.querySelectorAll('.dropdown-menu');
  const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
  
  let clickedOnDropdown = false;
  
  // Sprawdź czy kliknięto na dropdown lub jego zawartość
  dropdowns.forEach(dropdown => {
    if (dropdown.contains(event.target)) {
      clickedOnDropdown = true;
    }
  });
  
  // Sprawdź czy kliknięto na przycisk dropdown
  dropdownToggles.forEach(toggle => {
    if (toggle.contains(event.target)) {
      clickedOnDropdown = true;
    }
  });
  
  // Jeśli nie kliknięto na dropdown ani jego przycisk, zamknij wszystkie dropdowny
  if (!clickedOnDropdown) {
    dropdowns.forEach(dropdown => {
      dropdown.classList.remove('show');
    });
  }
}); 