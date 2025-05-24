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

// Stałe dla modułu zamówień
const ORDER_STATUSES = [
    'w przygotowaniu' => [
        'label' => 'W przygotowaniu',
        'class' => 'status-badge warning'
    ],
    'wysłane' => [
        'label' => 'Wysłane',
        'class' => 'status-badge info'
    ],
    'dostarczone' => [
        'label' => 'Dostarczone',
        'class' => 'status-badge success'
    ],
    'anulowane' => [
        'label' => 'Anulowane',
        'class' => 'status-badge danger'
    ]
];

// Dozwolone kolumny sortowania
const ORDER_SORT_COLUMNS = [
    'id', 
    'nazwa_uzytkownika', 
    'data_zamowienia', 
    'status', 
    'wartosc_calkowita'
];

// Obsługa akcji na zamówieniach
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete':
        // Tylko informatyk i właściciel mogą usuwać zamówienia
        if ($role === 'informatyk' || $role === 'właściciel') {
          if (isset($_POST['order_id'])) {
            $order_id = mysqli_real_escape_string($connection, $_POST['order_id']);
            
            // Rozpoczęcie transakcji
            mysqli_begin_transaction($connection);
            
            try {
                // Najpierw usuwamy szczegóły zamówienia
                $result1 = mysqli_query($connection, "DELETE FROM zamowienie_szczegoly WHERE zamowienie_id = '$order_id'");
                
                // Następnie usuwamy samo zamówienie
                $result2 = mysqli_query($connection, "DELETE FROM zamowienia WHERE id = '$order_id'");
                
                // Jeśli obie operacje się powiodły, zatwierdzamy transakcję
                if ($result1 && $result2) {
                    mysqli_commit($connection);
                } else {
                    mysqli_rollback($connection);
                }
            } catch (Exception $e) {
                mysqli_rollback($connection);
            }
          }
        }
        break;
      case 'update_status':
        if (isset($_POST['order_id']) && isset($_POST['status'])) {
          $order_id = mysqli_real_escape_string($connection, $_POST['order_id']);
          $status = mysqli_real_escape_string($connection, $_POST['status']);
          
          // Sprawdzenie czy status jest prawidłowy
          if (array_key_exists($status, ORDER_STATUSES)) {
            // Pracownik nie może ustawiać statusu 'anulowane' ani 'dostarczone'
            if ($role === 'pracownik' && ($status === 'anulowane' || $status === 'dostarczone')) {
              // Przekieruj bez zmiany statusu
              header('Location: panel.php?view=orders&error=permission_denied');
              exit();
            } else {
              $sql = "UPDATE zamowienia SET status = '$status' WHERE id = '$order_id'";
              mysqli_query($connection, $sql);
              header('Location: panel.php?view=orders&success=updated');
              exit();
            }
          }
        }
        break;
    }
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'data_zamowienia';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
if (!in_array($sort_column, ORDER_SORT_COLUMNS)) {
    $sort_column = 'data_zamowienia';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=orders&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Sprawdzenie, czy mamy podgląd szczegółów
if (isset($_GET['view_details']) && is_numeric($_GET['view_details'])) {
    $order_id = $_GET['view_details'];
    
    // Pobieranie szczegółów zamówienia
    $order_sql = "SELECT z.*, u.nazwa_uzytkownika, u.email, 
                 (SELECT SUM(zs.ilosc * zs.cena) FROM zamowienie_szczegoly zs WHERE zs.zamowienie_id = z.id) as wartosc_calkowita
                 FROM zamowienia z
                 JOIN klienci k ON z.klient_id = k.id
                 JOIN uzytkownicy u ON k.uzytkownik_id = u.id
                 WHERE z.id = '$order_id'";
    
    $order_result = mysqli_query($connection, $order_sql);
    $order = mysqli_fetch_assoc($order_result);
    
    if ($order) {
        // Pobieranie elementów zamówienia
        $items_sql = "SELECT zs.*, i.nazwa as produkt_nazwa, i.kod_produktu
                     FROM zamowienie_szczegoly zs
                     JOIN instrumenty i ON zs.instrument_id = i.id
                     WHERE zs.zamowienie_id = '$order_id'";
        
        $items_result = mysqli_query($connection, $items_sql);
        $order_items = [];
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $order_items[] = $item;
        }
        
        // Wyświetlanie szczegółów zamówienia
        ?>
        <div class="order-details-container">
          <div class="order-info">
            <div class="info-group">
              <h3>Informacje o zamówieniu</h3>
              <p><strong>ID zamówienia:</strong> <?php echo $order['id']; ?></p>
              <p><strong>Data złożenia:</strong> <?php echo date('d.m.Y H:i', strtotime($order['data_zamowienia'])); ?></p>
              <p><strong>Status:</strong> <span class="<?php echo ORDER_STATUSES[$order['status']]['class']; ?>"><?php echo $order['status']; ?></span></p>
              <p><strong>Wartość całkowita:</strong> <?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</p>
            </div>
            
            <div class="info-group">
              <h3>Informacje o kliencie</h3>
              <p><strong>Klient:</strong> <?php echo $order['nazwa_uzytkownika']; ?></p>
              <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
              <p><strong>Adres:</strong> <?php echo $order['adres_wysylki']; ?></p>
            </div>
          </div>
          
          <h3>Pozycje zamówienia</h3>
          <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Produkt</th>
                <th>Kod produktu</th>
                <th>Ilość</th>
                <th>Cena</th>
                <th>Suma</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($order_items as $item): ?>
                <tr>
                  <td><?php echo $item['id']; ?></td>
                  <td><?php echo $item['produkt_nazwa']; ?></td>
                  <td><?php echo $item['kod_produktu']; ?></td>
                  <td><?php echo $item['ilosc']; ?></td>
                  <td><?php echo number_format($item['cena'], 2); ?> zł</td>
                  <td><?php echo number_format($item['ilosc'] * $item['cena'], 2); ?> zł</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
          
          <div class="admin-actions">
            <?php if ($role === 'informatyk' || $role === 'właściciel'): ?>
            <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
              <i class="fas fa-trash"></i> Usuń zamówienie
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php
    } else {
        echo '<div class="admin-alert error">Nie znaleziono zamówienia o podanym ID.</div>';
    }
} else {
    // Pobranie zamówień z dodatkowymi informacjami
    $sql = "SELECT z.*, 
            u.nazwa_uzytkownika,
            (SELECT SUM(zs.ilosc * zs.cena) FROM zamowienie_szczegoly zs WHERE zs.zamowienie_id = z.id) as wartosc_calkowita
            FROM zamowienia z
            JOIN klienci k ON z.klient_id = k.id
            JOIN uzytkownicy u ON k.uzytkownik_id = u.id";
    
    // Dodanie odpowiedniego sortowania
    if ($sort_column === 'nazwa_uzytkownika') {
        $sql .= " ORDER BY u.$sort_column $sort_dir";
    } else if ($sort_column === 'wartosc_calkowita') {
        $sql .= " ORDER BY wartosc_calkowita $sort_dir";
    } else {
        $sql .= " ORDER BY z.$sort_column $sort_dir";
    }
    
    $orders = mysqli_query($connection, $sql);
    ?>
    <div class="admin-filters">
      <div class="admin-search">
        <input type="text" id="orderSearch" class="form-input" placeholder="Szukaj zamówień..." 
               onkeyup="filterTable('orderTable', 'orderSearch', 1)">
      </div>
      <div class="dropdown">
        <button class="dropdown-toggle" type="button" onclick="toggleDropdown('statusDropdown')">
          <span id="statusDropdownText">Wszystkie statusy</span>
          <i class="fa-solid fa-chevron-down"></i>
        </button>
        <ul class="dropdown-menu" id="statusDropdown">
          <li><a href="#" class="dropdown-item" onclick="selectStatus('', 'Wszystkie statusy')">Wszystkie statusy</a></li>
          <li class="dropdown-divider"></li>
          <?php foreach (ORDER_STATUSES as $value => $status): ?>
            <li><a href="#" class="dropdown-item" onclick="selectStatus('<?php echo $value; ?>', '<?php echo $status['label']; ?>')">
              <?php echo $status['label']; ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </div>
        <input type="date" class="form-input date-input" id="dateFrom" name="date_from" 
               value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>" placeholder="Od">
        <input type="date" class="form-input date-input" id="dateTo" name="date_to" 
               value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>" placeholder="Do">
        <button class="admin-button" onclick="filterByDate()">
          <i class="fas fa-filter"></i> Filtruj
        </button>
    </div>

<div class="admin-table-wrapper">
    <table id="orderTable" class="admin-table">
      <thead>
        <tr>
          <th>
            <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
              ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
            </a>
          </th>
          <th>
            <a href="<?php echo getSortLink('nazwa_uzytkownika', $sort_column, $sort_dir); ?>" class="sort-link">
              Klient <?php echo getSortIcon('nazwa_uzytkownika', $sort_column, $sort_dir); ?>
            </a>
          </th>
          <th>
            <a href="<?php echo getSortLink('data_zamowienia', $sort_column, $sort_dir); ?>" class="sort-link">
              Data zamówienia <?php echo getSortIcon('data_zamowienia', $sort_column, $sort_dir); ?>
            </a>
          </th>
          <th>
            <a href="<?php echo getSortLink('status', $sort_column, $sort_dir); ?>" class="sort-link">
              Status <?php echo getSortIcon('status', $sort_column, $sort_dir); ?>
            </a>
          </th>
          <th>
            <a href="<?php echo getSortLink('wartosc_calkowita', $sort_column, $sort_dir); ?>" class="sort-link">
              Wartość <?php echo getSortIcon('wartosc_calkowita', $sort_column, $sort_dir); ?>
            </a>
          </th>
          <th>Akcje</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
          <tr data-status="<?php echo htmlspecialchars($order['status']); ?>"
              data-date="<?php echo date('Y-m-d', strtotime($order['data_zamowienia'])); ?>">
            <td><?php echo htmlspecialchars($order['id']); ?></td>
            <td><?php echo htmlspecialchars($order['nazwa_uzytkownika']); ?></td>
            <td><?php echo date('d.m.Y H:i', strtotime($order['data_zamowienia'])); ?></td>
            <td>
              <span class="<?php echo ORDER_STATUSES[$order['status']]['class']; ?>">
                <?php echo htmlspecialchars($order['status']); ?>
              </span>
            </td>
            <td><?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</td>
            <td>
              <div class="admin-actions">
                <a href="?view=orders&view_details=<?php echo $order['id']; ?>" class="admin-button info">
                  <i class="fas fa-eye"></i>
                </a>
                <button class="admin-button warning" onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                  <i class="fas fa-edit"></i>
                </button>
                <?php if ($role === 'informatyk' || $role === 'właściciel'): ?>
                <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
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
    <?php
}
?>

