<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'add':
      $identyfikator = mysqli_real_escape_string($connection, $_POST['identyfikator']);
      $username = mysqli_real_escape_string($connection, $_POST['username']);
      $email = mysqli_real_escape_string($connection, $_POST['email']);
      $password = mysqli_real_escape_string($connection, $_POST['password']);
      $stanowisko = mysqli_real_escape_string($connection, $_POST['stanowisko']);
      
      // Najpierw dodajemy użytkownika
      $sql = "INSERT INTO uzytkownicy (nazwa_uzytkownika, email, haslo, typ) VALUES ('$username', '$email', '$password', 'pracownik')";
      if (mysqli_query($connection, $sql)) {
        $user_id = mysqli_insert_id($connection);
        
        // Następnie dodajemy pracownika
        $sql = "INSERT INTO pracownicy (uzytkownik_id, identyfikator, stanowisko_id) 
                SELECT '$user_id', '$identyfikator', id FROM stanowiska WHERE nazwa = '$stanowisko'";
        mysqli_query($connection, $sql);
      }
      header('Location: panel.php?view=employees&success=added');
      exit();
      break;
      
    case 'update':
      $employee_id = mysqli_real_escape_string($connection, $_POST['employee_id']);
      $identyfikator = mysqli_real_escape_string($connection, $_POST['identyfikator']);
      $username = mysqli_real_escape_string($connection, $_POST['username']);
      $email = mysqli_real_escape_string($connection, $_POST['email']);
      $stanowisko = mysqli_real_escape_string($connection, $_POST['stanowisko']);
      
      // Pobieramy ID użytkownika
      $result = mysqli_query($connection, "SELECT uzytkownik_id FROM pracownicy WHERE id = '$employee_id'");
      $user = mysqli_fetch_assoc($result);
      $user_id = $user['uzytkownik_id'];
      
      // Aktualizujemy dane użytkownika
      $sql = "UPDATE uzytkownicy SET nazwa_uzytkownika = '$username', email = '$email' WHERE id = '$user_id'";
      mysqli_query($connection, $sql);
      
      // Aktualizujemy dane pracownika
      $sql = "UPDATE pracownicy p 
              SET p.identyfikator = '$identyfikator', 
                  p.stanowisko_id = (SELECT id FROM stanowiska WHERE nazwa = '$stanowisko') 
              WHERE p.id = '$employee_id'";
      mysqli_query($connection, $sql);
      
      // Jeśli podano nowe hasło, aktualizujemy je
      if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($connection, $_POST['password']);
        mysqli_query($connection, "UPDATE uzytkownicy SET haslo = '$password' WHERE id = '$user_id'");
      }
      header('Location: panel.php?view=employees&success=updated');
      exit();
      break;
      
    case 'delete':
      $employee_id = mysqli_real_escape_string($connection, $_POST['employee_id']);
      
      // Pobieramy ID użytkownika
      $result = mysqli_query($connection, "SELECT uzytkownik_id FROM pracownicy WHERE id = '$employee_id'");
      $user = mysqli_fetch_assoc($result);
      $user_id = $user['uzytkownik_id'];
      
      // Usuwamy pracownika i użytkownika
      mysqli_query($connection, "DELETE FROM pracownicy WHERE id = '$employee_id'");
      mysqli_query($connection, "DELETE FROM uzytkownicy WHERE id = '$user_id'");
      
      header('Location: panel.php?view=employees&success=deleted');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'stanowisko';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'identyfikator', 'nazwa_uzytkownika', 'email', 'stanowisko', 'data_rejestracji'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'stanowisko';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=employees&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie pracowników z dodatkowymi informacjami
$sql = "SELECT p.*, u.nazwa_uzytkownika, u.email, u.data_rejestracji, s.nazwa as stanowisko, s.wynagrodzenie_miesieczne
        FROM pracownicy p
        JOIN uzytkownicy u ON p.uzytkownik_id = u.id
        JOIN stanowiska s ON p.stanowisko_id = s.id
        ORDER BY ";

