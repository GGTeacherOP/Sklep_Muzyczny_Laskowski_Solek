<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {    
    case 'delete':
      $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
      
      // Najpierw usuwamy powiązane rekordy
      mysqli_query($connection, "DELETE FROM koszyk WHERE klient_id = '$client_id'");
      mysqli_query($connection, "DELETE FROM zamowienia WHERE klient_id = '$client_id'");
      
      // Pobieramy ID użytkownika
      $result = mysqli_query($connection, "SELECT uzytkownik_id FROM klienci WHERE id = '$client_id'");
      $user = mysqli_fetch_assoc($result);
      $user_id = $user['uzytkownik_id'];
      
      // Usuwamy klienta i użytkownika
      mysqli_query($connection, "DELETE FROM klienci WHERE id = '$client_id'");
      mysqli_query($connection, "DELETE FROM uzytkownicy WHERE id = '$user_id'");
      
      header('Location: panel.php?view=customers&success=deleted');
      exit();
      break;
      
    case 'update':
      $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
      $email = mysqli_real_escape_string($connection, $_POST['email']);
      $username = mysqli_real_escape_string($connection, $_POST['username']);
      
      // Pobieramy ID użytkownika
      $result = mysqli_query($connection, "SELECT uzytkownik_id FROM klienci WHERE id = '$client_id'");
      $user = mysqli_fetch_assoc($result);
      $user_id = $user['uzytkownik_id'];
      
      // Aktualizujemy dane użytkownika
      mysqli_query($connection, "UPDATE uzytkownicy SET email = '$email', nazwa_uzytkownika = '$username' WHERE id = '$user_id'");
      
      header('Location: panel.php?view=customers&success=updated');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'nazwa_uzytkownika';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'nazwa_uzytkownika', 'email', 'data_rejestracji', 'liczba_zamowien', 'wartosc_zamowien'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'nazwa_uzytkownika';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=clients&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie klientów z dodatkowymi informacjami
$sql = "SELECT k.id, u.nazwa_uzytkownika, u.email, u.data_rejestracji,
        (SELECT COUNT(*) FROM zamowienia z WHERE z.klient_id = k.id) as liczba_zamowien,
        (SELECT SUM(zs.ilosc * zs.cena) 
         FROM zamowienie_szczegoly zs 
         JOIN zamowienia z ON zs.zamowienie_id = z.id 
         WHERE z.klient_id = k.id) as wartosc_zamowien
        FROM klienci k
        JOIN uzytkownicy u ON k.uzytkownik_id = u.id";

// Dodanie odpowiedniego sortowania
if ($sort_column === 'nazwa_uzytkownika' || $sort_column === 'email' || $sort_column === 'data_rejestracji') {
    $sql .= " ORDER BY u.$sort_column $sort_dir";
} elseif ($sort_column === 'id') {
    $sql .= " ORDER BY k.id $sort_dir";
} else {
    $sql .= " ORDER BY $sort_column $sort_dir";
}

$klienci = mysqli_query($connection, $sql);
?>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="clientSearch" class="form-input" placeholder="Szukaj klientów..." 
           onkeyup="filterTable('clientTable', 1)">
  </div>
</div>

<div class="admin-table-wrapper">
<table id="clientTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('nazwa_uzytkownika', $sort_column, $sort_dir); ?>" class="sort-link">
          Nazwa użytkownika <?php echo getSortIcon('nazwa_uzytkownika', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('email', $sort_column, $sort_dir); ?>" class="sort-link">
          Email <?php echo getSortIcon('email', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('data_rejestracji', $sort_column, $sort_dir); ?>" class="sort-link">
          Data rejestracji <?php echo getSortIcon('data_rejestracji', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('liczba_zamowien', $sort_column, $sort_dir); ?>" class="sort-link">
          Liczba zamówień <?php echo getSortIcon('liczba_zamowien', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('wartosc_zamowien', $sort_column, $sort_dir); ?>" class="sort-link">
          Wartość zamówień <?php echo getSortIcon('wartosc_zamowien', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($client = mysqli_fetch_assoc($klienci)) : ?>
      <tr>
        <td><?php echo htmlspecialchars($client['id']); ?></td>
        <td><?php echo htmlspecialchars($client['nazwa_uzytkownika']); ?></td>
        <td><?php echo htmlspecialchars($client['email']); ?></td>
        <td><?php echo date('d.m.Y', strtotime($client['data_rejestracji'])); ?></td>
        <td><?php echo htmlspecialchars($client['liczba_zamowien']); ?></td>
        <td><?php echo $client['wartosc_zamowien'] ? number_format($client['wartosc_zamowien'], 2) . ' zł' : '0.00 zł'; ?></td>
        <td>
          <div class="admin-actions">
            <button class="admin-button success" onclick="showClientOrders(<?php echo $client['id']; ?>)">
              <i class="fas fa-shopping-cart"></i>
            </button>
            <button class="admin-button warning" onclick="editClient(<?php echo htmlspecialchars(json_encode($client)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="admin-button danger" onclick="confirmDelete(<?php echo $client['id']; ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal z historią zamówień -->
<div id="ordersModal" class="modal">
  <div class="modal-content">
    <h2>Historia zamówień klienta</h2>
    <div id="ordersContent">
      <p class="loading">Ładowanie zamówień...</p>
    </div>
    <div class="admin-actions">
      <button type="button" class="admin-button" onclick="closeOrdersModal()">
        <i class="fas fa-times"></i> Zamknij
      </button>
    </div>
  </div>
</div>

<!-- Modal z edycją klienta -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <h2>Edytuj dane klienta</h2>
    <form method="POST" id="editForm">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="client_id" id="editClientId">
      <input type="hidden" name="status" id="selectedStatus">
      
      <div class="form-group">
        <label for="username" class="form-label">Nazwa użytkownika</label>
        <input type="text" id="editUsername" name="username" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="editEmail" name="email" class="form-input" required>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeEditModal()">
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
    <p>Czy na pewno chcesz usunąć tego klienta? Ta operacja jest nieodwracalna i spowoduje usunięcie wszystkich danych klienta, w tym zamówień i historii.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="client_id" id="delete_client_id">
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
function showClientOrders(clientId) {
  document.getElementById('ordersContent').innerHTML = '<p class="loading">Ładowanie zamówień...</p>';
  document.getElementById('ordersModal').style.display = 'block';
  
  // Pobierz zamówienia klienta przez AJAX
  fetch(`../includes/ajax/get_client_orders.php?client_id=${clientId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('ordersContent').innerHTML = data.html;
      } else {
        document.getElementById('ordersContent').innerHTML = 
          `<div class="admin-alert error">${data.message || 'Wystąpił błąd podczas pobierania historii zamówień'}</div>`;
      }
    })
    .catch(error => {
      document.getElementById('ordersContent').innerHTML = 
        '<div class="admin-alert error">Wystąpił błąd podczas pobierania historii zamówień</div>';
      console.error('Error:', error);
    });
}

function selectStatus(value, text) {
  document.getElementById('selectedStatus').value = value;
  document.getElementById('statusDropdownText').textContent = text;
  document.getElementById('statusDropdown').classList.remove('show');
}

function editClient(client) {
  document.getElementById('editClientId').value = client.id;
  document.getElementById('editUsername').value = client.nazwa_uzytkownika;
  document.getElementById('editEmail').value = client.email;
  
  // Otwórz modal
  document.getElementById('editModal').style.display = 'block';
}

function confirmDelete(clientId) {
  document.getElementById('delete_client_id').value = clientId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeOrdersModal() {
  document.getElementById('ordersModal').style.display = 'none';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

function filterTable(tableId, columnIndex) {
  const input = document.getElementById('clientSearch');
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
  const ordersModal = document.getElementById('ordersModal');
  const editModal = document.getElementById('editModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == ordersModal) {
    closeOrdersModal();
  } else if (event.target == editModal) {
    closeEditModal();
  } else if (event.target == deleteModal) {
    closeDeleteModal();
  }
}
</script>