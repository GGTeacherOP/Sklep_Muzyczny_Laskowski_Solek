document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('.search-input');
  const searchButton = document.querySelector('.search-button');
  const instrumentTypesList = document.querySelector('.instrument-types-list');
  const scrollButtons = document.querySelectorAll('.scroll-button');
  const scrollLeftButton = scrollButtons[0];
  const scrollRightButton = scrollButtons[1];
  const instrumentCards = document.querySelectorAll('.instrument-card');
  const viewAllButton = document.querySelector('.view-all-button');

  /**
   * Usuwa domyślne zaznaczenie kategorii
   * @returns {void}
   */
  function clearDefaultSelection() {
    const selectedCard = document.querySelector('.instrument-card.selected');
    if (selectedCard) {
      selectedCard.classList.remove('selected');
    }
  }

  clearDefaultSelection();

  window.addEventListener('popstate', clearDefaultSelection);
  window.addEventListener('pageshow', clearDefaultSelection);

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

  /** @const {number} Ilość pikseli do przewinięcia */
  const scrollAmount = 300;

  /**
   * Przekierowuje do podanej ścieżki z opóźnieniem
   * @param {string} path - Ścieżka do przekierowania
   * @returns {void}
   */
  function redirectTo(path) {
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

  if (scrollLeftButton && instrumentTypesList) {
    scrollLeftButton.addEventListener('click', () => {
      instrumentTypesList.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth',
      });
    });
  }

  if (scrollRightButton && instrumentTypesList) {
    scrollRightButton.addEventListener('click', () => {
      instrumentTypesList.scrollBy({
        left: scrollAmount,
        behavior: 'smooth',
      });
    });
  }

  const trayItems = {
    notifications: document.querySelector('.tray-item[aria-label="Powiadomienia"]'),
    cart: document.querySelector('.tray-item[aria-label="Koszyk"]'),
    profile: document.querySelector('.tray-item[aria-label="Profil użytkownika"]'),
  };

  /**
   * Konwertuje tekst na format URL
   * @param {string} text - Tekst do przekonwertowania
   * @returns {string} Tekst w formacie URL
   */
  function convertToUrlFormat(text) {
    return text.toLowerCase().replace(/\s+/g, '-');
  }

  if (instrumentCards.length > 0) {
    instrumentCards.forEach(card => {
      card.addEventListener('click', () => {
        document.querySelector('.instrument-card.selected')?.classList.remove('selected');
        card.classList.add('selected');
        const categoryName = card.querySelector('.instrument-name')?.textContent;
        if (categoryName) {
          redirectTo(`/produkty/${convertToUrlFormat(categoryName)}`);
        }
      });
    });
  }

  if (trayItems.notifications) {
    trayItems.notifications.addEventListener('click', () => redirectTo('/powiadomienia.html'));
  }

  if (trayItems.cart) {
    trayItems.cart.addEventListener('click', () => redirectTo('/koszyk.html'));
  }

  if (trayItems.profile) {
    trayItems.profile.addEventListener('click', () => redirectTo('/profile.html'));
  }

  /**
   * Aktualizuje stan przycisków przewijania
   * @returns {void}
   */
  function updateScrollButtons() {
    if (!instrumentTypesList || !scrollLeftButton || !scrollRightButton) {
      return;
    }

    const isAtStart = instrumentTypesList.scrollLeft === 0;
    const isAtEnd = instrumentTypesList.scrollLeft + instrumentTypesList.clientWidth >= instrumentTypesList.scrollWidth;

    scrollLeftButton.style.opacity = isAtStart ? '0.5' : '1';
    scrollRightButton.style.opacity = isAtEnd ? '0.5' : '1';
  }

  if (instrumentTypesList) {
    instrumentTypesList.addEventListener('scroll', updateScrollButtons);
    window.addEventListener('resize', updateScrollButtons);
    updateScrollButtons();
  }

  document.querySelectorAll('.more-info-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const productCard = btn.closest('.product-card');
      const productName = productCard?.querySelector('.product-name')?.textContent;
      if (productName) {
        redirectTo(`/produkt/${convertToUrlFormat(productName)}`);
      }
    });
  });

  if (viewAllButton) {
    viewAllButton.addEventListener('click', () => redirectTo('/produkty/wszystkie'));
  }
});