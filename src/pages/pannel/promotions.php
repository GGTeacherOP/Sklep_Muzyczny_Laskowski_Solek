<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'add':
      $kod = mysqli_real_escape_string($connection, $_POST['kod']);
      $znizka = mysqli_real_escape_string($connection, $_POST['znizka']);
      $data_rozpoczecia = mysqli_real_escape_string($connection, $_POST['data_rozpoczecia']);
      $data_zakonczenia = mysqli_real_escape_string($connection, $_POST['data_zakonczenia']);
      $aktywna = isset($_POST['aktywna']) ? 1 : 0;
      
      $sql = "INSERT INTO kody_promocyjne (kod, znizka, data_rozpoczecia, data_zakonczenia, aktywna) 
              VALUES ('$kod', '$znizka', '$data_rozpoczecia', '$data_zakonczenia', '$aktywna')";
      mysqli_query($connection, $sql);
      header('Location: panel.php?view=promotions&success=added');
      exit();
      break;
      
    case 'update':
      $id = mysqli_real_escape_string($connection, $_POST['promotion_id']);
      $kod = mysqli_real_escape_string($connection, $_POST['kod']);
      $znizka = mysqli_real_escape_string($connection, $_POST['znizka']);
      $data_rozpoczecia = mysqli_real_escape_string($connection, $_POST['data_rozpoczecia']);
      $data_zakonczenia = mysqli_real_escape_string($connection, $_POST['data_zakonczenia']);
      $aktywna = isset($_POST['aktywna']) ? 1 : 0;
      
      $sql = "UPDATE kody_promocyjne SET 
              kod = '$kod',
              znizka = '$znizka',
              data_rozpoczecia = '$data_rozpoczecia',
              data_zakonczenia = '$data_zakonczenia',
              aktywna = '$aktywna'
              WHERE id = '$id'";
      mysqli_query($connection, $sql);
      header('Location: panel.php?view=promotions&success=updated');
      exit();
      break;
      
    case 'delete':
      $id = mysqli_real_escape_string($connection, $_POST['promotion_id']);
      mysqli_query($connection, "DELETE FROM kody_promocyjne WHERE id = '$id'");
      header('Location: panel.php?view=promotions&success=deleted');
      exit();
      break;
      
    case 'toggle':
      $id = mysqli_real_escape_string($connection, $_POST['promotion_id']);
      $aktywna = mysqli_real_escape_string($connection, $_POST['aktywna']);
      mysqli_query($connection, "UPDATE kody_promocyjne SET aktywna = '$aktywna' WHERE id = '$id'");
      header('Location: panel.php?view=promotions&success=updated');
      exit();
      break;
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'data_rozpoczecia';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'kod', 'znizka', 'data_rozpoczecia', 'data_zakonczenia', 'aktywna'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_rozpoczecia';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=promotions&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie kodów promocyjnych
$sql = "SELECT * FROM kody_promocyjne ORDER BY $sort_column $sort_dir";
$promocje = mysqli_query($connection, $sql);
?>

<div class="admin-actions">
  <button class="admin-button success" onclick="showAddPromotionModal()">
    <i class="fas fa-plus"></i> Dodaj kod promocyjny
  </button>
</div>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="promotionSearch" class="form-input" placeholder="Szukaj kodów..." 
           onkeyup="filterTable('promotionTable', 'promotionSearch', 1)">
  </div>
  <select class="form-input" onchange="filterByStatus(this.value)">
    <option value="">Wszystkie</option>
    <option value="active">Aktywne</option>
    <option value="inactive">Nieaktywne</option>
    <option value="future">Przyszłe</option>
    <option value="expired">Wygasłe</option>
  </select>
</div>

