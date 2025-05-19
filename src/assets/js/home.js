import { redirectTo } from '../js/header.js';

document.addEventListener('DOMContentLoaded', () => {
  // Obsługa przewijania dla typów instrumentów
  const instrumentTypesList = document.querySelector('.instrument-types-list');
  const instrumentTypesScrollButtons = document.querySelectorAll('.instrument-types .scroll-button');
  const instrumentTypesScrollLeftButton = instrumentTypesScrollButtons[0];
  const instrumentTypesScrollRightButton = instrumentTypesScrollButtons[1];

  // Obsługa przewijania dla producentów
  const instrumentBrandsList = document.querySelector('.instrument-brands-list');
  const instrumentBrandsScrollButtons = document.querySelectorAll('.instrument-brands .scroll-button');
  const instrumentBrandsScrollLeftButton = instrumentBrandsScrollButtons[0];
  const instrumentBrandsScrollRightButton = instrumentBrandsScrollButtons[1];

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

  /** @const {number} Ilość pikseli do przewinięcia */
  const scrollAmount = 300;

  /**
   * Inicjalizuje obsługę przewijania dla danej listy
   * @param {HTMLElement} list - Lista do przewijania
   * @param {HTMLElement} leftButton - Przycisk przewijania w lewo
   * @param {HTMLElement} rightButton - Przycisk przewijania w prawo
   */
  function initializeScrolling(list, leftButton, rightButton) {
    if (!list || !leftButton || !rightButton) return;

    leftButton.addEventListener('click', () => {
      list.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth',
      });
    });

    rightButton.addEventListener('click', () => {
      list.scrollBy({
        left: scrollAmount,
        behavior: 'smooth',
      });
    });

    function updateScrollButtons() {
      const isAtStart = list.scrollLeft === 0;
      const isAtEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth;

      leftButton.style.opacity = isAtStart ? '0.5' : '1';
      rightButton.style.opacity = isAtEnd ? '0.5' : '1';
    }

    list.addEventListener('scroll', updateScrollButtons);
    window.addEventListener('resize', updateScrollButtons);
    updateScrollButtons();
  }

  // Inicjalizacja przewijania dla obu list
  initializeScrolling(instrumentTypesList, instrumentTypesScrollLeftButton, instrumentTypesScrollRightButton);
  initializeScrolling(instrumentBrandsList, instrumentBrandsScrollLeftButton, instrumentBrandsScrollRightButton);

  /**
   * Konwertuje tekst na format URL
   * @param {string} text - Tekst do przekonwertowania
   * @returns {string} Tekst w formacie URL
   */
  function convertToUrlFormat(text) {
    return text.toLowerCase().replace(/\s+/g, '-');
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
});