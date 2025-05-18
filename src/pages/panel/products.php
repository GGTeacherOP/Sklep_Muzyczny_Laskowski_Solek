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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete':
        // Sprawdź uprawnienia - tylko informatyk i właściciel mogą usuwać
        if ($role === 'informatyk' || $role === 'właściciel') {
          if (isset($_POST['product_id'])) {
            $product_id = mysqli_real_escape_string($connection, $_POST['product_id']);
            $sql = "DELETE FROM instrumenty WHERE id = '$product_id'";
            mysqli_query($connection, $sql);
          }
        }
        break;
        
      case 'edit_stock':
        // Pracownicy mogą tylko zmieniać stan magazynowy
        if (isset($_POST['product_id'])) {
          $product_id = mysqli_real_escape_string($connection, $_POST['product_id']);
          $stan = mysqli_real_escape_string($connection, $_POST['stan_magazynowy']);
          
          $sql = "UPDATE instrumenty SET stan_magazynowy = '$stan' WHERE id = '$product_id'";
          mysqli_query($connection, $sql);
          header('Location: panel.php?view=products&success=updated');
          exit();
        }
        break;
        
      case 'add':
        // Sprawdź uprawnienia - tylko informatyk i właściciel mogą dodawać produkty
        if ($role === 'informatyk' || $role === 'właściciel') {
        $kod = mysqli_real_escape_string($connection, $_POST['kod_produktu']);
        $nazwa = mysqli_real_escape_string($connection, $_POST['nazwa']);
        $opis = mysqli_real_escape_string($connection, $_POST['opis']);
        $cena = mysqli_real_escape_string($connection, $_POST['cena_sprzedazy']);
        $stan = mysqli_real_escape_string($connection, $_POST['stan_magazynowy']);
        $producent = mysqli_real_escape_string($connection, $_POST['producent_id']);
        $kategoria = mysqli_real_escape_string($connection, $_POST['kategoria_id']);
        
          $sql = "INSERT INTO instrumenty (kod_produktu, nazwa, opis, cena_sprzedazy, stan_magazynowy, producent_id, kategoria_id) 
                  VALUES ('$kod', '$nazwa', '$opis', '$cena', '$stan', '$producent', '$kategoria')";
          mysqli_query($connection, $sql);
          header('Location: panel.php?view=products&success=added');
          exit();
        }
        break;
        
      case 'edit':
        // Sprawdź uprawnienia - tylko informatyk i właściciel mogą edytować
        if ($role === 'informatyk' || $role === 'właściciel') {
          $id = mysqli_real_escape_string($connection, $_POST['product_id']);
          $kod = mysqli_real_escape_string($connection, $_POST['kod_produktu']);
          $nazwa = mysqli_real_escape_string($connection, $_POST['nazwa']);
          $opis = mysqli_real_escape_string($connection, $_POST['opis']);
          $cena = mysqli_real_escape_string($connection, $_POST['cena_sprzedazy']);
          $stan = mysqli_real_escape_string($connection, $_POST['stan_magazynowy']);
          $producent = mysqli_real_escape_string($connection, $_POST['producent_id']);
          $kategoria = mysqli_real_escape_string($connection, $_POST['kategoria_id']);
          
          $sql = "UPDATE instrumenty SET 
                  kod_produktu = '$kod',
                  nazwa = '$nazwa',
                  opis = '$opis',
                  cena_sprzedazy = '$cena',
                  stan_magazynowy = '$stan',
                  producent_id = '$producent',
                  kategoria_id = '$kategoria'
                  WHERE id = '$id'";
          mysqli_query($connection, $sql);
          header('Location: panel.php?view=products&success=updated');
          exit();
        }
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
  <?php if ($role === 'informatyk' || $role === 'właściciel'): ?>
  <button class="admin-button success add" onclick="showAddModal()">
    <i class="fas fa-plus"></i> Dodaj produkt
  </button>
  <?php endif; ?>
  <div class="admin-search">
    <input type="text" id="productSearch" class="form-input" placeholder="Szukaj produktów..." 
           onkeyup="filterTable('productTable', 2)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('categoryDropdown')">
      <span id="categoryDropdownText">Wszystkie kategorię</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="categoryDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectCategory('', 'Wszystkie kategorie')">Wszystkie kategorie</a></li>
      <li class="dropdown-divider"></li>
      <?php foreach ($kategorie_data as $kategoria) : ?>
        <li><a href="#" class="dropdown-item" onclick="selectCategory('<?php echo $kategoria['id']; ?>', '<?php echo htmlspecialchars($kategoria['nazwa']); ?>')">
          <?php echo htmlspecialchars($kategoria['nazwa']); ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('brandDropdown')">
      <span id="brandDropdownText">Wszyscy producenci</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="brandDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectBrand('', 'Wszyscy producenci')">Wszyscy producenci</a></li>
      <li class="dropdown-divider"></li>
      <?php foreach ($producenci_data as $producent) : ?>
        <li><a href="#" class="dropdown-item" onclick="selectBrand('<?php echo $producent['id']; ?>', '<?php echo htmlspecialchars($producent['nazwa']); ?>')">
          <?php echo htmlspecialchars($producent['nazwa']); ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="admin-table-wrapper">
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
          <?php if ($role === 'informatyk' || $role === 'właściciel'): ?>
          <button class="admin-button warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
            <i class="fas fa-edit"></i>
          </button>
          <?php else: ?>
          <button class="admin-button warning" onclick="editProductStock(<?php echo htmlspecialchars(json_encode($product)); ?>)">
            <i class="fas fa-edit"></i>
          </button>
          <?php endif; ?>
          <?php if ($role === 'informatyk' || $role === 'właściciel'): ?>
          <button class="admin-button danger" onclick="confirmDelete(<?php echo $product['id']; ?>)">
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

