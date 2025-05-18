<?php
/**
 * Funkcje obsługujące akcje dla kategorii
 */

/**
 * Przetwarza akcje dla kategorii (dodawanie, edycja, usuwanie)
 * 
 * @param array $postData Dane z formularza $_POST
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool|string True jeśli akcja się powiodła, komunikat błędu w przypadku niepowodzenia
 */
function processCategoryAction($postData, $connection) {
    if (!isset($postData['action'])) {
        return "Brak określonej akcji";
    }

    switch ($postData['action']) {
        case 'add':
            return addCategory($postData, $connection);
            
        case 'update':
            return updateCategory($postData, $connection);
            
        case 'delete':
            return deleteCategory($postData, $connection);
            
        default:
            return "Nieznana akcja: " . $postData['action'];
    }
}

/**
 * Dodaje nową kategorię
 * 
 * @param array $postData Dane z formularza
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool|string True w przypadku powodzenia, komunikat błędu w przypadku niepowodzenia
 */
function addCategory($postData, $connection) {
    if (empty($postData['nazwa'])) {
        return "Nazwa kategorii jest wymagana";
    }
    
    $nazwa = mysqli_real_escape_string($connection, $postData['nazwa']);
    
    if (!mysqli_query($connection, "INSERT INTO kategorie_instrumentow (nazwa) VALUES ('$nazwa')")) {
        return "Błąd podczas dodawania kategorii: " . mysqli_error($connection);
    }
    
    return true;
}

/**
 * Aktualizuje kategorię
 * 
 * @param array $postData Dane z formularza
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool|string True w przypadku powodzenia, komunikat błędu w przypadku niepowodzenia
 */
function updateCategory($postData, $connection) {
    if (empty($postData['category_id']) || empty($postData['nazwa'])) {
        return "ID kategorii i nazwa są wymagane";
    }
    
    $id = mysqli_real_escape_string($connection, $postData['category_id']);
    $nazwa = mysqli_real_escape_string($connection, $postData['nazwa']);
    
    if (!mysqli_query($connection, "UPDATE kategorie_instrumentow SET nazwa = '$nazwa' WHERE id = '$id'")) {
        return "Błąd podczas aktualizacji kategorii: " . mysqli_error($connection);
    }
    
    return true;
}

/**
 * Usuwa kategorię
 * 
 * @param array $postData Dane z formularza
 * @param mysqli $connection Połączenie z bazą danych
 * @return bool|string True w przypadku powodzenia, komunikat błędu w przypadku niepowodzenia
 */
function deleteCategory($postData, $connection) {
    if (empty($postData['category_id'])) {
        return "ID kategorii jest wymagane";
    }
    
    $id = mysqli_real_escape_string($connection, $postData['category_id']);
    
    // Sprawdzamy, czy kategoria ma przypisane instrumenty
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM instrumenty WHERE kategoria_id = '$id'");
    if (!$result) {
        return "Błąd podczas sprawdzania powiązanych instrumentów: " . mysqli_error($connection);
    }
    
    $count = mysqli_fetch_assoc($result)['count'];
    
    if ($count > 0) {
        return "has_products"; // Specjalny kod błędu
    }
    
    if (!mysqli_query($connection, "DELETE FROM kategorie_instrumentow WHERE id = '$id'")) {
        return "Błąd podczas usuwania kategorii: " . mysqli_error($connection);
    }
    
    return true;
} 