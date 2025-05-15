<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete':
        if (isset($_POST['brand_id'])) {
          $id = (int)$_POST['brand_id'];
          
          // Sprawdzenie czy producent ma powiązane instrumenty
          $stmt = $connection->prepare("SELECT COUNT(*) as count FROM instrumenty WHERE producent_id = ?");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          $row = $result->fetch_assoc();
          $stmt->close();
      
          if ($row['count'] > 0) {
            header('Location: panel.php?view=brands&error=has_products');
            exit();
          }
      
          $stmt = $connection->prepare("DELETE FROM producenci WHERE id = ?");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $stmt->close();
        }
        break;
      case 'add':
        $nazwa = trim($_POST['nazwa']);

        $stmt = $connection->prepare("INSERT INTO producenci (nazwa) VALUES (?)");
        $stmt->bind_param("s", $nazwa);
        
        if (!$stmt->execute()) {
          if ($connection->errno == 1062) { // Kod błędu dla naruszenia UNIQUE KEY
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
          }
        }
        
        $stmt->close();
        break;
      case 'edit':
        $id = (int)$_POST['brand_id'];
        $nazwa = trim($_POST['nazwa']);

        $stmt = $connection->prepare("UPDATE producenci SET nazwa = ? WHERE id = ?");
        $stmt->bind_param("si", $nazwa, $id);
        
        if (!$stmt->execute()) {
          if ($connection->errno == 1062) { // Kod błędu dla naruszenia UNIQUE KEY
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
          }
        }
        
        $stmt->close();
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

<div class="admin-actions">
  <button class="admin-button success" onclick="showAddModal()">
    <i class="fas fa-plus"></i> Dodaj producenta
  </button>
</div>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="brandSearch" class="form-input" placeholder="Szukaj producentów..." 
           onkeyup="filterTable('brandTable', 1)">
  </div>
</div>

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
            <form method="POST" style="display: inline;" 
                  onsubmit="return confirm('Czy na pewno chcesz usunąć tego producenta?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="brand_id" value="<?php echo $brand['id']; ?>">
              <button type="submit" class="admin-button danger" <?php echo $brand['liczba_produktow'] > 0 ? 'disabled' : ''; ?>>
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Modal dodawania/edycji producenta -->
<div id="brandModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeBrandModal()">&times;</span>
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
  const modal = document.getElementById('brandModal');
  modal.style.display = 'none';
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

// Zamykanie modalu po kliknięciu poza nim
window.onclick = function(event) {
  const modal = document.getElementById('brandModal');
  if (event.target == modal) {
    closeBrandModal();
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