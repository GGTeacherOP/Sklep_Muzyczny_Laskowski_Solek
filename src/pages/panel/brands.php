<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete':
        if (isset($_POST['brand_id'])) {
          $brand_id = mysqli_real_escape_string($connection, $_POST['brand_id']);
          
          // Sprawdzenie czy producent ma powiązane instrumenty
          $check_sql = "SELECT COUNT(*) as count FROM instrumenty WHERE producent_id = '$brand_id'";
          $check_result = mysqli_query($connection, $check_sql);
          $check_row = mysqli_fetch_assoc($check_result);
          
          if ($check_row['count'] > 0) {
            header('Location: panel.php?view=brands&error=has_products');
            exit();
          }
          
          $sql = "DELETE FROM producenci WHERE id = '$brand_id'";
          mysqli_query($connection, $sql);
          header('Location: panel.php?view=brands&success=deleted');
          exit();
        }
        break;
        
      case 'add':
        $nazwa = mysqli_real_escape_string($connection, $_POST['nazwa']);
        $sql = "INSERT INTO producenci (nazwa) VALUES ('$nazwa')";
        
        if (!mysqli_query($connection, $sql)) {
          if ($connection->errno == 1062) {
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
          }
        }
        header('Location: panel.php?view=brands&success=added');
        exit();
        break;
        
      case 'edit':
        $brand_id = mysqli_real_escape_string($connection, $_POST['brand_id']);
        $nazwa = mysqli_real_escape_string($connection, $_POST['nazwa']);
        $sql = "UPDATE producenci SET nazwa = '$nazwa' WHERE id = '$brand_id'";
        
        if (!mysqli_query($connection, $sql)) {
          if ($connection->errno == 1062) {
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
          }
        }
        header('Location: panel.php?view=brands&success=updated');
        exit();
        break;
    }
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'id';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'nazwa', 'liczba_produktow'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=brands&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Tworzenie odpowiedniego zapytania dla sortowania
if ($sort_column === 'liczba_produktow') {
    $query = "SELECT p.*, 
             (SELECT COUNT(*) FROM instrumenty WHERE producent_id = p.id) as liczba_produktow 
             FROM producenci p 
             ORDER BY liczba_produktow $sort_dir, id ASC";
} else {
    $query = "SELECT p.*, 
             (SELECT COUNT(*) FROM instrumenty WHERE producent_id = p.id) as liczba_produktow 
             FROM producenci p 
             ORDER BY $sort_column $sort_dir";
}

$result = $connection->query($query);
?>

<div class="admin-filters">
  <button class="admin-button success add" onclick="showAddModal()">
    <i class="fas fa-plus"></i> Dodaj producenta
  </button>
  <div class="admin-search">
    <input type="text" id="brandSearch" class="form-input" placeholder="Szukaj producentów..." 
           onkeyup="filterTable('brandTable', 1)">
  </div>
</div>

<div class="admin-table-wrapper">
<table id="brandTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
          Nazwa <?php echo getSortIcon('nazwa', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('liczba_produktow', $sort_column, $sort_dir); ?>" class="sort-link">
          Liczba produktów <?php echo getSortIcon('liczba_produktow', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($brand = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($brand['id']); ?></td>
        <td><?php echo htmlspecialchars($brand['nazwa']); ?></td>
        <td><?php echo htmlspecialchars($brand['liczba_produktow']); ?></td>
        <td>
          <div class="admin-actions">
            <button class="admin-button warning" onclick="editBrand(<?php echo htmlspecialchars(json_encode($brand)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
            <?php if ($brand['liczba_produktow'] == 0): ?>
              <button class="admin-button danger" onclick="confirmDelete(<?php echo $brand['id']; ?>)">
                <i class="fas fa-trash"></i>
              </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal dodawania/edycji producenta -->
<div id="brandModal" class="modal">
  <div class="modal-content">
    <h2 id="modalTitle">Dodaj producenta</h2>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="brand_id" id="brandId">
      
      <div class="form-group">
        <label for="nazwa" class="form-label">Nazwa</label>
        <input type="text" id="nazwa" name="nazwa" class="form-input" required maxlength="255">
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeBrandModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h2>Potwierdzenie usunięcia</h2>
    <p>Czy na pewno chcesz usunąć tego producenta? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="brand_id" id="delete_brand_id">
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

<script>
function showAddModal() {
  const modal = document.getElementById('brandModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Dodaj producenta';
  document.getElementById('formAction').value = 'add';
  document.getElementById('brandId').value = '';
  form.reset();
  
  modal.style.display = 'block';
}

function closeBrandModal() {
  document.getElementById('brandModal').style.display = 'none';
}

function editBrand(brand) {
  const modal = document.getElementById('brandModal');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Edytuj producenta';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('brandId').value = brand.id;
  document.getElementById('nazwa').value = brand.nazwa;
  
  modal.style.display = 'block';
}

function filterTable(tableId, columnIndex) {
  const input = document.getElementById('brandSearch');
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    const cell = rows[i].getElementsByTagName('td')[columnIndex];
    if (cell) {
      const text = cell.textContent || cell.innerText;
      rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
  }
}

function confirmDelete(brandId) {
  document.getElementById('delete_brand_id').value = brandId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

// Zamykanie modali po kliknięciu poza nimi
window.onclick = function(event) {
  const brandModal = document.getElementById('brandModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == brandModal) {
    closeBrandModal();
  } else if (event.target == deleteModal) {
    closeDeleteModal();
  }
}

// Obsługa komunikatów
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('success') === 'added') {
    alert('Producent został pomyślnie dodany.');
  } else if (urlParams.get('success') === 'updated') {
    alert('Producent został pomyślnie zaktualizowany.');
  } else if (urlParams.get('success') === 'deleted') {
    alert('Producent został pomyślnie usunięty.');
  } else if (urlParams.get('error') === 'has_products') {
    alert('Nie można usunąć producenta, który ma powiązane produkty.');
  } else if (urlParams.get('error') === 'duplicate') {
    alert('Producent o takiej nazwie już istnieje.');
  }
});
</script>