/**
 * Skrypt JavaScript dla widoku zamówień w panelu administratora
 */

/**
 * Moduł obsługi zamówień w panelu administracyjnym
 */
const OrdersModule = (function() {
    /**
     * Otwiera modal z edycją statusu zamówienia
     * 
     * @param {number} orderId - ID zamówienia
     * @param {string} currentStatus - Aktualny status zamówienia
     */
    function editOrderStatus(orderId, currentStatus) {
        document.getElementById('order_id').value = orderId;
        
        const statusSelect = document.getElementById('order_status');
        for (let i = 0; i < statusSelect.options.length; i++) {
            if (statusSelect.options[i].value === currentStatus) {
                statusSelect.selectedIndex = i;
                break;
            }
        }
        
        showModal('statusChangeModal');
    }

    /**
     * Otwiera modal z potwierdzeniem usunięcia zamówienia
     * 
     * @param {number} orderId - ID zamówienia do usunięcia
     */
    function confirmDelete(orderId) {
        document.getElementById('delete_order_id').value = orderId;
        showModal('deleteConfirmationModal');
    }

    /**
     * Zamyka modal zmiany statusu
     */
    function closeStatusModal() {
        closeModal('statusChangeModal');
    }

    /**
     * Zamyka modal potwierdzenia usunięcia
     */
    function closeDeleteModal() {
        closeModal('deleteConfirmationModal');
    }

    /**
     * Filtruje tabelę zamówień według statusu
     * 
     * @param {string} status - Status do filtrowania
     */
    function filterByStatus(status) {
        const table = document.getElementById('orderTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            if (!rows[i].getAttribute('data-status')) continue;
            
            const rowStatus = rows[i].getAttribute('data-status');
            if (status === '' || rowStatus === status) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    /**
     * Inicjalizacja funkcji dla strony zamówień
     */
    function init() {
        const orderTable = document.getElementById('orderTable');
        const orderSearch = document.getElementById('orderSearch');
        
        if (orderTable && orderSearch) {
            orderSearch.addEventListener('keyup', function() {
                filterTable('orderTable', 'orderSearch', 1);
            });
        }
    }

    // Eksportowanie publicznych funkcji
    return {
        editOrderStatus: editOrderStatus,
        confirmDelete: confirmDelete,
        closeStatusModal: closeStatusModal,
        closeDeleteModal: closeDeleteModal,
        filterByStatus: filterByStatus,
        init: init
    };
})();

// Inicjalizacja modułu po załadowaniu DOM
document.addEventListener('DOMContentLoaded', OrdersModule.init);

// Eksportowanie funkcji do globalnego zakresu (dla obsługi zdarzeń z HTML)
window.editOrderStatus = OrdersModule.editOrderStatus;
window.confirmDelete = OrdersModule.confirmDelete;
window.closeStatusModal = OrdersModule.closeStatusModal;
window.closeDeleteModal = OrdersModule.closeDeleteModal;
window.filterByStatus = OrdersModule.filterByStatus;

// Szczegóły zamówienia klienta
function viewOrderDetails(orderId) {
    const detailsContainer = document.getElementById('clientOrderDetails');
    const detailsContent = document.getElementById('orderDetailsContent');
    
    detailsContainer.style.display = 'block';
    detailsContent.innerHTML = '<p class="loading">Ładowanie szczegółów...</p>';
    
    // Pobierz szczegóły zamówienia przez AJAX
    fetch(`../includes/ajax/get_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="order-items">';
                html += '<table class="admin-table">';
                html += '<thead><tr><th>Produkt</th><th>Kod</th><th>Cena</th><th>Ilość</th><th>Suma</th></tr></thead>';
                html += '<tbody>';
                
                data.items.forEach(item => {
                    const itemTotal = (item.cena * item.ilosc).toFixed(2);
                    html += `<tr>
                        <td>${item.nazwa}</td>
                        <td>${item.kod_produktu}</td>
                        <td>${item.cena} zł</td>
                        <td>${item.ilosc}</td>
                        <td>${itemTotal} zł</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                
                // Podsumowanie
                html += '<div class="order-summary">';
                html += `<div class="order-summary-item"><span>Suma częściowa:</span><span>${data.summary.subtotal} zł</span></div>`;
                
                if (data.summary.discount) {
                    html += `<div class="order-summary-item discount">
                        <span>Rabat (${data.summary.discount_percent}%):</span>
                        <span>-${data.summary.discount} zł</span>
                    </div>`;
                }
                
                html += `<div class="order-summary-item total"><span>Razem:</span><span>${data.summary.total} zł</span></div>`;
                html += '</div>';
                html += '</div>';
                
                detailsContent.innerHTML = html;
            } else {
                detailsContent.innerHTML = '<div class="admin-alert error">Nie udało się pobrać szczegółów zamówienia</div>';
            }
        })
        .catch(error => {
            detailsContent.innerHTML = '<div class="admin-alert error">Wystąpił błąd podczas pobierania szczegółów</div>';
            console.error('Error:', error);
        });
} 