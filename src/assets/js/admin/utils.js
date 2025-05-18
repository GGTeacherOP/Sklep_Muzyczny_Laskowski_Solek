/**
 * Funkcje pomocnicze dla panelu administracyjnego
 */

/**
 * Pokazuje modal o podanym ID
 * 
 * @param {string} modalId - ID modalu do pokazania
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

/**
 * Zamyka modal o podanym ID
 * 
 * @param {string} modalId - ID modalu do zamknięcia
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Filtruje tabelę na podstawie wartości z pola wyszukiwania
 * 
 * @param {string} tableId - ID tabeli do filtrowania
 * @param {string} inputId - ID pola wyszukiwania
 * @param {number} columnIndex - Indeks kolumny, według której filtrujemy
 */
function filterTable(tableId, inputId, columnIndex) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // Rozpoczynamy od 1, aby pominąć nagłówek
        const cell = rows[i].getElementsByTagName('td')[columnIndex];
        if (cell) {
            const textValue = cell.textContent || cell.innerText;
            if (textValue.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}

/**
 * Wyświetla komunikat powodzenia
 * 
 * @param {string} message - Treść komunikatu
 * @param {number} duration - Czas wyświetlania w ms (domyślnie 3000ms)
 */
function showSuccess(message, duration = 3000) {
    showNotification(message, 'success', duration);
}

/**
 * Wyświetla komunikat błędu
 * 
 * @param {string} message - Treść komunikatu
 * @param {number} duration - Czas wyświetlania w ms (domyślnie 5000ms)
 */
function showError(message, duration = 5000) {
    showNotification(message, 'error', duration);
}

/**
 * Wyświetla powiadomienie
 * 
 * @param {string} message - Treść komunikatu
 * @param {string} type - Typ komunikatu (success/error/info)
 * @param {number} duration - Czas wyświetlania w ms
 */
function showNotification(message, type, duration) {
    // Sprawdzenie czy kontener na powiadomienia istnieje
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        document.body.appendChild(container);
    }

    // Tworzenie nowego powiadomienia
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerText = message;

    // Dodanie do kontenera
    container.appendChild(notification);

    // Usunięcie po określonym czasie
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, duration);
} 