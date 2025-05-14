<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
include_once dirname(__DIR__, 2) . '/includes/config/session_config.php';
include_once dirname(__DIR__, 2) . '/includes/config/orders_config.php';

// Dołączenie wymaganych plików
include_once dirname(__DIR__, 2) . '/includes/fetch/fetch_order_details.php';
include_once dirname(__DIR__, 2) . '/includes/fetch/fetch_orders.php';
include_once dirname(__DIR__, 2) . '/includes/admin/order_actions.php';
include_once dirname(__DIR__, 2) . '/includes/admin/sorting_helpers.php';
include_once dirname(__DIR__, 2) . '/includes/render/render_order_details.php';
include_once dirname(__DIR__, 2) . '/includes/render/render_orders_table.php';
include_once dirname(__DIR__, 2) . '/includes/modals/orders_modals.php';

// Obsługa akcji na zamówieniach
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = processOrderAction($_POST, $connection);
    
    if ($result === true) {
        header('Location: panel.php?view=orders&success=' . ($_POST['action'] === 'delete' ? 'deleted' : 'updated'));
    } else {
        header('Location: panel.php?view=orders&error=' . urlencode($result));
    }
    exit();
}

// Pobranie parametrów sortowania
$sort_column = $_GET['sort'] ?? DEFAULT_SORT_COLUMN;
$sort_dir = $_GET['dir'] ?? DEFAULT_SORT_DIRECTION;

// Sprawdzenie, czy mamy podgląd szczegółów
$order_details = null;
if (isset($_GET['view_details']) && is_numeric($_GET['view_details'])) {
    $order_details = fetchOrderDetails($_GET['view_details'], $connection);
}

// Dołączenie CSS i JS dla zamówień
echo '<link rel="stylesheet" href="../assets/css/admin/orders.css">';
echo '<script src="../assets/js/admin/utils.js"></script>';
echo '<script src="../assets/js/admin/orders.js"></script>';

// Renderowanie widoku
if ($order_details) {
    // Widok szczegółów zamówienia
    echo renderOrderDetails($order_details);
} else {
    // Widok listy zamówień
    $orders = fetchOrders($connection, $sort_column, $sort_dir);
    echo renderOrdersTable($orders, $sort_column, $sort_dir);
}

// Renderowanie modali
echo renderStatusChangeModal();
echo renderDeleteConfirmationModal();
?>