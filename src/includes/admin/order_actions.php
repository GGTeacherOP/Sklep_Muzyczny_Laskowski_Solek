<?php
/**
 * Funkcje do obsługi akcji na zamówieniach
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Aktualizuje status zamówienia
 * 
 * @param int $order_id ID zamówienia
 * @param string $status Nowy status zamówienia
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool Czy operacja się powiodła
 */
function updateOrderStatus($order_id, $status, $connection) {
    $order_id = mysqli_real_escape_string($connection, $order_id);
    $status = mysqli_real_escape_string($connection, $status);
    
    // Sprawdzenie czy status jest prawidłowy
    if (!array_key_exists($status, ORDER_STATUSES)) {
        return false;
    }
    
    $sql = "UPDATE zamowienia SET status = '$status' WHERE id = '$order_id'";
    return mysqli_query($connection, $sql) ? true : false;
}

/**
 * Usuwa zamówienie i jego szczegóły
 * 
 * @param int $order_id ID zamówienia
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool Czy operacja się powiodła
 */
function deleteOrder($order_id, $connection) {
    $order_id = mysqli_real_escape_string($connection, $order_id);
    
    // Rozpoczęcie transakcji
    mysqli_begin_transaction($connection);
    
    try {
        // Najpierw usuwamy szczegóły zamówienia
        $result1 = mysqli_query($connection, "DELETE FROM zamowienie_szczegoly WHERE zamowienie_id = '$order_id'");
        
        // Następnie usuwamy samo zamówienie
        $result2 = mysqli_query($connection, "DELETE FROM zamowienia WHERE id = '$order_id'");
        
        // Jeśli obie operacje się powiodły, zatwierdzamy transakcję
        if ($result1 && $result2) {
            mysqli_commit($connection);
            return true;
        }
        
        // W przeciwnym przypadku cofamy zmiany
        mysqli_rollback($connection);
        return false;
    } catch (Exception $e) {
        mysqli_rollback($connection);
        return false;
    }
}

/**
 * Przetwarza akcje formularza na zamówieniach
 * 
 * @param array $post Dane z formularza ($_POST)
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool|string True jeśli sukces, komunikat błędu w przypadku niepowodzenia
 */
function processOrderAction($post, $connection) {
    if (!isset($post['action']) || !isset($post['order_id'])) {
        return 'Brakujące parametry akcji';
    }
    
    $order_id = $post['order_id'];
    $action = $post['action'];
    
    switch ($action) {
        case 'update_status':
            if (!isset($post['status'])) {
                return 'Brak statusu do aktualizacji';
            }
            $status = $post['status'];
            return updateOrderStatus($order_id, $status, $connection) ? true : 'Nie udało się zaktualizować statusu';
            
        case 'delete':
            return deleteOrder($order_id, $connection) ? true : 'Nie udało się usunąć zamówienia';
            
        default:
            return 'Nieznana akcja';
    }
} 