import { redirectTo } from '../js/header.js';

document.addEventListener('DOMContentLoaded', () => {
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

  /** @const {number} Ilość pikseli do przewinięcia */
  const scrollAmount = 300;

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

  /**
   * Konwertuje tekst na format URL
   * @param {string} text - Tekst do przekonwertowania
   * @returns {string} Tekst w formacie URL
   */
  function convertToUrlFormat(text) {
    return text.toLowerCase().replace(/\s+/g, '-');
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
});