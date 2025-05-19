<?php
/** @var mysqli $connection */
include_once '../includes/config/db_config.php';
include_once '../includes/config/session_config.php';
include_once '../includes/fetch/fetch_catalog_products.php';
include_once '../includes/fetch/fetch_product_categories.php';
include_once '../includes/render/render_product_card.php';
include_once '../includes/render/render_category_card.php';
include_once '../includes/helpers/cart_helpers.php';

// Pobieranie parametrów filtrowania
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$producer_id = isset($_GET['producer_id']) ? (int)$_GET['producer_id'] : null;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$search_query = isset($_GET['search']) ? $_GET['search'] : null;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'nazwa';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;

// Pobieranie produktów z filtrami
$products = getCatalogProducts(
    $connection,
    $category_id,
    $producer_id,
    $min_price,
    $max_price,
    $sort_by,
    'ASC',
    $search_query,
    $page,
    $items_per_page
);

// Pobieranie całkowitej liczby produktów dla paginacji
$total_products = getTotalProductsCount(
    $connection,
    $category_id,
    $producer_id,
    $min_price,
    $max_price,
    $search_query
);

$total_pages = ceil($total_products / $items_per_page);

// Pobieranie kategorii i producentów do filtrów
$categories = getProductCategories($connection);
$producers_query = "SELECT id, nazwa FROM producenci ORDER BY nazwa";
$producers_result = mysqli_query($connection, $producers_query);

