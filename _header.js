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

      const fullPath = `${basePath}/${cleanPath}`;

      window.location.href = fullPath;
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

  /**
   * Obsługuje wyszukiwanie produktów
   * @returns {void}
   */
  function handleSearch() {
    const searchTerm = searchInput?.value.trim().toLowerCase();
    if (searchTerm) {
      console.log('Wyszukiwanie:', searchTerm);
    }
  }

  if (searchButton && searchInput) {
    searchButton.addEventListener('click', handleSearch);
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleSearch();
      }
    });
  }
});