<?php
/**
 * Konfiguracja dla modułu zamówień
 */

// Dozwolone statusy zamówień
const ORDER_STATUSES = [
    'w przygotowaniu' => [
        'label' => 'W przygotowaniu',
        'class' => 'status-badge warning'
    ],
    'wysłane' => [
        'label' => 'Wysłane',
        'class' => 'status-badge info'
    ],
    'dostarczone' => [
        'label' => 'Dostarczone',
        'class' => 'status-badge success'
    ],
    'anulowane' => [
        'label' => 'Anulowane',
        'class' => 'status-badge danger'
    ]
];

// Dozwolone kolumny sortowania
const ORDER_SORT_COLUMNS = [
    'id', 
    'nazwa_uzytkownika', 
    'data_zamowienia', 
    'status', 
    'wartosc_calkowita'
];

// Domyślne wartości sortowania
const DEFAULT_SORT_COLUMN = 'data_zamowienia';
const DEFAULT_SORT_DIRECTION = 'desc'; 