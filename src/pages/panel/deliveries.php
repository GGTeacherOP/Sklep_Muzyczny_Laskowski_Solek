<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Sprawdź czy zmienna $role jest ustawiona (powinna być przekazana z panel.php)
if (!isset($role)) {
  // Jeśli nie jest ustawiona, pobierz ją
  if (!isset($_SESSION)) {
    session_start();
  }
  
  if (isset($_SESSION['employee_id'])) {
    $employee_id = $_SESSION['employee_id'];
    
    // Pobierz pracownika
    $stmt = $connection->prepare("SELECT * FROM pracownicy WHERE identyfikator = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();
    
    if ($employee) {
      // Pobierz nazwę stanowiska
      $stmt = $connection->prepare("SELECT s.nazwa FROM stanowiska s JOIN pracownicy p ON s.id = p.stanowisko_id WHERE p.id = ?");
      $stmt->bind_param("i", $employee['id']);
      $stmt->execute();
      $stanowisko_result = $stmt->get_result();
      $stanowisko = $stanowisko_result->fetch_assoc();
      $role = $stanowisko['nazwa'];
      $stmt->close();
    }
  }
}

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'add':
      // Zmiana: manager również może dodawać dostawy
      if ($role === 'właściciel' || $role === 'manager') {
        // Pobierz ID pracownika na podstawie identyfikatora z sesji
        $employee_identifier = $_SESSION['employee_id'];
        $stmt = $connection->prepare("SELECT id FROM pracownicy WHERE identyfikator = ?");
        $stmt->bind_param("s", $employee_identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $pracownik = $result->fetch_assoc();
        $pracownik_id = $pracownik['id'];
        $stmt->close();
        
        // Sprawdź czy mamy jakiekolwiek instrumenty
        if (empty($_POST['instrumenty']) || !is_array($_POST['instrumenty'])) {
          header('Location: panel.php?view=deliveries&error=no_instruments');
          exit();
        }
        
        // Pobierz producenta pierwszego instrumentu
        $first_instrument_id = null;
        $producent_id = null;
        
        foreach ($_POST['instrumenty'] as $index => $instrument_id) {
          if (!empty($instrument_id)) {
            $first_instrument_id = $instrument_id;
            break;
          }
        }
        
        if ($first_instrument_id) {
          // Pobierz producenta instrumentu
          $stmt = $connection->prepare("SELECT producent_id FROM instrumenty WHERE id = ?");
          $stmt->bind_param("i", $first_instrument_id);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($instrument = $result->fetch_assoc()) {
            $producent_id = $instrument['producent_id'];
          }
          $stmt->close();
        }
        
        if (!$producent_id) {
          header('Location: panel.php?view=deliveries&error=invalid_instrument');
          exit();
        }
        
        // Dodaj nową dostawę z producentem
        $sql = "INSERT INTO dostawy (pracownik_id, producent_id) VALUES ('$pracownik_id', '$producent_id')";
        if (mysqli_query($connection, $sql)) {
          $dostawa_id = mysqli_insert_id($connection);
          
          // Dodaj szczegóły dostawy
          foreach ($_POST['instrumenty'] as $index => $instrument_id) {
            if (empty($instrument_id)) continue; // Pomijamy puste wiersze
            
            $ilosc = (int)mysqli_real_escape_string($connection, $_POST['ilosci'][$index]);
            // Użyj prepared statement dla bezpieczeństwa i poprawnej obsługi typu float
            $cena = (float)mysqli_real_escape_string($connection, $_POST['ceny'][$index]);
            
            // Walidacja - upewnij się, że cena jest większa od zera
            if ($cena <= 0 || $ilosc <= 0) {
              continue; // Pomiń tę pozycję jeśli cena lub ilość jest nieprawidłowa
            }
            
            // Sprawdź producenta instrumentu
            $stmt = $connection->prepare("SELECT producent_id FROM instrumenty WHERE id = ?");
            $stmt->bind_param("i", $instrument_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $instrument = $result->fetch_assoc();
            $instrument_producent_id = $instrument['producent_id'];
            $stmt->close();
            
            // Użyj prepared statement zamiast bezpośredniego wstawiania
            $status = 'oczekiwana'; // Domyślny status
            $stmt = $connection->prepare("INSERT INTO dostawa_szczegoly (dostawa_id, instrument_id, ilosc, cena_zakupu, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $dostawa_id, $instrument_id, $ilosc, $cena, $status);
            $stmt->execute();
            $stmt->close();
          }
        }
        header('Location: panel.php?view=deliveries&success=added');
        exit();
      } else {
        header('Location: panel.php?view=deliveries&error=permission_denied');
        exit();
      }
      break;
      
    case 'update_status':
      $dostawa_id = mysqli_real_escape_string($connection, $_POST['dostawa_id']);
      $new_status = mysqli_real_escape_string($connection, $_POST['status']);
      
      // Zmiana: pracownik może zmienić status tylko na "dostarczona", manager ma pełny dostęp
      if ($role === 'pracownik' && $new_status !== 'dostarczona') {
        header('Location: panel.php?view=deliveries&error=permission_denied');
        exit();
      }
      
      // Aktualizuj status dostawy
      $sql = "UPDATE dostawy SET status = '$new_status'";
      if ($new_status === 'dostarczona') {
        $sql .= ", data_dostawy = NOW()";
      }
      $sql .= " WHERE id = '$dostawa_id'";
      mysqli_query($connection, $sql);
      
      // Jeśli dostawa jest dostarczona, zaktualizuj stan magazynowy
      if ($new_status === 'dostarczona') {
        // Pobierz szczegóły dostawy
        $sql = "SELECT ds.instrument_id, ds.ilosc 
                FROM dostawa_szczegoly ds 
                WHERE ds.dostawa_id = '$dostawa_id' AND ds.status = 'oczekiwana'";
        $result = mysqli_query($connection, $sql);
        
        while ($detail = mysqli_fetch_assoc($result)) {
          $instrument_id = $detail['instrument_id'];
          $ilosc = $detail['ilosc'];
          
          // Aktualizuj stan magazynowy instrumentu
          $update_sql = "UPDATE instrumenty 
                        SET stan_magazynowy = stan_magazynowy + $ilosc 
                        WHERE id = '$instrument_id'";
          mysqli_query($connection, $update_sql);
          
          // Aktualizuj status szczegółu dostawy
          $update_detail_sql = "UPDATE dostawa_szczegoly 
                              SET status = 'dostarczona' 
                              WHERE dostawa_id = '$dostawa_id' 
                              AND instrument_id = '$instrument_id'";
          mysqli_query($connection, $update_detail_sql);
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
$instrumenty = mysqli_query($connection, "SELECT i.*, p.nazwa as nazwa_producenta FROM instrumenty i JOIN producenci p ON i.producent_id = p.id ORDER BY i.nazwa");

// Zorganizowanie instrumentów według producenta
$instrumenty_by_producer = [];
while ($instrument = mysqli_fetch_assoc($instrumenty)) {
  if (!isset($instrumenty_by_producer[$instrument['producent_id']])) {
    $instrumenty_by_producer[$instrument['producent_id']] = [];
  }
  $instrumenty_by_producer[$instrument['producent_id']][] = $instrument;
}
mysqli_data_seek($instrumenty, 0);
?>

<div class="admin-filters">
  <?php if ($role === 'właściciel' || $role === 'manager'): ?>
  <button class="admin-button success add" onclick="showAddDeliveryModal()">
    <i class="fas fa-plus"></i> Dodaj dostawę
  </button>
  <?php endif; ?>
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
              <?php if ($role === 'pracownik'): ?>
              <button class="admin-button success" onclick="showDeliveryStatusModal(<?php echo $dostawa['id']; ?>, 'dostarczona')">
                <i class="fas fa-check"></i>
              </button>
              <?php else: ?>
              <button class="admin-button warning" onclick="showStatusModal(<?php echo $dostawa['id']; ?>, '<?php echo $dostawa['status']; ?>')">
                <i class="fas fa-edit"></i>
              </button>
              <?php endif; ?>
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
    <h2>Dodaj nową dostawę</h2>
    <form method="POST" id="deliveryForm" onsubmit="return validateDeliveryForm()">
      <input type="hidden" name="action" value="add">
      
      <div id="instrumenty_container">
        <div class="instrument-row">
          <div class="form-group">
            <label class="form-label">Producent</label>
            <select name="producenci[]" class="form-input producer-select" required onchange="filterInstrumentsForRow(this)">
              <option value="">Wybierz producenta</option>
              <?php 
              mysqli_data_seek($producenci, 0);
              while ($producent = mysqli_fetch_assoc($producenci)): 
              ?>
                <option value="<?php echo $producent['id']; ?>">
                  <?php echo htmlspecialchars($producent['nazwa']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Instrument</label>
            <select name="instrumenty[]" class="form-input instrument-select" required disabled onchange="updatePrice(this)">
              <option value="">Najpierw wybierz producenta</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Ilość</label>
            <input type="number" name="ilosci[]" class="form-input" required min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Cena zakupu</label>
            <input type="number" name="ceny[]" class="form-input price-input" readonly required min="0.01" step="0.01">
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
    <h2>Szczegóły dostawy</h2>
    <div id="deliveryDetails"></div>
    <div class="admin-actions">
      <button type="button" class="admin-button" onclick="closeDetailsModal()">
        <i class="fas fa-times"></i> Zamknij
      </button>
    </div>
  </div>
</div>

<!-- Modal zmiany statusu -->
<div id="statusModal" class="modal">
  <div class="modal-content">
    <h2>Zmień status dostawy</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="dostawa_id" id="status_dostawa_id">
      <input type="hidden" name="status" id="selectedDeliveryStatus">
      
      <div class="form-group">
        <label for="status" class="form-label">Status</label>
        <div class="dropdown">
          <button type="button" class="dropdown-toggle" onclick="toggleDropdown('deliveryStatusDropdown')">
            <span id="deliveryStatusDropdownText">Wybierz status</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="deliveryStatusDropdown">
            <?php if ($role !== 'pracownik'): ?>
            <li><a href="#" class="dropdown-item" onclick="selectDeliveryStatus('oczekiwana', 'Oczekiwana')">Oczekiwana</a></li>
            <?php endif; ?>
            <li><a href="#" class="dropdown-item" onclick="selectDeliveryStatus('dostarczona', 'Dostarczona')">Dostarczona</a></li>
            <?php if ($role !== 'pracownik'): ?>
            <li><a href="#" class="dropdown-item" onclick="selectDeliveryStatus('anulowana', 'Anulowana')">Anulowana</a></li>
            <?php endif; ?>
          </ul>
        </div>
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

<!-- Modal potwierdzenia dostarczenia -->
<div id="confirmDeliveryModal" class="modal">
  <div class="modal-content">
    <h2>Potwierdzenie dostarczenia</h2>
    <p>Czy na pewno chcesz oznaczyć tę dostawę jako dostarczoną?</p>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="dostawa_id" id="confirm_dostawa_id">
      <input type="hidden" name="status" value="dostarczona">
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-check"></i> Potwierdź
        </button>
        <button type="button" class="admin-button" onclick="closeConfirmDeliveryModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Dane instrumentów dla każdego producenta
const instrumentsByProducer = <?php echo json_encode($instrumenty_by_producer); ?>;

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
  resetDeliveryForm();
  const modal = document.getElementById('deliveryModal');
  modal.style.display = 'block';
}

function closeDeliveryModal() {
  document.getElementById('deliveryModal').style.display = 'none';
  resetDeliveryForm();
}

function resetDeliveryForm() {
  const form = document.getElementById('deliveryForm');
  if (form) {
    form.reset();
    
    // Zachowaj tylko pierwszy wiersz i zresetuj go
    const container = document.getElementById('instrumenty_container');
    const firstRow = container.querySelector('.instrument-row');
    
    // Usuń wszystkie wiersze oprócz pierwszego
    while (container.children.length > 1) {
      container.removeChild(container.lastChild);
    }
    
    // Zresetuj pierwszy wiersz
    const selects = firstRow.querySelectorAll('select');
    selects.forEach(select => {
      select.value = '';
      if (select.classList.contains('instrument-select')) {
        select.innerHTML = '<option value="">Najpierw wybierz producenta</option>';
        select.disabled = true;
      }
    });
    
    firstRow.querySelectorAll('input').forEach(input => {
      input.value = '';
    });
  }
}

function filterInstrumentsForRow(producerSelect) {
  const producerId = producerSelect.value;
  const row = producerSelect.closest('.instrument-row');
  const instrumentSelect = row.querySelector('.instrument-select');
  
  // Wyczyść obecne opcje
  instrumentSelect.innerHTML = '';
  
  if (!producerId) {
    // Jeśli nie wybrano producenta
    instrumentSelect.innerHTML = '<option value="">Najpierw wybierz producenta</option>';
    instrumentSelect.disabled = true;
    
    // Wyczyść cenę
    const priceInput = row.querySelector('.price-input');
    if (priceInput) priceInput.value = '';
  } else {
    // Dodaj opcje dla wybranego producenta
    instrumentSelect.innerHTML = '<option value="">Wybierz instrument</option>';
    const instruments = instrumentsByProducer[producerId] || [];
    
    instruments.forEach(instrument => {
      const option = document.createElement('option');
      option.value = instrument.id;
      option.textContent = instrument.nazwa;
      option.dataset.price = instrument.cena_kupna;
      instrumentSelect.appendChild(option);
    });
    
    instrumentSelect.disabled = false;
  }
}

function updatePrice(selectElement) {
  const selectedOption = selectElement.options[selectElement.selectedIndex];
  const priceInput = selectElement.closest('.instrument-row').querySelector('.price-input');
  
  if (selectedOption && selectedOption.dataset.price) {
    priceInput.value = selectedOption.dataset.price;
  } else {
    priceInput.value = '';
  }
}

function addInstrumentRow() {
  const container = document.getElementById('instrumenty_container');
  const newRow = container.children[0].cloneNode(true);
  
  // Wyczyść wartości
  newRow.querySelectorAll('input, select').forEach(input => {
    input.value = '';
    
    // Odpowiednio skonfiguruj selekty
    if (input.classList.contains('instrument-select')) {
      input.innerHTML = '<option value="">Najpierw wybierz producenta</option>';
      input.disabled = true;
    }
  });
  
  // Dodaj nowy listener do filtrowania instrumentów
  const producerSelect = newRow.querySelector('.producer-select');
  producerSelect.addEventListener('change', function() {
    filterInstrumentsForRow(this);
  });
  
  // Dodaj nowy listener do aktualizacji ceny
  const instrumentSelect = newRow.querySelector('.instrument-select');
  instrumentSelect.addEventListener('change', function() {
    updatePrice(this);
  });
  
  container.appendChild(newRow);
}

function removeInstrumentRow(button) {
  const container = document.getElementById('instrumenty_container');
  if (container.children.length > 1) {
    button.closest('.instrument-row').remove();
  } else {
    // Jeśli to ostatni wiersz, po prostu go resetuj
    const row = container.children[0];
    row.querySelectorAll('select').forEach(select => {
      select.value = '';
      if (select.classList.contains('instrument-select')) {
        select.innerHTML = '<option value="">Najpierw wybierz producenta</option>';
        select.disabled = true;
      }
    });
    
    row.querySelectorAll('input').forEach(input => {
      input.value = '';
    });
  }
}

function validateDeliveryForm() {
  const instrumenty = document.getElementsByName('instrumenty[]');
  const ilosci = document.getElementsByName('ilosci[]');
  const ceny = document.getElementsByName('ceny[]');
  const producenci = document.getElementsByName('producenci[]');
  
  // Sprawdź czy jest przynajmniej jedna pozycja
  let hasValidItem = false;
  
  for (let i = 0; i < instrumenty.length; i++) {
    // Pomijamy niepełne wiersze
    if (!producenci[i].value || !instrumenty[i].value || !ilosci[i].value || !ceny[i].value) {
      continue;
    }
    
    hasValidItem = true;
    
    // Dodatkowa walidacja wartości
    if (parseFloat(ceny[i].value) <= 0 || parseInt(ilosci[i].value) <= 0) {
      alert('Cena i ilość muszą być większe od zera');
      return false;
    }
  }
  
  if (!hasValidItem) {
    alert('Dodaj co najmniej jeden produkt do dostawy');
    return false;
  }
  
  return true;
}

async function showDeliveryDetails(dostawaId) {
  const modal = document.getElementById('detailsModal');
  const detailsContainer = document.getElementById('deliveryDetails');
  
  try {
    const response = await fetch(`../includes/ajax/delivery_details.php?id=${dostawaId}`);
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
  document.getElementById('selectedDeliveryStatus').value = currentStatus;
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

function selectDeliveryStatus(status, text) {
  document.getElementById('selectedDeliveryStatus').value = status;
  document.getElementById('deliveryStatusDropdownText').textContent = text;
  document.getElementById('deliveryStatusDropdown').classList.remove('show');
}

function showDeliveryStatusModal(dostawaId, status) {
  document.getElementById('confirm_dostawa_id').value = dostawaId;
  document.getElementById('confirmDeliveryModal').style.display = 'block';
}

function closeConfirmDeliveryModal() {
  document.getElementById('confirmDeliveryModal').style.display = 'none';
}

// Zamykanie modali po kliknięciu poza nimi
window.onclick = function(event) {
  const deliveryModal = document.getElementById('deliveryModal');
  const detailsModal = document.getElementById('detailsModal');
  const statusModal = document.getElementById('statusModal');
  const confirmDeliveryModal = document.getElementById('confirmDeliveryModal');
  
  if (event.target == deliveryModal) {
    closeDeliveryModal();
  } else if (event.target == detailsModal) {
    closeDetailsModal();
  } else if (event.target == statusModal) {
    closeStatusModal();
  } else if (event.target == confirmDeliveryModal) {
    closeConfirmDeliveryModal();
  }
}
</script>