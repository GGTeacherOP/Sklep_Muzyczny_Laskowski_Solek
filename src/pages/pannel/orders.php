<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

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
        break;
      case 'update_status':
        if (isset($_POST['order_id']) && isset($_POST['status'])) {
          $order_id = mysqli_real_escape_string($connection, $_POST['order_id']);
          $status = mysqli_real_escape_string($connection, $_POST['status']);
          
          // Sprawdzenie czy status jest prawidłowy
          if (array_key_exists($status, ORDER_STATUSES)) {
              $sql = "UPDATE zamowienia SET status = '$status' WHERE id = '$order_id'";
              mysqli_query($connection, $sql);
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
        <div class="admin-actions">
          <a href="?view=orders" class="admin-button">
            <i class="fas fa-arrow-left"></i> Powrót do listy
          </a>
        </div>
        
        <div class="order-details-container">
          <h2>Szczegóły zamówienia #<?php echo $order['id']; ?></h2>
          
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
            </div>
          </div>
          
          <h3>Pozycje zamówienia</h3>
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
          
          <div class="order-summary">
            <div class="order-summary-item total">
              <div>Razem:</div>
              <div><?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</div>
            </div>
          </div>
          
          <div class="admin-actions">
            <button class="admin-button warning" onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
              <i class="fas fa-edit"></i> Zmień status
            </button>
            <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
              <i class="fas fa-trash"></i> Usuń zamówienie
            </button>
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
      <button class="admin-button success add" onclick="showAddOrderModal()">
        <i class="fas fa-plus"></i> Dodaj zamówienie
      </button>
      <div class="admin-search">
        <input type="text" id="orderSearch" class="form-input" placeholder="Szukaj zamówień..." 
               onkeyup="filterTable('orderTable', 'orderSearch', 1)">
      </div>
      <select class="form-input" id="statusFilter" onchange="filterByStatus(this.value)">
        <option value="">Wszystkie statusy</option>
        <?php foreach (ORDER_STATUSES as $value => $status): ?>
        <option value="<?php echo $value; ?>"><?php echo $status['label']; ?></option>
        <?php endforeach; ?>
      </select>
      <div class="date-range">
        <input type="date" class="form-input" id="dateFrom" name="date_from" 
               value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>" placeholder="Od">
        <input type="date" class="form-input" id="dateTo" name="date_to" 
               value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>" placeholder="Do">
        <button class="admin-button" onclick="filterByDate()">
          <i class="fas fa-filter"></i> Filtruj
        </button>
      </div>
    </div>
    
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
          <tr data-status="<?php echo htmlspecialchars($order['status']); ?>">
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
                <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php
}
?>

<!-- Modal do zmiany statusu zamówienia -->
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
  document.getElementById('order_status').value = status;
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

  for (let i = 1; i < rows.length; i++) {
    const cell = rows[i].getElementsByTagName('td')[columnIndex];
    if (cell) {
      const text = cell.textContent || cell.innerText;
      rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
  }
}

function filterByStatus(status) {
  const table = document.getElementById('orderTable');
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    if (!status) {
      rows[i].style.display = '';
    } else {
      const orderStatus = rows[i].getAttribute('data-status');
      rows[i].style.display = orderStatus === status ? '' : 'none';
    }
  }
}

function filterByDate() {
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;
  const table = document.getElementById('orderTable');
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const dateCell = row.getElementsByTagName('td')[2]; // Zakładając, że data jest w trzeciej kolumnie
    if (dateCell) {
      const orderDate = new Date(dateCell.textContent);
      const fromDate = dateFrom ? new Date(dateFrom) : null;
      const toDate = dateTo ? new Date(dateTo) : null;

      let show = true;
      if (fromDate && orderDate < fromDate) show = false;
      if (toDate && orderDate > toDate) show = false;

      row.style.display = show ? '' : 'none';
    }
  }
}

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
  }
});
</script>