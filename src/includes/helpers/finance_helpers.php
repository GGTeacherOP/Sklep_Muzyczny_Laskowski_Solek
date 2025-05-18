<?php
// Funkcja formatująca kwotę
function formatAmount($amount) {
  return number_format($amount, 2, ',', ' ') . ' zł';
}

// Funkcja pobierająca nazwę miesiąca po polsku
function getPolishMonth($month) {
  $months = [
    1 => 'Styczeń',
    2 => 'Luty',
    3 => 'Marzec',
    4 => 'Kwiecień',
    5 => 'Maj',
    6 => 'Czerwiec',
    7 => 'Lipiec',
    8 => 'Sierpień',
    9 => 'Wrzesień',
    10 => 'Październik',
    11 => 'Listopad',
    12 => 'Grudzień'
  ];
  return $months[$month];
}

// Funkcja do generowania linków sortowania
function getFinanceSortLink($column, $current_sort, $current_dir) {
    $dir = ($column === $current_sort && $current_dir === 'asc') ? 'desc' : 'asc';
    return "?view=finances&sort={$column}&dir={$dir}";
}

// Funkcja do wyświetlania ikony sortowania
function getFinanceSortIcon($column, $current_sort, $current_dir) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($current_dir === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
} 