<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'add':
      if (empty($nazwa)) {
        header('Location: panel.php?view=positions&error=empty_name');
        exit();
      }
      
      $sql = "INSERT INTO stanowiska (nazwa, wynagrodzenie_miesieczne) VALUES ('$nazwa', '$wynagrodzenie')";
      if (!mysqli_query($connection, $sql)) {
        header('Location: panel.php?view=positions&error=db_error');
        exit();
      }
      header('Location: panel.php?view=positions&success=added');
      exit();
      break;
      
    case 'update':
      $id = mysqli_real_escape_string($connection, $_POST['position_id']);
      $wynagrodzenie = mysqli_real_escape_string($connection, $_POST['wynagrodzenie']);
      
      $sql = "UPDATE stanowiska SET wynagrodzenie_miesieczne = '$wynagrodzenie' WHERE id = '$id'";
      mysqli_query($connection, $sql);
      header('Location: panel.php?view=positions&success=updated');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'nazwa';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'nazwa', 'wynagrodzenie_miesieczne'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'nazwa';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=positions&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie stanowisk
$sql = "SELECT * FROM stanowiska ORDER BY $sort_column $sort_dir";
$stanowiska = mysqli_query($connection, $sql);
?>

<div class="admin-filters">
  <button class="admin-button success add" onclick="showAddPositionModal()">
    <i class="fas fa-plus"></i> Dodaj stanowisko
  </button>
  <div class="admin-search">
    <input type="text" id="positionSearch" class="form-input" placeholder="Szukaj stanowisk..." 
           onkeyup="filterTable('positionTable', 1)">
  </div>
</div>

<div class="admin-table-wrapper">
<table id="positionTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
          Nazwa stanowiska <?php echo getSortIcon('nazwa', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('wynagrodzenie_miesieczne', $sort_column, $sort_dir); ?>" class="sort-link">
          Wynagrodzenie miesięczne <?php echo getSortIcon('wynagrodzenie_miesieczne', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($position = mysqli_fetch_assoc($stanowiska)) : ?>
      <tr>
        <td><?php echo htmlspecialchars($position['id']); ?></td>
        <td>
          <span class="status-badge <?php echo strtolower($position['nazwa']); ?>">
            <?php echo ucfirst($position['nazwa']); ?>
          </span>
        </td>
        <td><?php echo formatPrice($position['wynagrodzenie_miesieczne']); ?></td>
        <td>
          <div class="admin-actions">
            <button class="admin-button warning" onclick="editPosition(<?php echo htmlspecialchars(json_encode($position)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal dodawania/edycji stanowiska -->
<div id="positionModal" class="modal">
  <div class="modal-content">
    <h2 id="modalTitle">Dodaj stanowisko</h2>
    <form method="POST" onsubmit="return validateForm()">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="position_id" id="positionId">
      
      <div class="form-group" id="nameGroup">
        <label for="nazwa" class="form-label">Nazwa stanowiska</label>
        <input type="text" id="nazwa" name="nazwa" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="wynagrodzenie" class="form-label">Wynagrodzenie miesięczne</label>
        <div class="input-group">
          <input type="number" id="wynagrodzenie" name="wynagrodzenie" class="form-input" 
                 required min="0" step="0.01">
        </div>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closePositionModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function showAddPositionModal() {
  const modal = document.getElementById('positionModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  const nameGroup = document.getElementById('nameGroup');
  
  modalTitle.textContent = 'Dodaj stanowisko';
  document.getElementById('formAction').value = 'add';
  document.getElementById('positionId').value = '';
  nameGroup.style.display = 'block';
  form.reset();
  
  modal.style.display = 'block';
}

function editPosition(position) {
  const modal = document.getElementById('positionModal');
  const modalTitle = document.getElementById('modalTitle');
  const nameGroup = document.getElementById('nameGroup');
  
  modalTitle.textContent = 'Edytuj wynagrodzenie';
  document.getElementById('formAction').value = 'update';
  document.getElementById('positionId').value = position.id;
  document.getElementById('nazwa').value = position.nazwa;
  document.getElementById('wynagrodzenie').value = position.wynagrodzenie_miesieczne;
  
  // Ukryj pole wyboru nazwy przy edycji
  nameGroup.style.display = 'none';
  
  modal.style.display = 'block';
}

function closePositionModal() {
  document.getElementById('positionModal').style.display = 'none';
}

function selectPosition(value, text) {
  document.getElementById('selectedPosition').value = value;
  document.getElementById('positionDropdownText').textContent = text;
  document.getElementById('positionDropdown').classList.remove('show');
}

function validateForm() {
  const formAction = document.getElementById('formAction').value;
  const wynagrodzenie = document.getElementById('wynagrodzenie').value;
  
  if (formAction === 'add') {
    const nazwa = document.getElementById('nazwa').value;
    if (!nazwa) {
      alert('Wprowadź nazwę stanowiska');
      return false;
    }
  }
  
  if (wynagrodzenie <= 0) {
    alert('Wynagrodzenie musi być większe od 0');
    return false;
  }
  
  return true;
}

function filterTable(tableId, columnIndex) {
  const input = document.getElementById('positionSearch');
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
  const modal = document.getElementById('positionModal');
  if (event.target == modal) {
    closePositionModal();
  }
}
</script>

<?php
// Na początku pliku, przed jakimkolwiek wyjściem HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Otrzymane dane POST: ' . print_r($_POST, true));
}
?> 