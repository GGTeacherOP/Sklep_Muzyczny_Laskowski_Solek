<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
include_once dirname(__DIR__, 2) . '/includes/config/session_config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Sprawdź czy podano ID dostawy
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing delivery ID']);
    exit();
}

$dostawa_id = mysqli_real_escape_string($connection, $_GET['id']);

// Pobierz szczegóły dostawy
$sql = "SELECT ds.*, i.nazwa as nazwa_instrumentu
        FROM dostawa_szczegoly ds
        JOIN instrumenty i ON ds.instrument_id = i.id
        WHERE ds.dostawa_id = '$dostawa_id'
        ORDER BY ds.id";

$result = mysqli_query($connection, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}

$details = [];
while ($row = mysqli_fetch_assoc($result)) {
    $details[] = [
        'nazwa_instrumentu' => $row['nazwa_instrumentu'],
        'ilosc' => $row['ilosc'],
        'cena_zakupu' => $row['cena_zakupu'],
        'status' => $row['status']
    ];
}

// Zwróć dane w formacie JSON
header('Content-Type: application/json');
echo json_encode($details); 