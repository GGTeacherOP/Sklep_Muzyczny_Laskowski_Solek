<?php
  /** @var mysqli $connection */
  include_once dirname(__DIR__, 2) . '\includes\config\db_config.php';
  include_once dirname(__DIR__, 2) . '\includes\fetch\fetch_all_products.php';

  $products = fetchAllProducts($connection);
?>
<h2>Produkty</h2>
<p>Lista dostępnych produktów. Tutaj możesz zarządzać produktami w sklepie.</p>
<table border="1" cellspacing="0" cellpadding="8">
  <thead>
  <tr>
    <th>ID</th>
    <th>Kod Produktu</th>
    <th>Nazwa</th>
    <th>Opis</th>
    <th>Cena</th>
    <th>Stan Magazynowy</th>
    <th>Producent</th>
    <th>Kategoria</th>
  </tr>
  </thead>
  <tbody>
  <?php while ($product = mysqli_fetch_assoc($products)) : ?>
    <tr>
      <td><?php echo htmlspecialchars($product['id']); ?></td>
      <td><?php echo htmlspecialchars($product['kod_produktu']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa']); ?></td>
      <td><?php echo htmlspecialchars($product['opis']); ?></td>
      <td><?php echo  htmlspecialchars($product['cena']) ?></td>
      <td><?php echo htmlspecialchars($product['stan_magazynowy']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa_producenta']); ?></td>
      <td><?php echo htmlspecialchars($product['nazwa_kategorii']); ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>