<?php
/**
 * Konfiguracja dla modułu kategorii
 */

// Dozwolone kolumny sortowania
const CATEGORY_SORT_COLUMNS = [
    'id', 
    'nazwa', 
    'liczba_instrumentow'
];

// Domyślne wartości sortowania
const DEFAULT_CATEGORY_SORT_COLUMN = 'nazwa';
const DEFAULT_CATEGORY_SORT_DIRECTION = 'asc'; 