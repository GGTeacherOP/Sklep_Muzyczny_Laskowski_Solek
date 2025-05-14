<?php
/**
 * Funkcje renderujące widoki dla kategorii
 */

/**
 * Renderuje tabelę kategorii
 * 
 * @param mysqli_result $categories Wynik zapytania zawierający dane kategorii
 * @param string $sort_column Aktualna kolumna sortowania
 * @param string $sort_dir Aktualny kierunek sortowania
 * @return string HTML z tabelą kategorii
 */
function renderCategoriesTable($categories, $sort_column, $sort_dir) {
    ob_start();
?>
<h2>Kategorie</h2>
<p>Zarządzanie kategoriami produktów. Dodawaj, edytuj i usuwaj kategorie.</p>

<div class="admin-actions">
  <button class="admin-button" onclick="showAddCategoryModal()">
    <i class="fas fa-plus"></i> Dodaj kategorię
  </button>
</div>

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="categorySearch" class="form-input" placeholder="Szukaj kategorii..." 
           onkeyup="filterTable('categoryTable', 'categorySearch', 1)">
  </div>
</div>

<table id="categoryTable" class="admin-table">
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
        <a href="<?php echo getSortLink('liczba_instrumentow', $sort_column, $sort_dir); ?>" class="sort-link">
          Liczba instrumentów <?php echo getSortIcon('liczba_instrumentow', $sort_column, $sort_dir); ?>
        </a>
      </th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($category = mysqli_fetch_assoc($categories)) : ?>
      <tr>
        <td><?php echo htmlspecialchars($category['id']); ?></td>
        <td><?php echo htmlspecialchars($category['nazwa']); ?></td>
        <td><?php echo htmlspecialchars($category['liczba_instrumentow']); ?></td>
        <td>
          <div class="admin-actions">
            <button class="admin-button" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
              <i class="fas fa-edit"></i>
            </button>
            <?php if ($category['liczba_instrumentow'] == 0) : ?>
              <button class="admin-button danger" onclick="confirmDelete(<?php echo $category['id']; ?>)">
                <i class="fas fa-trash"></i>
              </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php
    return ob_get_clean();
} 