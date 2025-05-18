/**
 * Plik JavaScript dla panelu administracyjnego
 * Zawiera wspólne funkcje dla wszystkich widoków panelu
 */

// Funkcje modali
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Funkcje dla wyszukiwania i filtrowania
function filterTable(tableId, inputId, columnIndex = 1) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    const filter = input.value.toLowerCase();
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cell = rows[i].getElementsByTagName('td')[columnIndex];
        if (cell) {
            const text = cell.textContent || cell.innerText;
            rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}

// Funkcja dla obsługi powiadomień
function showNotification(message, type = 'success') {
    const notificationContainer = document.getElementById('notificationContainer');
    
    // Jeśli kontener nie istnieje, stwórz go
    if (!notificationContainer) {
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Stwórz nowe powiadomienie
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Style dla powiadomienia
    notification.style.backgroundColor = type === 'success' ? 'var(--success-color)' : 'var(--danger-color)';
    notification.style.color = 'white';
    notification.style.padding = '10px 15px';
    notification.style.borderRadius = '4px';
    notification.style.margin = '0 0 10px 0';
    notification.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.2)';
    notification.style.display = 'flex';
    notification.style.justifyContent = 'space-between';
    notification.style.alignItems = 'center';
    notification.style.minWidth = '250px';
    
    // Dodaj powiadomienie do kontenera
    document.getElementById('notificationContainer').appendChild(notification);
    
    // Automatycznie ukryj po 5 sekundach
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

// Funkcja do obsługi parametrów URL
function getUrlParams() {
    const searchParams = new URLSearchParams(window.location.search);
    const params = {};
    
    for (const [key, value] of searchParams.entries()) {
        params[key] = value;
    }
    
    return params;
}

// Funkcja do przetwarzania odpowiedzi po akcjach
function processActionResponses() {
    const params = getUrlParams();
    
    // Obsługa komunikatów sukcesu
    if (params.success) {
        switch(params.success) {
            case 'added':
                showNotification('Element został pomyślnie dodany.', 'success');
                break;
            case 'updated':
                showNotification('Element został pomyślnie zaktualizowany.', 'success');
                break;
            case 'deleted':
                showNotification('Element został pomyślnie usunięty.', 'success');
                break;
            default:
                showNotification('Operacja zakończona sukcesem.', 'success');
        }
    }
    
    // Obsługa komunikatów błędu
    if (params.error) {
        switch(params.error) {
            case 'duplicate':
                showNotification('Element o takiej nazwie już istnieje.', 'error');
                break;
            case 'has_products':
                showNotification('Nie można usunąć, ponieważ są powiązane produkty.', 'error');
                break;
            case 'has_orders':
                showNotification('Nie można usunąć, ponieważ są powiązane zamówienia.', 'error');
                break;
            default:
                showNotification('Wystąpił błąd podczas wykonywania operacji.', 'error');
        }
    }
}

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja zamykania modali po kliknięciu poza nimi
    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target === modals[i]) {
                modals[i].style.display = 'none';
            }
        }
    };
    
    // Przetwarzanie odpowiedzi z URL
    processActionResponses();
}); 