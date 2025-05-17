<?php
/** @var mysqli $connection */

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'update_status':
      $message_id = mysqli_real_escape_string($connection, $_POST['message_id']);
      $new_status = mysqli_real_escape_string($connection, $_POST['status']);
      
      $sql = "UPDATE wiadomosci SET status = '$new_status' WHERE id = '$message_id'";
      mysqli_query($connection, $sql);
      
      header('Location: panel.php?view=messages&success=updated');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'data_wyslania';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'email', 'temat', 'data_wyslania', 'status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_wyslania';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=messages&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie wiadomości
$sql = "SELECT * FROM wiadomosci ORDER BY $sort_column $sort_dir";
$wiadomosci = mysqli_query($connection, $sql);
?>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="messageSearch" class="form-input" placeholder="Szukaj wiadomości..." 
           onkeyup="filterTable('messageTable', 2)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('statusDropdown')">
      <span id="statusDropdownText">Wszystkie statusy</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="statusDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectStatus('', 'Wszystkie statusy')">Wszystkie statusy</a></li>
      <li class="dropdown-divider"></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('nowa', 'Nowe')">Nowe</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('w_trakcie', 'W trakcie')">W trakcie</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('zakonczona', 'Zakończone')">Zakończone</a></li>
      <li><a href="#" class="dropdown-item" onclick="selectStatus('archiwalna', 'Archiwalne')">Archiwalne</a></li>
    </ul>
  </div>
</div>

<div class="admin-table-wrapper">
<table id="messageTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('email', $sort_column, $sort_dir); ?>" class="sort-link">
          Email <?php echo getSortIcon('email', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('temat', $sort_column, $sort_dir); ?>" class="sort-link">
          Temat <?php echo getSortIcon('temat', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Treść</th>
      <th>
        <a href="<?php echo getSortLink('data_wyslania', $sort_column, $sort_dir); ?>" class="sort-link">
          Data wysłania <?php echo getSortIcon('data_wyslania', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('status', $sort_column, $sort_dir); ?>" class="sort-link">
          Status <?php echo getSortIcon('status', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($message = mysqli_fetch_assoc($wiadomosci)) : ?>
      <tr data-status="<?php echo htmlspecialchars($message['status']); ?>">
        <td><?php echo htmlspecialchars($message['id']); ?></td>
        <td><?php echo htmlspecialchars($message['email']); ?></td>
        <td><?php echo htmlspecialchars($message['temat']); ?></td>
        <td>
          <button class="admin-button" onclick="showMessageContent('<?php echo htmlspecialchars(addslashes($message['tresc'])); ?>')">
            <i class="fas fa-eye"></i> Pokaż treść
          </button>
        </td>
        <td><?php echo date('d.m.Y H:i', strtotime($message['data_wyslania'])); ?></td>
        <td>
          <span class="status-badge <?php echo $message['status']; ?>">
            <?php
              $statusy = [
                'nowa' => 'Nowa',
                'w_trakcie' => 'W trakcie',
                'zakonczona' => 'Zakończona',
                'archiwalna' => 'Archiwalna'
              ];
              echo $statusy[$message['status']] ?? $message['status'];
            ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <button class="admin-button warning" onclick="showStatusModal(<?php echo $message['id']; ?>, '<?php echo $message['status']; ?>')">
              <i class="fas fa-edit"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal treści wiadomości -->
<div id="messageModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeMessageModal()">&times;</span>
    <h2>Treść wiadomości</h2>
    <div id="messageContent" class="message-content"></div>
    <div class="admin-actions">
      <button type="button" class="admin-button" onclick="closeMessageModal()">
        <i class="fas fa-times"></i> Zamknij
      </button>
    </div>
  </div>
</div>

<!-- Modal zmiany statusu -->
<div id="statusModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeStatusModal()">&times;</span>
    <h2>Zmień status wiadomości</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="message_id" id="status_message_id">
      
      <div class="form-group">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-input" required>
          <option value="nowa">Nowa</option>
          <option value="w_trakcie">W trakcie</option>
          <option value="zakonczona">Zakończona</option>
          <option value="archiwalna">Archiwalna</option>
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

function showMessageContent(content) {
  const modal = document.getElementById('messageModal');
  document.getElementById('messageContent').textContent = content;
  modal.style.display = 'block';
}

function closeMessageModal() {
  document.getElementById('messageModal').style.display = 'none';
}

function showStatusModal(messageId, currentStatus) {
  const modal = document.getElementById('statusModal');
  document.getElementById('status_message_id').value = messageId;
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
  
  // Filtruj wiadomości
  filterByStatus(status);
  
  // Ukryj dropdown
  document.getElementById('statusDropdown').classList.remove('show');
}

function filterByStatus(status) {
  const rows = document.querySelectorAll('#messageTable tbody tr');
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
  const messageModal = document.getElementById('messageModal');
  const statusModal = document.getElementById('statusModal');
  
  if (event.target == messageModal) {
    closeMessageModal();
  } else if (event.target == statusModal) {
    closeStatusModal();
  }
}
</script>

<style>
.message-content {
  white-space: pre-wrap;
  max-height: 400px;
  overflow-y: auto;
  padding: 15px;
  background: var(--background-secondary);
  border-radius: 4px;
  margin: 10px 0;
}

.status-nowa {
  background-color: #f44336;
}

.status-w_trakcie {
  background-color: #ff9800;
}

.status-zakonczona {
  background-color: #4caf50;
}

.status-archiwalna {
  background-color: #9e9e9e;
}
</style> 