// Pobieranie zakresu cen
$price_range_query = "SELECT MIN(cena_sprzedazy) as min_price, MAX(cena_sprzedazy) as max_price FROM instrumenty";
$price_range_result = mysqli_query($connection, $price_range_query);
$price_range = mysqli_fetch_assoc($price_range_result);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog produktów - Sklep Muzyczny</title>
    <script src="https://kit.fontawesome.com/da02356be8.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/catalog.css">
    <script type="module" src="../assets/js/header.js"></script>
    <script type="module" src="../assets/js/catalog.js"></script>
    <script>
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('show');
            
            // Zamykanie innych dropdownów
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== dropdownId && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            });
        }

        function selectSort(value, text) {
            document.getElementById('sortInput').value = value;
            document.getElementById('sortDropdownText').textContent = text;
            document.getElementById('sortDropdown').classList.remove('show');
            document.getElementById('filters-form').submit();
        }

        // Zamykanie dropdownów po kliknięciu poza nimi
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.matches('.dropdown-toggle *')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu.classList.contains('show')) {
                        menu.classList.remove('show');
                    }
                });
            }
        }
    </script>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <main class="catalog-container">
        <aside class="filters-sidebar">
            <form id="filters-form" method="get" action="katalog.php">
                <div class="filter-section">
                    <h3>Wyszukaj</h3>
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Szukaj po nazwie, opisie lub kodzie produktu..." 
                               value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Kategorie</h3>
                    <div class="categories-grid">
                        <label class="category-checkbox">
                            <input type="radio" name="category_id" value="" 
                                <?php echo !$category_id ? 'checked' : ''; ?>>
                            Wszystkie kategorie
                        </label>
                        <?php mysqli_data_seek($categories, 0); ?>
                        <?php while ($category = mysqli_fetch_assoc($categories)) : ?>
                            <label class="category-checkbox">
                                <input type="radio" name="category_id" value="<?php echo $category['id']; ?>"
                                    <?php echo $category_id == $category['id'] ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($category['nazwa']); ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Producenci</h3>
                    <div class="producers-list">
                        <label class="producer-checkbox">
                            <input type="radio" name="producer_id" value="" 
                                <?php echo !$producer_id ? 'checked' : ''; ?>>
                            Wszyscy producenci
                        </label>
                        <?php while ($producer = mysqli_fetch_assoc($producers_result)) : ?>
                            <label class="producer-checkbox">
                                <input type="radio" name="producer_id" value="<?php echo $producer['id']; ?>"
                                    <?php echo $producer_id == $producer['id'] ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($producer['nazwa']); ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Zakres cenowy</h3>
                    <div class="price-range">
                        <input type="number" name="min_price" placeholder="Od" 
                               min="0" 
                               max="<?php echo ceil($price_range['max_price']); ?>" 
                               step="0.01"
                               value="<?php echo $min_price ?? ''; ?>">
                        <span>-</span>
                        <input type="number" name="max_price" placeholder="Do" 
                               min="0" 
                               max="<?php echo ceil($price_range['max_price']); ?>" 
                               step="0.01"
                               value="<?php echo $max_price ?? ''; ?>">
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Sortowanie</h3>
                    <div class="dropdown">
                        <button class="dropdown-toggle" type="button" onclick="toggleDropdown('sortDropdown')">
                            <span id="sortDropdownText">
                                <?php
                                    $sort_labels = [
                                        'nazwa' => 'Alfabetycznie',
                                        'cena_rosnaco' => 'Cena: od najniższej',
                                        'cena_malejaco' => 'Cena: od najwyższej',
                                        'ocena' => 'Najlepiej oceniane',
                                        'popularnosc' => 'Najpopularniejsze'
                                    ];
                                    echo $sort_labels[$sort_by] ?? 'Alfabetycznie';
                                ?>
                            </span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu" id="sortDropdown">
                            <li><a href="#" class="dropdown-item" onclick="selectSort('nazwa', 'Alfabetycznie')">Alfabetycznie</a></li>
                            <li><a href="#" class="dropdown-item" onclick="selectSort('cena_rosnaco', 'Cena: od najniższej')">Cena: od najniższej</a></li>
                            <li><a href="#" class="dropdown-item" onclick="selectSort('cena_malejaco', 'Cena: od najwyższej')">Cena: od najwyższej</a></li>
                            <li><a href="#" class="dropdown-item" onclick="selectSort('ocena', 'Najlepiej oceniane')">Najlepiej oceniane</a></li>
                            <li><a href="#" class="dropdown-item" onclick="selectSort('popularnosc', 'Najpopularniejsze')">Najpopularniejsze</a></li>
                        </ul>
                        <input type="hidden" name="sort_by" id="sortInput" value="<?php echo htmlspecialchars($sort_by); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="filter-btn apply-filters-btn">
                        <i class="fa-solid fa-check"></i>
                        Zastosuj filtry
                    </button>
                    <a href="katalog.php" class="filter-btn reset-filters-btn">
                        <i class="fa-solid fa-xmark"></i>
                        Resetuj filtry
                    </a>
                </div>
            </form>
        </aside>

        <section class="products-section">
            <div class="products-header">
                <h1>Katalog produktów</h1>
                <p>Znaleziono <?php echo $total_products; ?> produktów</p>
                <?php if ($search_query || $category_id || $producer_id || $min_price || $max_price) : ?>
                    <div class="active-filters">
                        <p>Aktywne filtry:</p>
                        <?php if ($search_query) : ?>
                            <span class="filter-tag search">Wyszukiwanie: <?php echo htmlspecialchars($search_query); ?></span>
                        <?php endif; ?>
                        <?php if ($category_id) : ?>
                            <span class="filter-tag category">Kategoria: <?php 
                                mysqli_data_seek($categories, 0);
                                while ($category = mysqli_fetch_assoc($categories)) {
                                    if ($category['id'] == $category_id) {
                                        echo htmlspecialchars($category['nazwa']);
                                        break;
                                    }
                                }
                            ?></span>
                        <?php endif; ?>
                        <?php if ($producer_id) : ?>
                            <span class="filter-tag producer">Producent: <?php 
                                mysqli_data_seek($producers_result, 0);
                                while ($producer = mysqli_fetch_assoc($producers_result)) {
                                    if ($producer['id'] == $producer_id) {
                                        echo htmlspecialchars($producer['nazwa']);
                                        break;
                                    }
                                }
                            ?></span>
                        <?php endif; ?>
                        <?php if ($min_price) : ?>
                            <span class="filter-tag price">Cena od: <?php echo number_format($min_price, 2); ?> zł</span>
                        <?php endif; ?>
                        <?php if ($max_price) : ?>
                            <span class="filter-tag price">Cena do: <?php echo number_format($max_price, 2); ?> zł</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($products)) : ?>
                <div class="no-products">
                    <i class="fa-solid fa-box-open"></i>
                    <h2>Nie znaleziono produktów</h2>
                    <p>Spróbuj zmienić kryteria wyszukiwania</p>
                </div>
            <?php else : ?>
                <div class="products-grid">
                    <?php foreach ($products as $product) : ?>
                        <?php echo renderProductCard($product, 'buy'); ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1) : ?>
                    <div class="pagination">
                        <?php if ($page > 1) : ?>
                            <a href="?page=<?php echo ($page - 1); ?>&<?php echo http_build_query(array_filter([
                                'category_id' => $category_id,
                                'producer_id' => $producer_id,
                                'min_price' => $min_price,
                                'max_price' => $max_price,
                                'search' => $search_query,
                                'sort_by' => $sort_by
                            ])); ?>" class="pagination-arrow">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) : ?>
                            <a href="?page=1&<?php echo http_build_query(array_filter([
                                'category_id' => $category_id,
                                'producer_id' => $producer_id,
                                'min_price' => $min_price,
                                'max_price' => $max_price,
                                'search' => $search_query,
                                'sort_by' => $sort_by
                            ])); ?>">1</a>
                            <?php if ($start_page > 2) : ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter([
                                'category_id' => $category_id,
                                'producer_id' => $producer_id,
                                'min_price' => $min_price,
                                'max_price' => $max_price,
                                'search' => $search_query,
                                'sort_by' => $sort_by
                            ])); ?>"
                               class="<?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages) : ?>
                            <?php if ($end_page < $total_pages - 1) : ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query(array_filter([
                                'category_id' => $category_id,
                                'producer_id' => $producer_id,
                                'min_price' => $min_price,
                                'max_price' => $max_price,
                                'search' => $search_query,
                                'sort_by' => $sort_by
                            ])); ?>"><?php echo $total_pages; ?></a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages) : ?>
                            <a href="?page=<?php echo ($page + 1); ?>&<?php echo http_build_query(array_filter([
                                'category_id' => $category_id,
                                'producer_id' => $producer_id,
                                'min_price' => $min_price,
                                'max_price' => $max_price,
                                'search' => $search_query,
                                'sort_by' => $sort_by
                            ])); ?>" class="pagination-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include '../components/footer.php'; ?>
</body>
</html>
<?php mysqli_close($connection); ?> 