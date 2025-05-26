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