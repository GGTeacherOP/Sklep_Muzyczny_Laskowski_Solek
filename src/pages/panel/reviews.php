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

// Pobieranie listy produktów
$products_query = "SELECT id, nazwa, kod_produktu FROM instrumenty ORDER BY nazwa";
$products_result = $connection->query($products_query);
$products = [];
while ($product = $products_result->fetch_assoc()) {
    $products[] = $product;
}

// Pobieranie opinii
$query = "SELECT io.*, i.nazwa as instrument_nazwa, i.kod_produktu 
          FROM instrument_oceny io 
          JOIN instrumenty i ON io.instrument_id = i.id 
          ORDER BY $order_col $sort_dir";
$result = $connection->query($query);
?>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="reviewSearch" class="form-input" placeholder="Szukaj ocen..." 
           onkeyup="filterTable('reviewTable', 1)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('productDropdown')">
      <span id="productDropdownText">Wszystkie produkty</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="productDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectProduct('', 'Wszystkie produkty')">Wszystkie produkty</a></li>
      <li class="dropdown-divider"></li>
      <?php foreach ($products as $product): ?>
        <li><a href="#" class="dropdown-item" onclick="selectProduct('<?php echo $product['id']; ?>', '<?php echo htmlspecialchars($product['nazwa'] . ' (' . $product['kod_produktu'] . ')'); ?>')">
          <?php echo htmlspecialchars($product['nazwa'] . ' (' . $product['kod_produktu'] . ')'); ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('ratingDropdown')">
      <span id="ratingDropdownText">Wszystkie oceny</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="ratingDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectRating('', 'Wszystkie oceny')">Wszystkie oceny</a></li>
      <li class="dropdown-divider"></li>
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <li><a href="#" class="dropdown-item" onclick="selectRating('<?php echo $i; ?>', '<?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>')">
          <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
        </a></li>
      <?php endfor; ?>
    </ul>
  </div>
    <input type="date" class="form-input date-input" id="dateFrom" name="date_from" placeholder="Od">
    <input type="date" class="form-input date-input" id="dateTo" name="date_to" placeholder="Do">
    <button class="admin-button" onclick="filterByDate()">
      <i class="fas fa-filter"></i> Filtruj
    </button>
</div>

<div class="admin-table-wrapper">
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
      <tr data-product="<?php echo $review['instrument_id']; ?>" 
          data-rating="<?php echo $review['ocena']; ?>"
          data-date="<?php echo date('Y-m-d', strtotime($review['data_oceny'])); ?>">
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
            <button class="admin-button danger" onclick="confirmDelete(<?php echo $review['id']; ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h2>Potwierdzenie usunięcia</h2>
    <p>Czy na pewno chcesz usunąć tę ocenę? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="review_id" id="delete_review_id">
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

function filterByProduct(productId) {
  const rows = document.querySelectorAll('#reviewTable tbody tr');
  rows.forEach(row => {
    if (!productId || row.dataset.product === productId) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function filterByRating(rating) {
  const rows = document.querySelectorAll('#reviewTable tbody tr');
  rows.forEach(row => {
    if (!rating || row.dataset.rating === rating) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function filterByDate() {
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;
  const rows = document.querySelectorAll('#reviewTable tbody tr');

  rows.forEach(row => {
    const reviewDate = new Date(row.dataset.date);
    const fromDate = dateFrom ? new Date(dateFrom) : null;
    const toDate = dateTo ? new Date(dateTo) : null;

    let show = true;
    if (fromDate && reviewDate < fromDate) show = false;
    if (toDate && reviewDate > toDate) show = false;

    row.style.display = show ? '' : 'none';
  });
}

function selectProduct(productId, productName) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('productDropdownText');
  button.textContent = productName;
  button.dataset.selectedId = productId;
  
  // Filtruj oceny
  filterByProduct(productId);
  
  // Ukryj dropdown
  document.getElementById('productDropdown').classList.remove('show');
}

function selectRating(rating, ratingText) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('ratingDropdownText');
  button.textContent = ratingText;
  button.dataset.selectedId = rating;
  
  // Filtruj oceny
  filterByRating(rating);
  
  // Ukryj dropdown
  document.getElementById('ratingDropdown').classList.remove('show');
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

document.addEventListener('DOMContentLoaded', function() {
  // Obsługa komunikatów
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('success') === 'deleted') {
    alert('Ocena została pomyślnie usunięta.');
  }
});

function confirmDelete(reviewId) {
  document.getElementById('delete_review_id').value = reviewId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

// Obsługa zamykania modali po kliknięciu poza nimi
window.onclick = function(event) {
  const deleteModal = document.getElementById('deleteModal');
  if (event.target == deleteModal) {
    closeDeleteModal();
  }
}
</script>