<!-- Modal do zmiany statusu zamówienia -->
<div id="statusChangeModal" class="modal">
  <div class="modal-content">
    <h2>Zmień status zamówienia</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="order_id" id="order_id">
      <input type="hidden" name="status" id="selectedStatus">
      
      <div class="form-group">
        <label for="order_status" class="form-label">Status zamówienia</label>
        <div class="dropdown">
          <button type="button" class="dropdown-toggle" onclick="toggleDropdown('modalStatusDropdown')">
            <span id="modalStatusDropdownText">Wybierz status</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="modalStatusDropdown">
            <?php foreach (ORDER_STATUSES as $status => $info): ?>
              <li><a href="#" class="dropdown-item" onclick="selectModalStatus('<?php echo $status; ?>', '<?php echo $info['label']; ?>')">
                <?php echo $info['label']; ?>
              </a></li>
            <?php endforeach; ?>
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

<!-- Modal potwierdzenia usunięcia -->
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

<script>
function editOrderStatus(id, status) {
  document.getElementById('order_id').value = id;
  document.getElementById('selectedStatus').value = status;
  document.getElementById('modalStatusDropdownText').textContent = ORDER_STATUSES[status].label;

  // Jeśli pracownik, ukryj opcje 'anulowane' i 'dostarczone'
  const isWorker = '<?php echo $role; ?>' === 'pracownik';
  if (isWorker) {
    const options = document.querySelectorAll('#modalStatusDropdown .dropdown-item');
    options.forEach(option => {
      if (option.textContent.trim() === 'Anulowane' || option.textContent.trim() === 'Dostarczone') {
        option.style.display = 'none';
      } else {
        option.style.display = '';
      }
    });
  }

  document.getElementById('statusChangeModal').style.display = 'block';
}

