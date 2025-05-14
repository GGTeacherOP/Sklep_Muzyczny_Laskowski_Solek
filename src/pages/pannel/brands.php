<?php
/** @var mysqli $connection */

// Obsługa dodawania nowego producenta
if (isset($_POST['add_brand'])) {
    $nazwa = trim($_POST['nazwa']);

    $stmt = $connection->prepare("INSERT INTO producenci (nazwa) VALUES (?)");
    $stmt->bind_param("s", $nazwa);
    
    if (!$stmt->execute()) {
        if ($connection->errno == 1062) { // Kod błędu dla naruszenia UNIQUE KEY
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
        }
    }
    
    $stmt->close();
    header('Location: panel.php?view=brands&success=added');
    exit();
}

// Obsługa edycji producenta
if (isset($_POST['edit_brand'])) {
    $id = (int)$_POST['brand_id'];
    $nazwa = trim($_POST['nazwa']);

    $stmt = $connection->prepare("UPDATE producenci SET nazwa = ? WHERE id = ?");
    $stmt->bind_param("si", $nazwa, $id);
    
    if (!$stmt->execute()) {
        if ($connection->errno == 1062) { // Kod błędu dla naruszenia UNIQUE KEY
            header('Location: panel.php?view=brands&error=duplicate');
            exit();
        }
    }
    
    $stmt->close();
    header('Location: panel.php?view=brands&success=updated');
    exit();
}

// Obsługa usuwania producenta
if (isset($_POST['delete_brand'])) {
    $id = (int)$_POST['brand_id'];
    
    // Sprawdzenie czy producent ma powiązane instrumenty
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM instrumenty WHERE producent_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        header('Location: panel.php?view=brands&error=has_products');
        exit();
    }

    $stmt = $connection->prepare("DELETE FROM producenci WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: panel.php?view=brands&success=deleted');
    exit();
}

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'id';
$sort_dir = $_GET['dir'] ?? 'asc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['id', 'nazwa', 'liczba_produktow'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'asc';
}

// Tworzenie odpowiedniego zapytania dla sortowania
if ($sort_column === 'liczba_produktow') {
    $query = "SELECT p.*, 
             (SELECT COUNT(*) FROM instrumenty WHERE producent_id = p.id) as liczba_produktow 
             FROM producenci p 
             ORDER BY liczba_produktow $sort_dir, id ASC";
} else {
    $query = "SELECT p.*, 
             (SELECT COUNT(*) FROM instrumenty WHERE producent_id = p.id) as liczba_produktow 
             FROM producenci p 
             ORDER BY $sort_column $sort_dir";
}

$result = $connection->query($query);

// Funkcja do generowania linków sortowania
function getSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=brands&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
?>

<h2>Producenci</h2>
<p>Przeglądaj producentów i dodawaj nowe marki.</p>

<div class="admin-content-header">
    <h3>Lista producentów</h3>
    <button class="btn btn-primary" onclick="showAddBrandModal()">
        <i class="fas fa-plus"></i> Dodaj producenta
    </button>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>
                    <a href="<?php echo getSortLink('id', $sort_column, $sort_dir); ?>" class="sort-link">
                        ID <?php echo getSortIcon('id', $sort_column, $sort_dir); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo getSortLink('nazwa', $sort_column, $sort_dir); ?>" class="sort-link">
                        Nazwa <?php echo getSortIcon('nazwa', $sort_column, $sort_dir); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo getSortLink('liczba_produktow', $sort_column, $sort_dir); ?>" class="sort-link">
                        Liczba produktów <?php echo getSortIcon('liczba_produktow', $sort_column, $sort_dir); ?>
                    </a>
                </th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nazwa']); ?></td>
                    <td><?php echo htmlspecialchars($row['liczba_produktow']); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="showEditBrandModal(<?php 
                            echo htmlspecialchars(json_encode($row)); 
                        ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz usunąć tego producenta?');">
                            <input type="hidden" name="brand_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_brand" class="btn btn-danger btn-sm" <?php echo $row['liczba_produktow'] > 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal dodawania producenta -->
<div id="addBrandModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddBrandModal()">&times;</span>
        <h2>Dodaj producenta</h2>
        <form method="POST">
            <div class="form-group">
                <label for="nazwa">Nazwa:</label>
                <input type="text" id="nazwa" name="nazwa" required maxlength="255">
            </div>
            <button type="submit" name="add_brand" class="btn btn-primary">Dodaj</button>
        </form>
    </div>
</div>

<!-- Modal edycji producenta -->
<div id="editBrandModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditBrandModal()">&times;</span>
        <h2>Edytuj producenta</h2>
        <form method="POST">
            <input type="hidden" id="edit_brand_id" name="brand_id">
            <div class="form-group">
                <label for="edit_nazwa">Nazwa:</label>
                <input type="text" id="edit_nazwa" name="nazwa" required maxlength="255">
            </div>
            <button type="submit" name="edit_brand" class="btn btn-primary">Zapisz zmiany</button>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 5px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-sm {
    padding: 3px 8px;
    font-size: 12px;
}

.d-inline {
    display: inline-block;
}

.sort-link {
    color: #333;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sort-link i {
    margin-left: 5px;
}

th {
    cursor: pointer;
}
</style>

<script>
function showAddBrandModal() {
    document.getElementById('addBrandModal').style.display = 'block';
}

function closeAddBrandModal() {
    document.getElementById('addBrandModal').style.display = 'none';
}

function showEditBrandModal(brand) {
    document.getElementById('edit_brand_id').value = brand.id;
    document.getElementById('edit_nazwa').value = brand.nazwa;
    document.getElementById('editBrandModal').style.display = 'block';
}

function closeEditBrandModal() {
    document.getElementById('editBrandModal').style.display = 'none';
}

// Zamykanie modali po kliknięciu poza nimi
window.onclick = function(event) {
    if (event.target == document.getElementById('addBrandModal')) {
        closeAddBrandModal();
    }
    if (event.target == document.getElementById('editBrandModal')) {
        closeEditBrandModal();
    }
}

// Obsługa komunikatów
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'added') {
        alert('Producent został pomyślnie dodany.');
    } else if (urlParams.get('success') === 'updated') {
        alert('Producent został pomyślnie zaktualizowany.');
    } else if (urlParams.get('success') === 'deleted') {
        alert('Producent został pomyślnie usunięty.');
    } else if (urlParams.get('error') === 'has_products') {
        alert('Nie można usunąć producenta, który ma powiązane produkty.');
    } else if (urlParams.get('error') === 'duplicate') {
        alert('Producent o takiej nazwie już istnieje.');
    }
});
</script>