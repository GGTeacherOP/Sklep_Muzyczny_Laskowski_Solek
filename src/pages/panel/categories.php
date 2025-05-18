<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
include_once dirname(__DIR__, 2) . '/includes/config/session_config.php';
include_once dirname(__DIR__, 2) . '/includes/config/categories_config.php';

// Dołączenie wymaganych plików
include_once dirname(__DIR__, 2) . '/includes/fetch/fetch_categories.php';
include_once dirname(__DIR__, 2) . '/includes/admin/category_actions.php';
include_once dirname(__DIR__, 2) . '/includes/admin/category_sort_helpers.php';
include_once dirname(__DIR__, 2) . '/includes/render/render_categories_table.php';
include_once dirname(__DIR__, 2) . '/includes/modals/categories_modals.php';

// Obsługa akcji dla kategorii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = processCategoryAction($_POST, $connection);
    
    if ($result === true) {
        header('Location: panel.php?view=categories&success=' . ($_POST['action'] === 'delete' ? 'deleted' : ($_POST['action'] === 'add' ? 'added' : 'updated')));
    } else {
        header('Location: panel.php?view=categories&error=' . urlencode($result));
    }
    exit();
}

// Pobranie parametrów sortowania
$sort_column = $_GET['sort'] ?? DEFAULT_CATEGORY_SORT_COLUMN;
$sort_dir = $_GET['dir'] ?? DEFAULT_CATEGORY_SORT_DIRECTION;

// Dołączenie CSS i JS dla kategorii
echo '<script src="../assets/js/admin/utils.js"></script>';
echo '<script src="../assets/js/admin/categories.js"></script>';

// Pobranie i renderowanie kategorii
$categories = fetchCategories($connection, $sort_column, $sort_dir);
echo renderCategoriesTable($categories, $sort_column, $sort_dir);

// Renderowanie modali
echo renderCategoryFormModal();
echo renderDeleteConfirmationModal();