<!-- Modal dodawania/edycji produktu -->
<div id="productModal" class="modal">
  <div class="modal-content">
    <h2 id="modalTitle">Dodaj produkt</h2>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="product_id" id="productId">
      <input type="hidden" name="producent_id" id="selectedProducent">
      <input type="hidden" name="kategoria_id" id="selectedCategory">
      
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
        <div class="dropdown">
          <button type="button" class="dropdown-toggle" onclick="toggleDropdown('producentDropdown')">
            <span id="producentDropdownText">Wybierz producenta</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="producentDropdown">
          <?php foreach ($producenci_data as $producent) : ?>
              <li><a href="#" class="dropdown-item" onclick="selectProducent('<?php echo $producent['id']; ?>', '<?php echo htmlspecialchars($producent['nazwa']); ?>')">
              <?php echo htmlspecialchars($producent['nazwa']); ?>
              </a></li>
          <?php endforeach; ?>
          </ul>
        </div>
      </div>
      
      <div class="form-group">
        <label for="kategoria_id" class="form-label">Kategoria</label>
        <div class="dropdown">
          <button type="button" class="dropdown-toggle" onclick="toggleDropdown('kategoriaDropdown')">
            <span id="kategoriaDropdownText">Wybierz kategorię</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="kategoriaDropdown">
          <?php foreach ($kategorie_data as $kategoria) : ?>
              <li><a href="#" class="dropdown-item" onclick="selectKategoria('<?php echo $kategoria['id']; ?>', '<?php echo htmlspecialchars($kategoria['nazwa']); ?>')">
              <?php echo htmlspecialchars($kategoria['nazwa']); ?>
              </a></li>
          <?php endforeach; ?>
          </ul>
        </div>
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

