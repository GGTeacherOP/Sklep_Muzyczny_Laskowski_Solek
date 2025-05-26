<?php
/**
 * Modale dla modułu zamówień
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

  /**
 * Renderuje modal do potwierdzenia usunięcia zamówienia
 * 
 * @return string Kod HTML modala
 */
function renderDeleteConfirmationModal() {
    ob_start();
?>
<div id="deleteConfirmationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Potwierdzenie usunięcia</h2>
        <p>Czy na pewno chcesz usunąć to zamówienie? Ta operacja jest nieodwracalna.</p>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="order_id" id="delete_order_id">
            <div class="admin-actions">
                <button type="submit" class="admin-button danger">
                    <i class="fas fa-trash"></i> Usuń
                </button>
                <button type="button" class="admin-button" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Anuluj
                </button>
            </div>
        </form>
    </div>
</div>
<?php
    return ob_get_clean();
} 