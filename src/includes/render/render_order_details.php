<?php
/**
 * Renderuje widok szczegółów zamówienia
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Renderuje widok szczegółów zamówienia
 * 
 * @param array $data Dane zamówienia z jego szczegółami
 * @return string Kod HTML z widokiem szczegółów zamówienia
 */
function renderOrderDetails($data) {
    $order = $data['order'];
    $items = $data['items'];
    $summary = $data['summary'];
    $statusClass = ORDER_STATUSES[$order['status']]['class'] ?? 'status-unknown';
    
    ob_start();
?>
<div class="order-details-page">
    <div class="admin-actions">
        <a href="?view=orders" class="admin-button">
            <i class="fas fa-arrow-left"></i> Powrót do listy zamówień
        </a>
    </div>
    
    <div class="order-info">
        <div class="info-group">
            <h3>Informacje o zamówieniu #<?php echo $order['id']; ?></h3>
            <p><strong>Data:</strong> <?php echo $order['data_zamowienia']; ?></p>
            <p><strong>Klient:</strong> <?php echo $order['nazwa_klienta']; ?></p>
            <p><strong>Status:</strong> <span class="admin-status <?php echo $statusClass; ?>"><?php echo $order['status']; ?></span></p>
        </div>
        <?php if (isset($order['adres'])): ?>
        <div class="info-group">
            <h3>Adres dostawy</h3>
            <p>
                <?php 
                    $adres = $order['adres'];
                    echo $adres['ulica'] . ' ' . $adres['numer_domu'];
                    if (!empty($adres['numer_mieszkania'])) echo '/' . $adres['numer_mieszkania'];
                    echo ', ' . $adres['kod_pocztowy'] . ' ' . $adres['miasto'];
                    if (!empty($adres['kraj'])) echo ', ' . $adres['kraj'];
                ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <h3>Przedmioty w zamówieniu</h3>
    <table class="admin-table order-items-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Kod</th>
                <th>Cena</th>
                <th>Ilość</th>
                <th>Suma</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $itemTotal = number_format($item['cena'] * $item['ilosc'], 2); ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nazwa']); ?></td>
                    <td><?php echo htmlspecialchars($item['kod_produktu']); ?></td>
                    <td><?php echo $item['cena']; ?> zł</td>
                    <td><?php echo $item['ilosc']; ?></td>
                    <td><?php echo $itemTotal; ?> zł</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="order-summary">
        <div class="order-summary-item">
            <span>Suma częściowa:</span>
            <span><?php echo $summary['subtotal']; ?> zł</span>
        </div>
        
        <?php if ($summary['discount']): ?>
        <div class="order-summary-item discount">
            <span>Rabat (<?php echo $summary['discount_percent']; ?>%):</span>
            <span>-<?php echo $summary['discount']; ?> zł</span>
        </div>
        <?php endif; ?>
        
        <div class="order-summary-item total">
            <span>Razem:</span>
            <span><?php echo $summary['total']; ?> zł</span>
        </div>
    </div>
    
    <div class="admin-actions center">
        <button class="admin-button" onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
            <i class="fas fa-edit"></i> Zmień status
        </button>
        <button class="admin-button danger" onclick="confirmDelete(<?php echo $order['id']; ?>)">
            <i class="fas fa-trash"></i> Usuń zamówienie
        </button>
    </div>
</div>
<?php
    return ob_get_clean();
} 