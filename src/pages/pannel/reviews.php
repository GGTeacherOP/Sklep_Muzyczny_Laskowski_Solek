<?php
/** @var mysqli $connection */

// Obsługa usuwania oceny
if (isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    $stmt = $connection->prepare("DELETE FROM instrument_oceny WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $stmt->close();
    header('Location: panel.php?view=reviews&success=deleted');
    exit();
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

<div class="admin-content-header">
    <h3>Lista ocen produktów</h3>
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
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($row['instrument_nazwa']); ?>
                        <br>
                        <small>(<?php echo htmlspecialchars($row['kod_produktu']); ?>)</small>
                    </td>
                    <td>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $row['ocena'] ? '★' : '☆';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['komentarz']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($row['data_oceny'])); ?></td>
                    <td>
                        <?php if ($row['czy_edytowana']): ?>
                            <span class="badge badge-warning">Tak</span>
                            <br>
                            <small><?php echo date('d.m.Y H:i', strtotime($row['data_edycji'])); ?></small>
                        <?php else: ?>
                            <span class="badge badge-success">Nie</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal potwierdzenia usunięcia -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Potwierdzenie usunięcia</h2>
        <p>Czy na pewno chcesz usunąć tę ocenę? Tej operacji nie można cofnąć.</p>
        <form method="POST">
            <input type="hidden" name="review_id" id="review_id_to_delete">
            <div class="admin-actions">
                <button type="submit" name="delete_review" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Usuń
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Anuluj
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}

.btn {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-danger {
    background-color: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-sm {
    padding: 3px 8px;
    font-size: 12px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    overflow: auto;
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

.admin-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
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
function confirmDelete(id) {
    document.getElementById('review_id_to_delete').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Zamykanie modala po kliknięciu poza nim
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        closeDeleteModal();
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