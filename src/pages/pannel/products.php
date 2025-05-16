<?php
  /** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete':
        if (isset($_POST['product_id'])) {
          $product_id = mysqli_real_escape_string($connection, $_POST['product_id']);
          $sql = "DELETE FROM instrumenty WHERE id = '$product_id'";
          mysqli_query($connection, $sql);
        }
        break;
      case 'add':
      case 'edit':
        $kod = mysqli_real_escape_string($connection, $_POST['kod_produktu']);
        $nazwa = mysqli_real_escape_string($connection, $_POST['nazwa']);
        $opis = mysqli_real_escape_string($connection, $_POST['opis']);
        $cena = mysqli_real_escape_string($connection, $_POST['cena_sprzedazy']);
        $stan = mysqli_real_escape_string($connection, $_POST['stan_magazynowy']);
        $producent = mysqli_real_escape_string($connection, $_POST['producent_id']);
        $kategoria = mysqli_real_escape_string($connection, $_POST['kategoria_id']);
        
        if ($_POST['action'] === 'add') {
          $sql = "INSERT INTO instrumenty (kod_produktu, nazwa, opis, cena_sprzedazy, stan_magazynowy, producent_id, kategoria_id) 
                  VALUES ('$kod', '$nazwa', '$opis', '$cena', '$stan', '$producent', '$kategoria')";
        } else {
          $id = mysqli_real_escape_string($connection, $_POST['product_id']);
          $sql = "UPDATE instrumenty SET 
                  kod_produktu = '$kod',
                  nazwa = '$nazwa',
                  opis = '$opis',
                  cena_sprzedazy = '$cena',
                  stan_magazynowy = '$stan',
                  producent_id = '$producent',
                  kategoria_id = '$kategoria'
                  WHERE id = '$id'";
        }
        mysqli_query($connection, $sql);
        break;
    }
  }
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'id';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'kod_produktu', 'nazwa', 'cena_sprzedazy', 'stan_magazynowy', 'nazwa_producenta', 'nazwa_kategorii'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=products&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// Pobranie producentów i kategorii do formularzy
$producenci = mysqli_query($connection, "SELECT id, nazwa FROM producenci ORDER BY nazwa");
$producenci_data = [];
while ($row = mysqli_fetch_assoc($producenci)) {
    $producenci_data[] = $row;
}
mysqli_data_seek($producenci, 0); // Reset wskaźnika dla późniejszego użycia

$kategorie = mysqli_query($connection, "SELECT id, nazwa FROM kategorie_instrumentow ORDER BY nazwa");
$kategorie_data = [];
while ($row = mysqli_fetch_assoc($kategorie)) {
    $kategorie_data[] = $row;
}
mysqli_data_seek($kategorie, 0); // Reset wskaźnika dla późniejszego użycia

// Pobranie produktów z dodatkowymi informacjami
$order_col = $sort_column;
if ($sort_column === 'nazwa_producenta') {
    $order_col = 'p.nazwa';
} else if ($sort_column === 'nazwa_kategorii') {
    $order_col = 'k.nazwa';
} else {
    $order_col = "i.$sort_column";
}

$sql = "SELECT i.*, p.nazwa as nazwa_producenta, k.nazwa as nazwa_kategorii 
        FROM instrumenty i 
        JOIN producenci p ON i.producent_id = p.id 
        JOIN kategorie_instrumentow k ON i.kategoria_id = k.id 
        ORDER BY $order_col $sort_dir";
$produkty = mysqli_query($connection, $sql);
?>

<div class="admin-filters">
  <button class="admin-button success add" onclick="showAddModal()">
    <i class="fas fa-plus"></i> Dodaj produkt
  </button>
  <div class="admin-search">
    <input type="text" id="productSearch" class="form-input" placeholder="Szukaj produktów..." 
           onkeyup="filterTable('productTable', 2)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" onclick="toggleDropdown('categoryDropdown')">
      <span id="categoryDropdownText">Wybierz kategorię</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="dropdown-menu" id="categoryDropdown">
      <a href="#" class="dropdown-item" onclick="selectCategory('', 'Wszystkie kategorie')">Wszystkie kategorie</a>
      <div class="dropdown-divider"></div>
      <?php foreach ($kategorie_data as $kategoria) : ?>
        <a href="#" class="dropdown-item" onclick="selectCategory('<?php echo $kategoria['id']; ?>', '<?php echo htmlspecialchars($kategoria['nazwa']); ?>')">
          <?php echo htmlspecialchars($kategoria['nazwa']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" onclick="toggleDropdown('brandDropdown')">
      <span id="brandDropdownText">Wybierz producenta</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="dropdown-menu" id="brandDropdown">
      <a href="#" class="dropdown-item" onclick="selectBrand('', 'Wszyscy producenci')">Wszyscy producenci</a>
      <div class="dropdown-divider"></div>
      <?php foreach ($producenci_data as $producent) : ?>
        <a href="#" class="dropdown-item" onclick="selectBrand('<?php echo $producent['id']; ?>', '<?php echo htmlspecialchars($producent['nazwa']); ?>')">
          <?php echo htmlspecialchars($producent['nazwa']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<table id="productTable" class="admin-table">
  <thead>
  <tr>
    <th>
      <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
        ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('kod_produktu', $sort_column, $sort_dir); ?>" class="sort-link">
        Kod <?php echo getSortIcon('kod_produktu', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
        Nazwa <?php echo getSortIcon('nazwa', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('cena_sprzedazy', $sort_column, $sort_dir); ?>" class="sort-link">
        Cena <?php echo getSortIcon('cena_sprzedazy', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('stan_magazynowy', $sort_column, $sort_dir); ?>" class="sort-link">
        Stan <?php echo getSortIcon('stan_magazynowy', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('nazwa_producenta', $sort_column, $sort_dir); ?>" class="sort-link">
        Producent <?php echo getSortIcon('nazwa_producenta', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>
      <a href="<?php echo getSortLink('nazwa_kategorii', $sort_column, $sort_dir); ?>" class="sort-link">
        Kategoria <?php echo getSortIcon('nazwa_kategorii', $sort_column, $sort_dir); ?>
      </a>
    </th>
    <th>Akcje</th>
  </tr>
  </thead>
  <tbody>
    <?php while ($product = mysqli_fetch_assoc($produkty)) : ?>
    <tr data-category="<?php echo $product['kategoria_id']; ?>" data-brand="<?php echo $product['producent_id']; ?>">
      <td><?php echo htmlspecialchars($product['id']); ?></td>
      <td><?php echo htmlspecialchars($product['kod_produktu']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa']); ?></td>
      <td><?php echo number_format($product['cena_sprzedazy'], 2); ?> zł</td>
      <td><?php echo htmlspecialchars($product['stan_magazynowy']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa_producenta']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa_kategorii']); ?></td>
      <td>
        <div class="admin-actions">
          <button class="admin-button warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
            <i class="fas fa-edit"></i>
          </button>
          <form method="POST" style="display: inline;" 
                onsubmit="return confirm('Czy na pewno chcesz usunąć ten produkt?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
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

<!-- Modal dodawania/edycji produktu -->
<div id="productModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeProductModal()">&times;</span>
    <h2 id="modalTitle">Dodaj produkt</h2>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="product_id" id="productId">
      
      <div class="form-group">
        <label for="kod_produktu" class="form-label">Kod produktu</label>
        <input type="text" id="kod_produktu" name="kod_produktu" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="nazwa" class="form-label">Nazwa</label>
        <input type="text" id="nazwa" name="nazwa" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label for="opis" class="form-label">Opis</label>
        <textarea id="opis" name="opis" class="form-input" required></textarea>
      </div>
      
      <div class="form-group">
        <label for="cena_sprzedazy" class="form-label">Cena sprzedaży</label>
        <input type="number" id="cena_sprzedazy" name="cena_sprzedazy" class="form-input" step="0.01" min="0" required>
      </div>
      
      <div class="form-group">
        <label for="stan_magazynowy" class="form-label">Stan magazynowy</label>
        <input type="number" id="stan_magazynowy" name="stan_magazynowy" class="form-input" min="0" required>
      </div>
      
      <div class="form-group">
        <label for="producent_id" class="form-label">Producent</label>
        <select id="producent_id" name="producent_id" class="form-input" required>
          <?php foreach ($producenci_data as $producent) : ?>
            <option value="<?php echo $producent['id']; ?>">
              <?php echo htmlspecialchars($producent['nazwa']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="kategoria_id" class="form-label">Kategoria</label>
        <select id="kategoria_id" name="kategoria_id" class="form-input" required>
          <?php foreach ($kategorie_data as $kategoria) : ?>
            <option value="<?php echo $kategoria['id']; ?>">
              <?php echo htmlspecialchars($kategoria['nazwa']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="admin-actions">
        <button type="submit" class="admin-button success">
          <i class="fas fa-save"></i> Zapisz
        </button>
        <button type="button" class="admin-button" onclick="closeProductModal()">
          <i class="fas fa-times"></i> Anuluj
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function showAddModal() {
  const modal = document.getElementById('productModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Dodaj produkt';
  document.getElementById('formAction').value = 'add';
  document.getElementById('productId').value = '';
  form.reset();
  
  modal.style.display = 'block';
}

function closeProductModal() {
  const modal = document.getElementById('productModal');
  modal.style.display = 'none';
}

function editProduct(product) {
  const modal = document.getElementById('productModal');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Edytuj produkt';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('productId').value = product.id;
  document.getElementById('kod_produktu').value = product.kod_produktu;
  document.getElementById('nazwa').value = product.nazwa;
  document.getElementById('opis').value = product.opis;
  document.getElementById('cena_sprzedazy').value = product.cena_sprzedazy;
  document.getElementById('stan_magazynowy').value = product.stan_magazynowy;
  document.getElementById('producent_id').value = product.producent_id;
  document.getElementById('kategoria_id').value = product.kategoria_id;
  
  modal.style.display = 'block';
}

function filterTable(tableId, columnIndex) {
  const input = document.getElementById('productSearch');
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

function filterByCategory(categoryId) {
  const rows = document.querySelectorAll('#productTable tbody tr');
  const brandId = document.getElementById('brandDropdownText').dataset.selectedId || '';
  
  rows.forEach(row => {
    const matchesCategory = !categoryId || row.dataset.category === categoryId;
    const matchesBrand = !brandId || row.dataset.brand === brandId;
    row.style.display = matchesCategory && matchesBrand ? '' : 'none';
  });
}

function filterByBrand(brandId) {
  const rows = document.querySelectorAll('#productTable tbody tr');
  const categoryId = document.getElementById('categoryDropdownText').dataset.selectedId || '';
  
  rows.forEach(row => {
    const matchesCategory = !categoryId || row.dataset.category === categoryId;
    const matchesBrand = !brandId || row.dataset.brand === brandId;
    row.style.display = matchesCategory && matchesBrand ? '' : 'none';
  });
}

function selectCategory(categoryId, categoryName) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('categoryDropdownText');
  button.textContent = categoryName;
  button.dataset.selectedId = categoryId;
  
  // Filtruj produkty
  filterByCategory(categoryId);
  
  // Ukryj dropdown
  document.getElementById('categoryDropdown').classList.remove('show');
}

function selectBrand(brandId, brandName) {
  // Aktualizuj tekst w przycisku
  const button = document.getElementById('brandDropdownText');
  button.textContent = brandName;
  button.dataset.selectedId = brandId;
  
  // Filtruj produkty
  filterByBrand(brandId);
  
  // Ukryj dropdown
  document.getElementById('brandDropdown').classList.remove('show');
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
</script>