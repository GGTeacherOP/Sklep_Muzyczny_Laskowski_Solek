<?php
include_once '../includes/config/db_config.php';

function getCatalogProducts(
    mysqli $connection,
    ?int $category_id = null,
    ?int $producer_id = null,
    ?float $min_price = null,
    ?float $max_price = null,
    string $sort_by = 'nazwa',
    string $sort_direction = 'ASC',
    ?string $search_query = null,
    int $page = 1,
    int $items_per_page = 12
): array {
    $offset = ($page - 1) * $items_per_page;
    
    $query = "
        SELECT 
            i.*,
            k.nazwa as nazwa_kategorii,
            p.nazwa as nazwa_producenta,
            iz.url,
            iz.alt_text,
            ROUND(COALESCE(AVG(io.ocena), 0), 2) as srednia_ocena,
            COUNT(DISTINCT io.id) as liczba_ocen,
            (
                SELECT COUNT(*)
                FROM wypozyczenia w
                WHERE w.instrument_id = i.id
                AND w.status = 'wypożyczone'
            ) as aktualnie_wypozyczone,
            (
                SELECT COUNT(*)
                FROM zamowienie_szczegoly zs
                JOIN zamowienia z ON z.id = zs.zamowienie_id
                WHERE zs.instrument_id = i.id
                AND z.status != 'anulowane'
            ) as liczba_sprzedazy
        FROM instrumenty i
        LEFT JOIN kategorie_instrumentow k ON i.kategoria_id = k.id
        LEFT JOIN producenci p ON i.producent_id = p.id
        LEFT JOIN (
            SELECT instrument_id, url, alt_text
            FROM instrument_zdjecia
            WHERE (instrument_id, kolejnosc) IN (
                SELECT instrument_id, MIN(kolejnosc)
                FROM instrument_zdjecia
                GROUP BY instrument_id
            )
        ) iz ON i.id = iz.instrument_id
        LEFT JOIN instrument_oceny io ON i.id = io.instrument_id
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if ($category_id) {
        $query .= " AND i.kategoria_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($producer_id) {
        $query .= " AND i.producent_id = ?";
        $params[] = $producer_id;
        $types .= "i";
    }
    
    if ($min_price) {
        $query .= " AND i.cena_sprzedazy >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price) {
        $query .= " AND i.cena_sprzedazy <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    if ($search_query) {
        $query .= " AND (i.nazwa LIKE ? OR i.opis LIKE ? OR i.kod_produktu LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    $query .= " GROUP BY i.id";
    
    switch ($sort_by) {
        case 'cena_rosnaco':
            $query .= " ORDER BY i.cena_sprzedazy ASC";
            break;
        case 'cena_malejaco':
            $query .= " ORDER BY i.cena_sprzedazy DESC";
            break;
        case 'ocena':
            $query .= " ORDER BY srednia_ocena DESC, liczba_ocen DESC";
            break;
        case 'popularnosc':
            $query .= " ORDER BY liczba_sprzedazy DESC, aktualnie_wypozyczone DESC";
            break;
        case 'nazwa':
        default:
            $query .= " ORDER BY i.nazwa $sort_direction";
    }
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $connection->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

function getTotalProductsCount(
    mysqli $connection,
    ?int $category_id = null,
    ?int $producer_id = null,
    ?float $min_price = null,
    ?float $max_price = null,
    ?string $search_query = null
): int {
    $query = "
        SELECT COUNT(DISTINCT i.id) as total 
        FROM instrumenty i
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if ($category_id) {
        $query .= " AND i.kategoria_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($producer_id) {
        $query .= " AND i.producent_id = ?";
        $params[] = $producer_id;
        $types .= "i";
    }
    
    if ($min_price) {
        $query .= " AND i.cena_sprzedazy >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price) {
        $query .= " AND i.cena_sprzedazy <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    if ($search_query) {
        $query .= " AND (i.nazwa LIKE ? OR i.opis LIKE ? OR i.kod_produktu LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    $stmt = $connection->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['total'];
}
?> 