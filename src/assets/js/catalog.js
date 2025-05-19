document.addEventListener('DOMContentLoaded', () => {
    // Obsługa formularza filtrów
    const filtersForm = document.getElementById('filters-form');
    const priceInputs = filtersForm.querySelectorAll('input[type="number"]');
    
    // Automatyczne wysyłanie formularza przy zmianie sortowania
    const sortSelect = filtersForm.querySelector('select[name="sort_by"]');
    sortSelect.addEventListener('change', () => {
        filtersForm.submit();
    });
    
    // Walidacja zakresu cen
    priceInputs.forEach(input => {
        input.addEventListener('input', () => {
            const minPrice = parseFloat(priceInputs[0].value) || 0;
            const maxPrice = parseFloat(priceInputs[1].value) || Infinity;
            
            if (maxPrice < minPrice) {
                priceInputs[1].setCustomValidity('Maksymalna cena musi być większa od minimalnej');
            } else {
                priceInputs[1].setCustomValidity('');
            }
        });
    });
    
    // Obsługa resetowania filtrów
    const resetButton = document.querySelector('.reset-filters-btn');
    resetButton.addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = 'katalog.php';
    });
    
    // Animacja produktów przy załadowaniu
    const productsGrid = document.querySelector('.products-grid');
    productsGrid.style.opacity = '0';
    
    setTimeout(() => {
        productsGrid.style.opacity = '1';
    }, 100);
}); 