// Dodanie odpowiedniego sortowania
if ($sort_column === 'nazwa_uzytkownika' || $sort_column === 'email' || $sort_column === 'data_rejestracji') {
    $sql .= "u.$sort_column $sort_dir";
} else if ($sort_column === 'stanowisko') {
    $sql .= "s.nazwa $sort_dir";
} else {
    $sql .= "p.$sort_column $sort_dir";
}

$pracownicy = mysqli_query($connection, $sql);
?>

<div class="admin-filters">
  <button class="admin-button success add" onclick="showAddEmployeeModal()">
    <i class="fas fa-plus"></i> Dodaj pracownika
  </button>
  <div class="admin-search">
    <input type="text" id="employeeSearch" class="form-input" placeholder="Szukaj pracowników..." 
           onkeyup="filterTable('employeeTable', 2)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('roleDropdown')">
      <span id="roleDropdownText">Wszystkie stanowiska</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="roleDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectRole('', 'Wszystkie stanowiska')">Wszystkie stanowiska</a></li>
      <li class="dropdown-divider"></li>
      <li><a href="#" class="dropdown-item" onclick="selectRole('pracownik', 'Pracownik')">Pracownik</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectRole('manager', 'Manager')">Manager</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectRole('sekretarka', 'Sekretarka')">Sekretarka</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectRole('informatyk', 'Informatyk')">Informatyk</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectRole('właściciel', 'Właściciel')">Właściciel</a></li>
    </ul>
  </div>
</div>

