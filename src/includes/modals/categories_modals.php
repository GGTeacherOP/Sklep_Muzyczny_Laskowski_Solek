<?php
/**
 * Modale dla sekcji kategorii w panelu administracyjnym
 */

/**
 * Renderuje modal do dodawania/edycji kategorii
 * @return string HTML z modalem
 */
function renderCategoryFormModal() {
    ob_start();
?>
<!-- Modal dodawania/edycji kategorii -->
<div id="categoryModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeCategoryModal()">&times;</span>
    <h2 id="modalTitle">Dodaj kategorię</h2>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="category_id" id="categoryId">
      
      <div class="form-group">
        <label for="nazwa" class="form-label">Nazwa kategorii</label>
        <input type="text" id="nazwa" name="nazwa" class="form-input" required>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeCategoryModal()">
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
 * Renderuje modal potwierdzenia usunięcia kategorii
 * @return string HTML z modalem
 */
function renderDeleteConfirmationModal() {
    ob_start();
?>
<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDeleteModal()">&times;</span>
    <h2>Potwierdzenie usunięcia</h2>
    <p>Czy na pewno chcesz usunąć tę kategorię? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="category_id" id="delete_category_id">
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