function closeStatusModal() {
  document.getElementById('statusChangeModal').style.display = 'none';
}

function confirmDelete(id) {
  document.getElementById('delete_order_id').value = id;
  document.getElementById('deleteConfirmationModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteConfirmationModal').style.display = 'none';
}

function filterTable(tableId, inputId, columnIndex) {
  const input = document.getElementById(inputId);
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName('tr');
  const statusFilter = document.getElementById('statusDropdownText').dataset.selectedId || '';
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const cell = row.getElementsByTagName('td')[columnIndex];
    if (cell) {
      const text = cell.textContent || cell.innerText;
      const matchesSearch = text.toLowerCase().indexOf(filter) > -1;
      const matchesStatus = !statusFilter || row.dataset.status === statusFilter;
      const matchesDate = filterByDateRange(row, dateFrom, dateTo);
      
      row.style.display = matchesSearch && matchesStatus && matchesDate ? '' : 'none';
    }
  }
}

function filterByStatus(status) {
  const rows = document.querySelectorAll('#orderTable tbody tr');
  const searchFilter = document.getElementById('orderSearch').value.toLowerCase();
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;

  rows.forEach(row => {
    const matchesStatus = !status || row.dataset.status === status;
    const matchesSearch = !searchFilter || row.getElementsByTagName('td')[1].textContent.toLowerCase().indexOf(searchFilter) > -1;
    const matchesDate = filterByDateRange(row, dateFrom, dateTo);
    
    row.style.display = matchesStatus && matchesSearch && matchesDate ? '' : 'none';
  });
}