<div class="admin-table-wrapper">
<table id="employeeTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('identyfikator', $sort_column, $sort_dir); ?>" class="sort-link">
          Identyfikator <?php echo getSortIcon('identyfikator', $sort_column, $sort_dir); ?>
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
        <a href="<?php echo getSortLink('stanowisko', $sort_column, $sort_dir); ?>" class="sort-link">
          Stanowisko <?php echo getSortIcon('stanowisko', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('data_rejestracji', $sort_column, $sort_dir); ?>" class="sort-link">
          Data zatrudnienia <?php echo getSortIcon('data_rejestracji', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($employee = mysqli_fetch_assoc($pracownicy)) : ?>
      <tr data-role="<?php echo htmlspecialchars($employee['stanowisko']); ?>">
        <td><?php echo htmlspecialchars($employee['id']); ?></td>
        <td><?php echo htmlspecialchars($employee['identyfikator']); ?></td>
        <td><?php echo htmlspecialchars($employee['nazwa_uzytkownika']); ?></td>
        <td><?php echo htmlspecialchars($employee['email']); ?></td>
        <td>
          <span class="status-badge <?php echo strtolower($employee['stanowisko']); ?>">
            <?php echo ucfirst($employee['stanowisko']); ?>
          </span>
        </td>
        <td><?php echo date('d.m.Y', strtotime($employee['data_rejestracji'])); ?></td>
        <td>
          <div class="admin-actions">
            <button class="admin-button warning" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
            <?php if ($employee['stanowisko'] !== 'właściciel') : ?>
              <button class="admin-button danger" onclick="confirmDelete(<?php echo $employee['id']; ?>)">
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

<!-- Modal dodawania/edycji pracownika -->
<div id="employeeModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEmployeeModal()">&times;</span>
    <h2 id="modalTitle">Dodaj pracownika</h2>
    <form method="POST" onsubmit="return validateForm()">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="employee_id" id="employeeId">
      
      <div class="form-group">
        <label for="identyfikator" class="form-label">Identyfikator pracownika</label>
        <input type="text" id="identyfikator" name="identyfikator" class="form-input" required 
               pattern="[A-Za-z0-9]+" title="Tylko litery i cyfry">
      </div>
      
      <div class="form-group">
        <label for="username" class="form-label">Nazwa użytkownika</label>
        <input type="text" id="username" name="username" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="password" class="form-label">Hasło</label>
        <input type="password" id="password" name="password" class="form-input" 
               minlength="8">
        <small>Minimum 8 znaków</small>
      </div>
      
      <div class="form-group">
        <label for="password_confirm" class="form-label">Potwierdź hasło</label>
        <input type="password" id="password_confirm" class="form-input" 
               minlength="8">
      </div>
      
      <div class="form-group">
        <label for="stanowisko" class="form-label">Stanowisko</label>
        <select id="stanowisko" name="stanowisko" class="form-input" required>
          <?php
          $stanowiska_query = mysqli_query($connection, "SELECT nazwa FROM stanowiska ORDER BY nazwa");
          while ($stanowisko = mysqli_fetch_assoc($stanowiska_query)) {
            echo '<option value="' . htmlspecialchars($stanowisko['nazwa']) . '">' . 
                 ucfirst(htmlspecialchars($stanowisko['nazwa'])) . '</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeEmployeeModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDeleteModal()">&times;</span>
    <h2>Potwierdzenie usunięcia</h2>
    <p>Czy na pewno chcesz usunąć tego pracownika? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="employee_id" id="delete_employee_id">
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
function showAddEmployeeModal() {
  const modal = document.getElementById('employeeModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  const passwordField = document.getElementById('password');
  const passwordConfirmField = document.getElementById('password_confirm');
  
  modalTitle.textContent = 'Dodaj pracownika';
  document.getElementById('formAction').value = 'add';
  document.getElementById('employeeId').value = '';
  form.reset();
  
  passwordField.required = true;
  passwordConfirmField.required = true;
  
  modal.style.display = 'block';
}

function editEmployee(employee) {
  const modal = document.getElementById('employeeModal');
  const modalTitle = document.getElementById('modalTitle');
  const passwordField = document.getElementById('password');
  const passwordConfirmField = document.getElementById('password_confirm');
  
  modalTitle.textContent = 'Edytuj pracownika';
  document.getElementById('formAction').value = 'update';
  document.getElementById('employeeId').value = employee.id;
  document.getElementById('identyfikator').value = employee.identyfikator;
  document.getElementById('username').value = employee.nazwa_uzytkownika;
  document.getElementById('email').value = employee.email;
  document.getElementById('stanowisko').value = employee.stanowisko;
  
  // Hasło nie jest wymagane przy edycji
  passwordField.required = false;
  passwordConfirmField.required = false;
  passwordField.value = '';
  passwordConfirmField.value = '';
  
  modal.style.display = 'block';
}

function confirmDelete(employeeId) {
  document.getElementById('delete_employee_id').value = employeeId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeEmployeeModal() {
  document.getElementById('employeeModal').style.display = 'none';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

function validateForm() {
  const formAction = document.getElementById('formAction').value;
  const password = document.getElementById('password').value;
  const passwordConfirm = document.getElementById('password_confirm').value;
  
  // Przy dodawaniu hasło jest wymagane
  if (formAction === 'add' && (password === '' || password.length < 8)) {
    alert('Hasło musi mieć co najmniej 8 znaków');
    return false;
  }
  
  // Przy edycji hasło jest opcjonalne, ale jeśli podano to musi mieć 8 znaków
  if (formAction === 'update' && password !== '' && password.length < 8) {
    alert('Hasło musi mieć co najmniej 8 znaków');
    return false;
  }
  
  // Sprawdzenie zgodności haseł
  if (password !== '' && password !== passwordConfirm) {
    alert('Hasła nie są zgodne');
    return false;
  }
  
  return true;
}

function selectRole(role, roleText) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('roleDropdownText');
  button.textContent = roleText;
  button.dataset.selectedId = role;
  
  // Filtruj pracowników
  filterByRole(role);
  
  // Ukryj dropdown
  document.getElementById('roleDropdown').classList.remove('show');
}

function filterByRole(role) {
  const rows = document.querySelectorAll('#employeeTable tbody tr');
  rows.forEach(row => {
    if (!role || row.dataset.role === role) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
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

function filterTable(tableId, columnIndex) {
  const input = document.getElementById('employeeSearch');
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
  const employeeModal = document.getElementById('employeeModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == employeeModal) {
    closeEmployeeModal();
  } else if (event.target == deleteModal) {
    closeDeleteModal();
  }
}
</script>