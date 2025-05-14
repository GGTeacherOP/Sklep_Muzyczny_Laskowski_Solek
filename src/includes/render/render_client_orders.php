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
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo $order['data_zamowienia_format']; ?></td>
                    <td>
                        <span class="admin-status <?php echo $order['statusClass']; ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $order['liczba_produktow']; ?></td>
                    <td><?php echo number_format($order['wartosc_calkowita'], 2); ?> zł</td>
                    <td>
                        <div class="admin-actions">
                            <button class="admin-button" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="clientOrderDetails" class="order-details-container" style="display: none; margin-top: 20px;">
    <h3>Szczegóły zamówienia</h3>
    <div id="orderDetailsContent">
        <p class="loading">Ładowanie szczegółów...</p>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    const detailsContainer = document.getElementById('clientOrderDetails');
    const detailsContent = document.getElementById('orderDetailsContent');
    
    detailsContainer.style.display = 'block';
    detailsContent.innerHTML = '<p class="loading">Ładowanie szczegółów...</p>';
    
    // Pobierz szczegóły zamówienia przez AJAX
    fetch(`../includes/ajax/get_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="order-items">';
                html += '<table class="admin-table">';
                html += '<thead><tr><th>Produkt</th><th>Kod</th><th>Cena</th><th>Ilość</th><th>Suma</th></tr></thead>';
                html += '<tbody>';
                
                data.items.forEach(item => {
                    const itemTotal = (item.cena * item.ilosc).toFixed(2);
                    html += `<tr>
                        <td>${item.nazwa}</td>
                        <td>${item.kod_produktu}</td>
                        <td>${item.cena} zł</td>
                        <td>${item.ilosc}</td>
                        <td>${itemTotal} zł</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                
                // Podsumowanie
                html += '<div class="order-summary">';
                html += `<div class="order-summary-item"><span>Suma częściowa:</span><span>${data.summary.subtotal} zł</span></div>`;
                
                if (data.summary.discount) {
                    html += `<div class="order-summary-item discount">
                        <span>Rabat (${data.summary.discount_percent}%):</span>
                        <span>-${data.summary.discount} zł</span>
                    </div>`;
                }
                
                html += `<div class="order-summary-item total"><span>Razem:</span><span>${data.summary.total} zł</span></div>`;
                html += '</div>';
                html += '</div>';
                
                detailsContent.innerHTML = html;
            } else {
                detailsContent.innerHTML = '<div class="admin-alert error">Nie udało się pobrać szczegółów zamówienia</div>';
            }
        })
        .catch(error => {
            detailsContent.innerHTML = '<div class="admin-alert error">Wystąpił błąd podczas pobierania szczegółów</div>';
            console.error('Error:', error);
        });
}
</script>
<?php
    return ob_get_clean();
} 