function filterByDate() {
  const rows = document.querySelectorAll('#orderTable tbody tr');
  const searchFilter = document.getElementById('orderSearch').value.toLowerCase();
  const statusFilter = document.getElementById('statusDropdownText').dataset.selectedId || '';
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;

  rows.forEach(row => {
    const matchesSearch = !searchFilter || row.getElementsByTagName('td')[1].textContent.toLowerCase().indexOf(searchFilter) > -1;
    const matchesStatus = !statusFilter || row.dataset.status === statusFilter;
    const matchesDate = filterByDateRange(row, dateFrom, dateTo);
    
    row.style.display = matchesSearch && matchesStatus && matchesDate ? '' : 'none';
  });
}

function filterByDateRange(row, dateFrom, dateTo) {
  if (!dateFrom && !dateTo) return true;
  
  const orderDate = new Date(row.dataset.date);
  const fromDate = dateFrom ? new Date(dateFrom) : null;
  const toDate = dateTo ? new Date(dateTo) : null;
  
  let show = true;
  if (fromDate && orderDate < fromDate) show = false;
  if (toDate && orderDate > toDate) show = false;
  
  return show;
}

function selectStatus(status, label) {
  document.getElementById('selectedStatus').value = status;
  document.getElementById('statusDropdownText').textContent = label;
  document.getElementById('statusDropdown').classList.remove('show');
}

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

function selectModalStatus(status, label) {
  document.getElementById('selectedStatus').value = status;
  document.getElementById('modalStatusDropdownText').textContent = label;
  document.getElementById('modalStatusDropdown').classList.remove('show');
}

// Modyfikacja obsługi kliknięcia poza dropdownem
document.addEventListener('click', function(event) {
  const dropdowns = document.querySelectorAll('.dropdown-menu');
  const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
  
  let clickedOnDropdown = false;
  
  // Sprawdź czy kliknięto na dropdown lub jego zawartość
  dropdowns.forEach(dropdown => {
    if (dropdown.contains(event.target)) {
      clickedOnDropdown = true;
    }
  });
  
  // Sprawdź czy kliknięto na przycisk dropdown
  dropdownToggles.forEach(toggle => {
    if (toggle.contains(event.target)) {
      clickedOnDropdown = true;
    }
  });
  
  // Jeśli nie kliknięto na dropdown ani jego przycisk, zamknij wszystkie dropdowny
  if (!clickedOnDropdown) {
    dropdowns.forEach(dropdown => {
      dropdown.classList.remove('show');
    });
  }
});

// Zamykanie modali po kliknięciu poza nimi
window.onclick = function(event) {
  const statusModal = document.getElementById('statusChangeModal');
  const deleteModal = document.getElementById('deleteConfirmationModal');
  
  if (event.target == statusModal) {
    closeStatusModal();
  }
  if (event.target == deleteModal) {
    closeDeleteModal();
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Obsługa komunikatów
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('success') === 'deleted') {
    alert('Zamówienie zostało pomyślnie usunięte.');
  } else if (urlParams.get('success') === 'updated') {
    alert('Status zamówienia został pomyślnie zaktualizowany.');
  } else if (urlParams.get('error') === 'permission_denied') {
    alert('Nie masz uprawnień do wykonania tej operacji.');
  }
});

// Dodanie stałej z informacjami o statusach dla JavaScript
const ORDER_STATUSES = <?php echo json_encode(ORDER_STATUSES); ?>;
</script>