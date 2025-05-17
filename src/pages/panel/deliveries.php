<?php
/** @var mysqli $connection */

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'add':
      $producent_id = mysqli_real_escape_string($connection, $_POST['producent_id']);
      $pracownik_id = $_SESSION['employee_id'];
      
      // Dodaj nową dostawę
      $sql = "INSERT INTO dostawy (producent_id, pracownik_id) VALUES ('$producent_id', '$pracownik_id')";
      if (mysqli_query($connection, $sql)) {
        $dostawa_id = mysqli_insert_id($connection);
        
        // Dodaj szczegóły dostawy
        foreach ($_POST['instrumenty'] as $index => $instrument_id) {
          $ilosc = mysqli_real_escape_string($connection, $_POST['ilosci'][$index]);
          $cena = mysqli_real_escape_string($connection, $_POST['ceny'][$index]);
          
          $sql = "INSERT INTO dostawa_szczegoly (dostawa_id, instrument_id, ilosc, cena_zakupu) 
                  VALUES ('$dostawa_id', '$instrument_id', '$ilosc', '$cena')";
          mysqli_query($connection, $sql);
        }
      }
      header('Location: panel.php?view=deliveries&success=added');
      exit();
      break;
      
    case 'update_status':
      $dostawa_id = mysqli_real_escape_string($connection, $_POST['dostawa_id']);
      $new_status = mysqli_real_escape_string($connection, $_POST['status']);
      
      // Aktualizuj status dostawy
      $sql = "UPDATE dostawy SET status = '$new_status'";
      if ($new_status === 'dostarczona') {
        $sql .= ", data_dostawy = NOW()";
      }
      $sql .= " WHERE id = '$dostawa_id'";
      mysqli_query($connection, $sql);
      
      // Jeśli dostawa jest dostarczona, zaktualizuj stan magazynowy
      if ($new_status === 'dostarczona') {
        $sql = "SELECT ds.instrument_id, ds.ilosc 
                FROM dostawa_szczegoly ds 
                WHERE ds.dostawa_id = '$dostawa_id' AND ds.status = 'oczekiwana'";
        $result = mysqli_query($connection, $sql);
        
        while ($detail = mysqli_fetch_assoc($result)) {
          $instrument_id = $detail['instrument_id'];
          $ilosc = $detail['ilosc'];
          
          mysqli_query($connection, "UPDATE instrumenty 
                                   SET stan_magazynowy = stan_magazynowy + $ilosc 
                                   WHERE id = '$instrument_id'");
          
          mysqli_query($connection, "UPDATE dostawa_szczegoly 
                                   SET status = 'dostarczona' 
                                   WHERE dostawa_id = '$dostawa_id' 
                                   AND instrument_id = '$instrument_id'");
        }
      }
      
      header('Location: panel.php?view=deliveries&success=updated');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'data_zamowienia';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['d.id', 'd.data_zamowienia', 'd.data_dostawy', 'd.status', 'p.nazwa'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'd.data_zamowienia';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=deliveries&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie dostaw
$sql = "SELECT d.*, p.nazwa as producent_nazwa, pr.identyfikator as pracownik_id
        FROM dostawy d
        JOIN producenci p ON d.producent_id = p.id
        JOIN pracownicy pr ON d.pracownik_id = pr.id
        ORDER BY $sort_column $sort_dir";
$dostawy = mysqli_query($connection, $sql);

// Pobranie producentów do formularza
$producenci = mysqli_query($connection, "SELECT * FROM producenci ORDER BY nazwa");

// Pobranie instrumentów do formularza
$instrumenty = mysqli_query($connection, "SELECT * FROM instrumenty ORDER BY nazwa");
?>

<div class="admin-filters">
  <button class="admin-button success add" onclick="showAddDeliveryModal()">
    <i class="fas fa-plus"></i> Dodaj dostawę
  </button>
  <div class="admin-search">
    <input type="text" id="deliverySearch" class="form-input" placeholder="Szukaj dostaw..." 
           onkeyup="filterTable('deliveryTable', 1)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('statusDropdown')">
      <span id="statusDropdownText">Wszystkie statusy</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="statusDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectStatus('', 'Wszystkie statusy')">Wszystkie statusy</a></li>
      <li class="dropdown-divider"></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('oczekiwana', 'Oczekiwane')">Oczekiwane</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('dostarczona', 'Dostarczone')">Dostarczone</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('anulowana', 'Anulowane')">Anulowane</a></li>
    </ul>
  </div>
</div>

<div class="admin-table-wrapper">
<table id="deliveryTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('d.id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('d.id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('p.nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
          Producent <?php echo getSortIcon('p.nazwa', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('d.data_zamowienia', $sort_column, $sort_dir); ?>" class="sort-link">
          Data zamówienia <?php echo getSortIcon('d.data_zamowienia', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('d.data_dostawy', $sort_column, $sort_dir); ?>" class="sort-link">
          Data dostawy <?php echo getSortIcon('d.data_dostawy', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('d.status', $sort_column, $sort_dir); ?>" class="sort-link">
          Status <?php echo getSortIcon('d.status', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($dostawa = mysqli_fetch_assoc($dostawy)) : ?>
      <tr data-status="<?php echo htmlspecialchars($dostawa['status']); ?>">
        <td><?php echo htmlspecialchars($dostawa['id']); ?></td>
        <td><?php echo htmlspecialchars($dostawa['producent_nazwa']); ?></td>
        <td><?php echo date('d.m.Y H:i', strtotime($dostawa['data_zamowienia'])); ?></td>
        <td>
          <?php echo $dostawa['data_dostawy'] ? date('d.m.Y H:i', strtotime($dostawa['data_dostawy'])) : '-'; ?>
        </td>
        <td>
          <span class="status-badge <?php echo $dostawa['status']; ?>">
            <?php
              $statusy = [
                'oczekiwana' => 'Oczekiwana',
                'dostarczona' => 'Dostarczona',
                'anulowana' => 'Anulowana'
              ];
              echo $statusy[$dostawa['status']] ?? $dostawa['status'];
            ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
          <button class="admin-button info" onclick="showDeliveryDetails(<?php echo $dostawa['id']; ?>)">
            <i class="fas fa-eye"></i>
          </button>
            <?php if ($dostawa['status'] === 'oczekiwana'): ?>
              <button class="admin-button warning" onclick="showStatusModal(<?php echo $dostawa['id']; ?>, '<?php echo $dostawa['status']; ?>')">
                <i class="fas fa-edit"></i>
              </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal dodawania dostawy -->
<div id="deliveryModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDeliveryModal()">&times;</span>
    <h2>Dodaj nową dostawę</h2>
    <form method="POST" onsubmit="return validateDeliveryForm()">
      <input type="hidden" name="action" value="add">
      
      <div class="form-group">
        <label for="producent_id" class="form-label">Producent</label>
        <select id="producent_id" name="producent_id" class="form-input" required>
          <option value="">Wybierz producenta</option>
          <?php while ($producent = mysqli_fetch_assoc($producenci)) : ?>
            <option value="<?php echo $producent['id']; ?>">
              <?php echo htmlspecialchars($producent['nazwa']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      
      <div id="instrumenty_container">
        <div class="instrument-row">
          <div class="form-group">
            <label class="form-label">Instrument</label>
            <select name="instrumenty[]" class="form-input" required>
              <option value="">Wybierz instrument</option>
              <?php 
              mysqli_data_seek($instrumenty, 0);
              while ($instrument = mysqli_fetch_assoc($instrumenty)) : 
              ?>
                <option value="<?php echo $instrument['id']; ?>">
                  <?php echo htmlspecialchars($instrument['nazwa']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Ilość</label>
            <input type="number" name="ilosci[]" class="form-input" required min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Cena zakupu</label>
            <input type="number" name="ceny[]" class="form-input" required min="0.01" step="0.01">
          </div>
          <button type="button" class="admin-button danger" onclick="removeInstrumentRow(this)">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
      
      <button type="button" class="admin-button" onclick="addInstrumentRow()">
        <i class="fas fa-plus"></i> Dodaj instrument
      </button>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeDeliveryModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal szczegółów dostawy -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDetailsModal()">&times;</span>
    <h2>Szczegóły dostawy</h2>
    <div id="deliveryDetails"></div>
  </div>
</div>

<!-- Modal zmiany statusu -->
<div id="statusModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeStatusModal()">&times;</span>
    <h2>Zmień status dostawy</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="dostawa_id" id="status_dostawa_id">
      
      <div class="form-group">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-input" required>
          <option value="oczekiwana">Oczekiwana</option>
          <option value="dostarczona">Dostarczona</option>
          <option value="anulowana">Anulowana</option>
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

<script>
function toggleDropdown(dropdownId) {
  const dropdown = document.getElementById(dropdownId);
  dropdown.classList.toggle('show');
  
  // Zamykanie innych dropdownów
  const allDropdowns = document.querySelectorAll('.dropdown-menu');
  allDropdowns.forEach(d => {
    if (d.id !== dropdownId && d.classList.contains('show')) {
      d.classList.remove('show');
    }
  });
}

function showAddDeliveryModal() {
  const modal = document.getElementById('deliveryModal');
  modal.style.display = 'block';
}

function closeDeliveryModal() {
  document.getElementById('deliveryModal').style.display = 'none';
}

function addInstrumentRow() {
  const container = document.getElementById('instrumenty_container');
  const newRow = container.children[0].cloneNode(true);
  
  // Wyczyść wartości
  newRow.querySelectorAll('input, select').forEach(input => {
    input.value = '';
  });
  
  container.appendChild(newRow);
}

function removeInstrumentRow(button) {
  const container = document.getElementById('instrumenty_container');
  if (container.children.length > 1) {
    button.parentElement.remove();
  }
}

function validateDeliveryForm() {
  const instrumenty = document.getElementsByName('instrumenty[]');
  const ilosci = document.getElementsByName('ilosci[]');
  const ceny = document.getElementsByName('ceny[]');
  
  for (let i = 0; i < instrumenty.length; i++) {
    if (!instrumenty[i].value || !ilosci[i].value || !ceny[i].value) {
      alert('Wypełnij wszystkie pola dla każdego instrumentu');
      return false;
    }
  }
  
  return true;
}

async function showDeliveryDetails(dostawaId) {
  const modal = document.getElementById('detailsModal');
  const detailsContainer = document.getElementById('deliveryDetails');
  
  try {
    const response = await fetch(`/sm/src/api/delivery_details.php?id=${dostawaId}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const details = await response.json();
    
    let html = '<table class="admin-table">';
    html += '<thead><tr><th>Instrument</th><th>Ilość</th><th>Cena zakupu</th></tr></thead>';
    html += '<tbody>';
    
    details.forEach(detail => {
      const status = detail.status === 'oczekiwana' ? 'Oczekiwana' :
                    detail.status === 'dostarczona' ? 'Dostarczona' :
                    detail.status === 'anulowana' ? 'Anulowana' : detail.status;
      
      html += `<tr>
        <td>${detail.nazwa_instrumentu}</td>
        <td>${detail.ilosc}</td>
        <td>${detail.cena_zakupu} zł</td>
      </tr>`;
    });
    
    html += '</tbody></table>';
    detailsContainer.innerHTML = html;
    modal.style.display = 'block';
    
  } catch (error) {
    console.error('Błąd podczas pobierania szczegółów:', error);
    alert('Wystąpił błąd podczas pobierania szczegółów dostawy');
  }
}

function closeDetailsModal() {
  document.getElementById('detailsModal').style.display = 'none';
}

function showStatusModal(dostawaId, currentStatus) {
  const modal = document.getElementById('statusModal');
  document.getElementById('status_dostawa_id').value = dostawaId;
  document.getElementById('status').value = currentStatus;
  modal.style.display = 'block';
}

function closeStatusModal() {
  document.getElementById('statusModal').style.display = 'none';
}

function selectStatus(status, statusText) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('statusDropdownText');
  button.textContent = statusText;
  button.dataset.selectedId = status;
  
  // Filtruj dostawy
  filterByStatus(status);
  
  // Ukryj dropdown
  document.getElementById('statusDropdown').classList.remove('show');
}

function filterByStatus(status) {
  const rows = document.querySelectorAll('#deliveryTable tbody tr');
  rows.forEach(row => {
    if (!status || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Zamykanie modali po kliknięciu poza nimi
window.onclick = function(event) {
  const deliveryModal = document.getElementById('deliveryModal');
  const detailsModal = document.getElementById('detailsModal');
  const statusModal = document.getElementById('statusModal');
  
  if (event.target == deliveryModal) {
    closeDeliveryModal();
  } else if (event.target == detailsModal) {
    closeDetailsModal();
  } else if (event.target == statusModal) {
    closeStatusModal();
  }
}
</script>