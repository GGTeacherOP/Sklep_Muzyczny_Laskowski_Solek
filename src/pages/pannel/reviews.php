<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['review_id'])) {
      $review_id = (int)$_POST['review_id'];
      $stmt = $connection->prepare("DELETE FROM instrument_oceny WHERE id = ?");
      $stmt->bind_param("i", $review_id);
      $stmt->execute();
      $stmt->close();
    }
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'data_oceny';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'instrument_nazwa', 'ocena', 'komentarz', 'data_oceny', 'czy_edytowana'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_oceny';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=reviews&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Określenie kolumny sortowania dla SQL
$order_col = $sort_column;
if ($sort_column === 'instrument_nazwa') {
    $order_col = 'i.nazwa';
} else {
    $order_col = "io.$sort_column";
}

// Pobieranie ocen z bazy danych
$query = "SELECT io.*, i.nazwa as instrument_nazwa, i.kod_produktu 
          FROM instrument_oceny io 
          JOIN instrumenty i ON io.instrument_id = i.id 
          ORDER BY $order_col $sort_dir";
$result = $connection->query($query);
?>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="reviewSearch" class="form-input" placeholder="Szukaj ocen..." 
           onkeyup="filterTable('reviewTable', 3)">
  </div>
</div>

<table id="reviewTable" class="admin-table">
  <thead>
    <tr>
      <th>
        <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
          ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('instrument_nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
          Produkt <?php echo getSortIcon('instrument_nazwa', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('ocena', $sort_column, $sort_dir); ?>" class="sort-link">
          Ocena <?php echo getSortIcon('ocena', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('komentarz', $sort_column, $sort_dir); ?>" class="sort-link">
          Komentarz <?php echo getSortIcon('komentarz', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('data_oceny', $sort_column, $sort_dir); ?>" class="sort-link">
          Data oceny <?php echo getSortIcon('data_oceny', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>
        <a href="<?php echo getSortLink('czy_edytowana', $sort_column, $sort_dir); ?>" class="sort-link">
          Edytowana <?php echo getSortIcon('czy_edytowana', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($review = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($review['id']); ?></td>
        <td>
          <?php echo htmlspecialchars($review['instrument_nazwa']); ?>
          <br>
          <small>(<?php echo htmlspecialchars($review['kod_produktu']); ?>)</small>
        </td>
        <td>
          <?php
          for ($i = 1; $i <= 5; $i++) {
              echo $i <= $review['ocena'] ? '★' : '☆';
          }
          ?>
        </td>
        <td><?php echo htmlspecialchars($review['komentarz']); ?></td>
        <td><?php echo date('d.m.Y H:i', strtotime($review['data_oceny'])); ?></td>
        <td>
          <?php if ($review['czy_edytowana']): ?>
            <span class="status-badge success">Tak</span>
            <br>
            <small><?php echo date('d.m.Y H:i', strtotime($review['data_edycji'])); ?></small>
          <?php else: ?>
            <span class="status-badge danger">Nie</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="admin-actions">
            <form method="POST" style="display: inline;" 
                  onsubmit="return confirm('Czy na pewno chcesz usunąć tę ocenę?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
              <button type="submit" class="admin-button danger">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<script>
function filterTable(tableId, columnIndex) {
  const input = document.getElementById('reviewSearch');
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

document.addEventListener('DOMContentLoaded', function() {
  // Obsługa komunikatów
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('success') === 'deleted') {
    alert('Ocena została pomyślnie usunięta.');
  }
});
</script>

<style>
.status-badge {
  display: inline-block;
  padding: 3px 6px;
  border-radius: var(--radius-xxs);
  font-size: var(--font-sm);
  color: white;
  font-weight: 500;
}

.status-badge.success {
  background-color: var(--button-buy-bg);
}

.status-badge.warning {
  background-color: var(--button-edit-bg);
}
</style>