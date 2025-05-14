<?php
/**
 * Obsługa żądania AJAX - pobieranie szczegółów zamówienia
 */

// Dołączenie wymaganych plików
require_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
require_once dirname(__DIR__, 2) . '/includes/fetch/fetch_order_details.php';

// Sprawdzenie, czy mamy ID zamówienia
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nie podano prawidłowego ID zamówienia'
    ]);
    exit;
}

// Pobranie danych zamówienia
$order_id = (int)$_GET['order_id'];

try {
    // Pobranie szczegółów zamówienia
    $order_details = fetchOrderDetails($order_id, $connection);
    
    if (!$order_details) {
        echo json_encode([
            'success' => false,
            'message' => 'Nie znaleziono zamówienia o podanym ID'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order_details['order'],
        'items' => $order_details['items'],
        'summary' => $order_details['summary']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Wystąpił błąd: ' . $e->getMessage()
    ]);
} 