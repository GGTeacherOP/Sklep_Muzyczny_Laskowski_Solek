<?php
/**
 * Renderuje widok historii zamówień klienta
 */

include_once dirname(__DIR__) . '/config/orders_config.php';

/**
 * Renderuje historię zamówień klienta
 * 
 * @param array $orders Lista zamówień klienta
 * @param int $client_id ID klienta
 * @return string Kod HTML z historią zamówień
 */
function renderClientOrders($orders, $client_id) {
    if (empty($orders)) {
        return '<div class="admin-alert info">Klient nie ma jeszcze żadnych zamówień</div>';
    }
    
    ob_start();
?>
<div class="client-orders">
    <table class="admin-table client-orders-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Status</th>
                <th>Ilość produktów</th>
                <th>Wartość</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo $order['data_zamowienia_format']; ?></td>
                    <td>
                    <span class="<?php echo ORDER_STATUSES[$order['status']]['class']; ?>"><?php echo $order['status']; ?></span>
                    </td>
                    <td><?php echo $order['liczba_produktow']; ?></td>
                    <td><?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
    return ob_get_clean();
} 