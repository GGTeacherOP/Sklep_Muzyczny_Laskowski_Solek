<?php
/**
 * Obsługa żądania AJAX - pobieranie zamówień klienta
 */

// Dołączenie wymaganych plików
require_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
require_once dirname(__DIR__, 2) . '/includes/fetch/fetch_client_orders.php';
require_once dirname(__DIR__, 2) . '/includes/render/render_client_orders.php';

// Sprawdzenie, czy mamy ID klienta
if (!isset($_GET['client_id']) || !is_numeric($_GET['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nie podano prawidłowego ID klienta'
    ]);
    exit;
}

// Pobranie danych klienta
$client_id = (int)$_GET['client_id'];

try {
    // Pobranie zamówień klienta
    $orders = fetchClientOrders($client_id, $connection);
    
    // Renderowanie HTML
    $html = renderClientOrders($orders, $client_id);
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Wystąpił błąd: ' . $e->getMessage()
    ]);
} 