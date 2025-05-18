<?php
/**
 * Renderuje tabelę zamówień dla panelu administratora
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Renderuje tabelę zamówień dla panelu administratora
 * 
 * @param mysqli_result $orders_result Wynik zapytania z listą zamówień
 * @param string $sort_column Aktualna kolumna sortowania
 * @param string $sort_dir Aktualny kierunek sortowania
 * @return string Kod HTML z tabelą zamówień
 */
function renderOrdersTable($orders_result, $sort_column, $sort_dir) {
    $base_url = "?view=orders";
    ob_start();
?>
<h2>Zamówienia</h2>
<p>Przeglądaj i zarządzaj zamówieniami klientów.</p>

<div class="admin-filters">
    <div class="admin-search">
        <input type="text" id="orderSearch" class="form-input" placeholder="Szukaj zamówień..." 
                onkeyup="filterTable('orderTable', 'orderSearch', 1)">
    </div>
    <div class="dropdown">
        <button class="dropdown-toggle" type="button" onclick="toggleDropdown('statusDropdown')">
            <span id="statusDropdownText">Wszystkie statusy</span>
            <i class="fa-solid fa-chevron-down"></i>
        </button>
        <ul class="dropdown-menu" id="statusDropdown">
            <li><a href="#" class="dropdown-item" onclick="selectStatus('', 'Wszystkie statusy')">Wszystkie statusy</a></li>
            <li class="dropdown-divider"></li>
            <?php foreach (ORDER_STATUSES as $value => $status): ?>
            <li><a href="#" class="dropdown-item" onclick="selectStatus('<?php echo $value; ?>', '<?php echo $status['label']; ?>')">
                <?php echo $status['label']; ?>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<table id="orderTable" class="admin-table">
    <thead>
        <tr>
            <th><?php echo getSortableHeader('id', 'ID', $sort_column, $sort_dir, $base_url); ?></th>
            <th><?php echo getSortableHeader('nazwa_uzytkownika', 'Klient', $sort_column, $sort_dir, $base_url); ?></th>
            <th><?php echo getSortableHeader('data_zamowienia', 'Data zamówienia', $sort_column, $sort_dir, $base_url); ?></th>
            <th><?php echo getSortableHeader('status', 'Status', $sort_column, $sort_dir, $base_url); ?></th>
            <th><?php echo getSortableHeader('wartosc_calkowita', 'Wartość', $sort_column, $sort_dir, $base_url); ?></th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($order = mysqli_fetch_assoc($orders_result)) : ?>
            <?php $statusClass = ORDER_STATUSES[$order['status']]['class'] ?? 'status-unknown'; ?>
            <tr data-status="<?php echo htmlspecialchars($order['status']); ?>">
                <td><?php echo htmlspecialchars($order['id']); ?></td>
                <td><?php echo htmlspecialchars($order['nazwa_uzytkownika']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($order['data_zamowienia'])); ?></td>
                <td>
                    <span class="admin-status <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </td>
                <td><?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</td>
                <td>
                    <div class="admin-actions">
                        <a href="?view=orders&view_details=<?php echo $order['id']; ?>" class="admin-button">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="admin-button" onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php
    return ob_get_clean();
} 