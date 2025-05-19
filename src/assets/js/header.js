/**
 * Przekierowuje do podanej ścieżki z opóźnieniem
 * @param {string} path - Ścieżka do przekierowania
 * @returns {void}
 */
export function redirectTo(path) {
  document.body.style.cursor = 'wait';

  setTimeout(() => {
    try {
      const basePath = window.location.pathname.split('/').slice(0, -1).join('/');
      const cleanPath = path.startsWith('/') ? path.substring(1) : path;
      window.location.href = `${basePath}/${cleanPath}`;
    }
    catch (error) {
      console.error('Błąd przekierowania:', error);
      alert('Przepraszamy, wystąpił błąd podczas przekierowania.');
      document.body.style.cursor = 'default';
    }
  }, 300);
}

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('.search-input');
  const searchButton = document.querySelector('.search-button');

  const trayItems = {
    cart: document.querySelector('.tray-item[aria-label="Koszyk"]'),
    profile: document.querySelector('.tray-item[aria-label="Profil użytkownika"]'),
    home: document.querySelector('.tray-item[aria-label="Strona główna"]'),
    pannel: document.querySelector('.tray-item[aria-label="Panel admina"]'),
  };

  if (trayItems.cart) {
    trayItems.cart.addEventListener('click', () => redirectTo('/cart.php'));
  }

  if (trayItems.profile) {
    trayItems.profile.addEventListener('click', () => redirectTo('/profile.php'));
  }

  if (trayItems.home) {
    trayItems.home.addEventListener('click', () => redirectTo('/home.php'));
  }

  if (trayItems.pannel) {
    trayItems.pannel.addEventListener('click', () => redirectTo('/panel.php'));
  }

  /**
   * Obsługuje wyszukiwanie produktów
   * @returns {void}
   */
  function handleSearch() {
    const searchTerm = searchInput?.value.trim();
    if (searchTerm) {
      redirectTo(`/katalog.php?search=${encodeURIComponent(searchTerm)}`);
    }
  }

  if (searchButton && searchInput) {
    searchButton.addEventListener('click', (e) => {
      if (e.target.closest('form')?.method !== 'get') {
        e.preventDefault();
        handleSearch();
      }
    });
    
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && e.target.closest('form')?.method !== 'get') {
        e.preventDefault();
        handleSearch();
      }
    });
  }
});