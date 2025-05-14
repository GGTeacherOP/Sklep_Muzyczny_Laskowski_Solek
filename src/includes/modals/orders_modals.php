<?php
/**
 * Modale dla modułu zamówień
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Renderuje modal do zmiany statusu zamówienia
 * 
 * @return string Kod HTML modala
 */
function renderStatusChangeModal() {
    ob_start();
?>
<div id="statusChangeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeStatusModal()">&times;</span>
        <h2>Zmień status zamówienia</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" id="order_id">
            
            <div class="form-group">
                <label for="order_status" class="form-label">Status zamówienia</label>
                <select id="order_status" name="status" class="form-input">
                    <?php foreach (ORDER_STATUSES as $status => $info): ?>
                        <option value="<?php echo $status; ?>"><?php echo $info['label']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="admin-actions">
                <button type="submit" class="admin-button success">
                    <i class="fas fa-save"></i> Zapisz
                </button>
                <button type="button" class="admin-button" onclick="closeStatusModal()">
                    <i class="fas fa-times"></i> Anuluj
                </button>
            </div>
        </form>
    </div>
</div>
<?php
    return ob_get_clean();
}

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