<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h2>Potwierdzenie usunięcia</h2>
    <p>Czy na pewno chcesz usunąć ten produkt? Tej operacji nie można cofnąć.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="product_id" id="delete_product_id">
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
function showAddModal() {
  const modal = document.getElementById('productModal');
  const form = modal.querySelector('form');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Dodaj produkt';
  document.getElementById('formAction').value = 'add';
  document.getElementById('productId').value = '';
  document.getElementById('selectedProducent').value = '';
  document.getElementById('selectedCategory').value = '';
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
  document.getElementById('selectedProducent').value = product.producent_id;
  document.getElementById('selectedCategory').value = product.kategoria_id;
  
  // Aktualizacja tekstu w dropdownach
  document.getElementById('producentDropdownText').textContent = product.nazwa_producenta;
  document.getElementById('kategoriaDropdownText').textContent = product.nazwa_kategorii;
  
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

function selectProducent(id, name) {
  document.getElementById('selectedProducent').value = id;
  document.getElementById('producentDropdownText').textContent = name;
  document.getElementById('producentDropdown').classList.remove('show');
}

function selectKategoria(id, name) {
  document.getElementById('selectedCategory').value = id;
  document.getElementById('kategoriaDropdownText').textContent = name;
  document.getElementById('kategoriaDropdown').classList.remove('show');
}

function confirmDelete(productId) {
  document.getElementById('delete_product_id').value = productId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

function editProductStock(product) {
  const modal = document.getElementById('productModal');
  const modalTitle = document.getElementById('modalTitle');
  
  modalTitle.textContent = 'Edytuj stan magazynowy';
  document.getElementById('formAction').value = 'edit_stock';
  document.getElementById('productId').value = product.id;
  document.getElementById('stan_magazynowy').value = product.stan_magazynowy;
  
  // Ukryj pozostałe pola formularza i wyłącz wymagania dla ukrytych pól
  document.querySelectorAll('.form-group').forEach(group => {
    group.style.display = 'none';
    
    // Znajdź pola formularza wewnątrz grupy i wyłącz wymagania
    const inputs = group.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      if (input !== document.getElementById('stan_magazynowy')) {
        input.required = false; // Usuń atrybut required
        input.disabled = true;  // Wyłącz pole, aby nie było wysyłane z formularzem
      }
    });
  });
  
  // Pokaż tylko pole stanu magazynowego i upewnij się, że jest aktywne i wymagane
  const stanGroup = document.querySelector('.form-group:nth-of-type(5)');
  stanGroup.style.display = 'block';
  document.getElementById('stan_magazynowy').disabled = false;
  document.getElementById('stan_magazynowy').required = true;
  
  modal.style.display = 'block';
}

// Modyfikacja obsługi zamykania modali
window.onclick = function(event) {
  const productModal = document.getElementById('productModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == productModal) {
    closeProductModal();
  } else if (event.target == deleteModal) {
    closeDeleteModal();
  }
}

function validateForm() {
  const formAction = document.getElementById('formAction').value;
  
  if (formAction === 'add' || formAction === 'edit') {
    const kod = document.getElementById('kod_produktu').value;
    const nazwa = document.getElementById('nazwa').value;
    const opis = document.getElementById('opis').value;
    const cena = document.getElementById('cena_sprzedazy').value;
    const stan = document.getElementById('stan_magazynowy').value;
    const producent = document.getElementById('selectedProducent').value;
    const kategoria = document.getElementById('selectedCategory').value;
    
    if (!kod || !nazwa || !opis || !cena || !stan || !producent || !kategoria) {
      alert('Wypełnij wszystkie pola formularza');
      return false;
    }
    
    if (parseFloat(cena) <= 0) {
      alert('Cena musi być większa od 0');
      return false;
    }
    
    if (parseInt(stan) < 0) {
      alert('Stan magazynowy nie może być ujemny');
      return false;
    }
  } else if (formAction === 'edit_stock') {
    const stan = document.getElementById('stan_magazynowy').value;
    
    if (stan === '' || parseInt(stan) < 0) {
      alert('Stan magazynowy nie może być pusty ani ujemny');
      return false;
    }
  }
  
  return true;
}
</script>