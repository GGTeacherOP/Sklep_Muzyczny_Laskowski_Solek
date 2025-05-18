/**
 * Skrypt obsługujący funkcjonalności związane z kategoriami
 */

/**
 * Pokazuje modal dodawania kategorii
 */
function showAddCategoryModal() {
  const modal = document.getElementById('categoryModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Dodaj kategorię';
  document.getElementById('formAction').value = 'add';
  document.getElementById('categoryId').value = '';
  form.reset();
  
  showModal('categoryModal');
}

/**
 * Zamyka modal dodawania/edycji kategorii
 */
function closeCategoryModal() {
  closeModal('categoryModal');
}

/**
 * Otwiera modal edycji kategorii i wypełnia go danymi
 * 
 * @param {Object} category Obiekt z danymi kategorii
 */
function editCategory(category) {
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Edytuj kategorię';
  document.getElementById('formAction').value = 'update';
  document.getElementById('categoryId').value = category.id;
  document.getElementById('nazwa').value = category.nazwa;
  
  showModal('categoryModal');
}

/**
 * Pokazuje modal potwierdzenia usunięcia
 * 
 * @param {number} id ID kategorii do usunięcia
 */
function confirmDelete(id) {
  document.getElementById('delete_category_id').value = id;
  showModal('deleteModal');
}

/**
 * Zamyka modal potwierdzenia usunięcia
 */
function closeDeleteModal() {
  closeModal('deleteModal');
}

// Obsługa zamykania modali po kliknięciu poza nimi
window.addEventListener('click', function(event) {
  const categoryModal = document.getElementById('categoryModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == categoryModal) {
    closeCategoryModal();
  }
  if (event.target == deleteModal) {
    closeDeleteModal();
  }
}); 