<table id="promotionTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('kod', $sort_column, $sort_dir); ?>" class="sort-link">
          Kod <?php echo getSortIcon('kod', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('znizka', $sort_column, $sort_dir); ?>" class="sort-link">
          Zniżka <?php echo getSortIcon('znizka', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('data_rozpoczecia', $sort_column, $sort_dir); ?>" class="sort-link">
          Data rozpoczęcia <?php echo getSortIcon('data_rozpoczecia', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('data_zakonczenia', $sort_column, $sort_dir); ?>" class="sort-link">
          Data zakończenia <?php echo getSortIcon('data_zakonczenia', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('aktywna', $sort_column, $sort_dir); ?>" class="sort-link">
          Status <?php echo getSortIcon('aktywna', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($promotion = mysqli_fetch_assoc($promocje)) : 
      $now = new DateTime();
      $start = new DateTime($promotion['data_rozpoczecia']);
      $end = new DateTime($promotion['data_zakonczenia']);
      
      $status = '';
      if (!$promotion['aktywna']) {
        $status = 'inactive';
      } elseif ($now < $start) {
        $status = 'future';
      } elseif ($now > $end) {
        $status = 'expired';
      } else {
        $status = 'active';
      }
    ?>
      <tr data-status="<?php echo $status; ?>">
        <td><?php echo htmlspecialchars($promotion['id']); ?></td>
        <td><?php echo htmlspecialchars($promotion['kod']); ?></td>
        <td><?php echo htmlspecialchars($promotion['znizka']); ?>%</td>
        <td><?php echo date('d.m.Y H:i', strtotime($promotion['data_rozpoczecia'])); ?></td>
        <td><?php echo date('d.m.Y H:i', strtotime($promotion['data_zakonczenia'])); ?></td>
        <td>
          <span class="admin-status status-<?php echo $status; ?>">
            <?php 
              switch ($status) {
                case 'active': echo 'Aktywny'; break;
                case 'inactive': echo 'Nieaktywny'; break;
                case 'future': echo 'Przyszły'; break;
                case 'expired': echo 'Wygasły'; break;
              }
            ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <button class="admin-button warning" onclick="editPromotion(<?php echo htmlspecialchars(json_encode($promotion)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="admin-button <?php echo $promotion['aktywna'] ? 'danger' : 'success'; ?>" 
                    onclick="toggleStatus(<?php echo $promotion['id']; ?>, <?php echo $promotion['aktywna']; ?>)">
              <i class="fas fa-power-off"></i>
            </button>
            <button class="admin-button danger" onclick="confirmDelete(<?php echo $promotion['id']; ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Modal dodawania/edycji kodu promocyjnego -->
<div id="promotionModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closePromotionModal()">&times;</span>
    <h2 id="modalTitle">Dodaj kod promocyjny</h2>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="promotion_id" id="promotionId">
      
      <div class="form-group">
        <label for="kod" class="form-label">Kod promocyjny</label>
        <input type="text" id="kod" name="kod" class="form-input" required 
               pattern="[A-Za-z0-9]+" title="Tylko litery i cyfry">
      </div>
      
      <div class="form-group">
        <label for="znizka" class="form-label">Zniżka (%)</label>
        <input type="number" id="znizka" name="znizka" class="form-input" 
               min="0" max="100" step="0.01" required>
      </div>
      
      <div class="form-group">
        <label for="data_rozpoczecia" class="form-label">Data rozpoczęcia</label>
        <input type="datetime-local" id="data_rozpoczecia" name="data_rozpoczecia" 
               class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="data_zakonczenia" class="form-label">Data zakończenia</label>
        <input type="datetime-local" id="data_zakonczenia" name="data_zakonczenia" 
               class="form-input" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">
          <input type="checkbox" name="aktywna" id="aktywna" checked> 
          Kod aktywny
        </label>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closePromotionModal()">
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
    <p>Czy na pewno chcesz usunąć ten kod promocyjny? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="promotion_id" id="delete_promotion_id">
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
function showAddPromotionModal() {
  const modal = document.getElementById('promotionModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Dodaj kod promocyjny';
  document.getElementById('formAction').value = 'add';
  document.getElementById('promotionId').value = '';
  form.reset();
  
  // Ustaw domyślne daty
  const now = new Date();
  const end = new Date();
  end.setMonth(end.getMonth() + 1);
  
  document.getElementById('data_rozpoczecia').value = now.toISOString().slice(0, 16);
  document.getElementById('data_zakonczenia').value = end.toISOString().slice(0, 16);
  document.getElementById('aktywna').checked = true;
  
  showModal('promotionModal');
}

function editPromotion(promotion) {
  const modal = document.getElementById('promotionModal');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Edytuj kod promocyjny';
  document.getElementById('formAction').value = 'update';
  document.getElementById('promotionId').value = promotion.id;
  document.getElementById('kod').value = promotion.kod;
  document.getElementById('znizka').value = promotion.znizka;
  
  // Konwersja daty do formatu przyjmowanego przez input datetime-local
  const dataRozpoczecia = new Date(promotion.data_rozpoczecia);
  const dataZakonczenia = new Date(promotion.data_zakonczenia);
  
  document.getElementById('data_rozpoczecia').value = dataRozpoczecia.toISOString().slice(0, 16);
  document.getElementById('data_zakonczenia').value = dataZakonczenia.toISOString().slice(0, 16);
  document.getElementById('aktywna').checked = promotion.aktywna === "1";
  
  showModal('promotionModal');
}

function confirmDelete(promotionId) {
  document.getElementById('delete_promotion_id').value = promotionId;
  showModal('deleteModal');
}

function closePromotionModal() {
  closeModal('promotionModal');
}

function closeDeleteModal() {
  closeModal('deleteModal');
}

function toggleStatus(id, currentStatus) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="promotion_id" value="${id}">
    <input type="hidden" name="aktywna" value="${currentStatus ? '0' : '1'}">
  `;
  document.body.appendChild(form);
  form.submit();
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
  const rows = document.querySelectorAll('#promotionTable tbody tr');
  rows.forEach(row => {
    if (!status || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Walidacja dat przy dodawaniu/edycji
document.addEventListener('DOMContentLoaded', function() {
  const startDate = document.getElementById('data_rozpoczecia');
  const endDate = document.getElementById('data_zakonczenia');
  
  function validateDates() {
    if (startDate.value && endDate.value) {
      if (new Date(endDate.value) <= new Date(startDate.value)) {
        endDate.setCustomValidity('Data zakończenia musi być późniejsza niż data rozpoczęcia');
      } else {
        endDate.setCustomValidity('');
      }
    }
  }
  
  startDate.addEventListener('change', validateDates);
  endDate.addEventListener('change', validateDates);
});
</script>