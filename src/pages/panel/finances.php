<?php
/** @var mysqli $connection */
include_once dirname(__DIR__, 2) . '/includes/config/db_config.php';
include_once dirname(__DIR__, 2) . '/includes/helpers/finance_helpers.php';

// Obsługa sortowania
$sort_column = $_GET['sort'] ?? 'rok';
$sort_dir = $_GET['dir'] ?? 'desc';

// Zabezpieczenie przed SQL Injection przy sortowaniu
$allowed_columns = ['rok', 'miesiac', 'przychody', 'wydatki', 'zysk'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'rok';
}

$sort_dir = strtolower($sort_dir);
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
    $sort_dir = 'desc';
}

// Pobranie lat z danymi
$sql = "SELECT DISTINCT YEAR(data_zamowienia) as rok 
        FROM (
          SELECT data_zamowienia FROM zamowienia
          UNION
          SELECT data_zamowienia FROM dostawy
        ) as daty
        ORDER BY rok DESC";
$lata = mysqli_query($connection, $sql);
$lata_data = [];
while ($row = mysqli_fetch_assoc($lata)) {
    $lata_data[] = $row;
}
mysqli_data_seek($lata, 0);

// Pobranie danych finansowych
$sql = "SELECT 
          YEAR(data) as rok,
          MONTH(data) as miesiac,
          SUM(przychody) as przychody,
          SUM(wydatki_dostawy) as wydatki_dostawy,
          SUM(wydatki_wynagrodzenia) as wydatki_wynagrodzenia,
          SUM(wydatki_dostawy + wydatki_wynagrodzenia) as wydatki_total,
          SUM(przychody - (wydatki_dostawy + wydatki_wynagrodzenia)) as zysk
        FROM (
          SELECT 
            data_zamowienia as data,
            COALESCE(SUM(zs.cena * zs.ilosc), 0) as przychody,
            0 as wydatki_dostawy,
            0 as wydatki_wynagrodzenia
          FROM zamowienia z 
          JOIN zamowienie_szczegoly zs ON z.id = zs.zamowienie_id
          WHERE z.status != 'anulowane'
          GROUP BY YEAR(data_zamowienia), MONTH(data_zamowienia)
          
          UNION ALL
          
          SELECT 
            data_zamowienia as data,
            0 as przychody,
            COALESCE(SUM(ds.cena_zakupu * ds.ilosc), 0) as wydatki_dostawy,
            (
              SELECT COALESCE(SUM(s.wynagrodzenie_miesieczne), 0)
              FROM pracownicy p 
              JOIN stanowiska s ON p.stanowisko_id = s.id
              WHERE YEAR(p.data_zatrudnienia) <= YEAR(d.data_zamowienia)
              AND MONTH(p.data_zatrudnienia) <= MONTH(d.data_zamowienia)
              AND (p.data_zwolnienia IS NULL 
                  OR (YEAR(p.data_zwolnienia) >= YEAR(d.data_zamowienia) 
                      AND MONTH(p.data_zwolnienia) >= MONTH(d.data_zamowienia)))
            ) as wydatki_wynagrodzenia
          FROM dostawy d
          JOIN dostawa_szczegoly ds ON d.id = ds.dostawa_id
          WHERE d.status != 'anulowana'
          GROUP BY YEAR(data_zamowienia), MONTH(data_zamowienia)
        ) as dane
        GROUP BY rok, miesiac
        ORDER BY ";

if ($sort_column === 'rok') {
    $sql .= "rok $sort_dir, miesiac ASC";
} else if ($sort_column === 'miesiac') {
    $sql .= "miesiac $sort_dir, rok DESC";
} else {
    $sql .= "$sort_column $sort_dir";
}

$finanse = mysqli_query($connection, $sql);
?>

<link href="../assets/css/finances.css" rel="stylesheet">

<div class="admin-filters">
  <div class="admin-search">
    <input type="text" id="financeSearch" class="form-input" placeholder="Szukaj po roku..." 
           onkeyup="filterTable('financeTable', 0)">
  </div>
  <div class="dropdown">
    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('yearDropdown')">
      <span id="yearDropdownText">Wszystkie lata</span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <ul class="dropdown-menu" id="yearDropdown">
      <li><a href="#" class="dropdown-item" onclick="selectYear('', 'Wszystkie lata')">Wszystkie lata</a></li>
      <li class="dropdown-divider"></li>
      <?php foreach ($lata_data as $rok) : ?>
        <li><a href="#" class="dropdown-item" onclick="selectYear('<?php echo $rok['rok']; ?>', '<?php echo $rok['rok']; ?>')">
          <?php echo $rok['rok']; ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="admin-content">
  <table id="financeTable" class="admin-table">
    <thead>
      <tr>
        <th>
          <a href="<?php echo getFinanceSortLink('rok', $sort_column, $sort_dir); ?>" class="sort-link">
            Rok <?php echo getFinanceSortIcon('rok', $sort_column, $sort_dir); ?>
          </a>
        </th>
        <th>
          <a href="<?php echo getFinanceSortLink('miesiac', $sort_column, $sort_dir); ?>" class="sort-link">
            Miesiąc <?php echo getFinanceSortIcon('miesiac', $sort_column, $sort_dir); ?>
          </a>
        </th>
        <th>
          <a href="<?php echo getFinanceSortLink('przychody', $sort_column, $sort_dir); ?>" class="sort-link">
            Przychody <?php echo getFinanceSortIcon('przychody', $sort_column, $sort_dir); ?>
          </a>
        </th>
        <th>
          <a href="<?php echo getFinanceSortLink('wydatki', $sort_column, $sort_dir); ?>" class="sort-link">
            Wydatki <?php echo getFinanceSortIcon('wydatki', $sort_column, $sort_dir); ?>
          </a>
        </th>
        <th>
          <a href="<?php echo getFinanceSortLink('zysk', $sort_column, $sort_dir); ?>" class="sort-link">
            Zysk <?php echo getFinanceSortIcon('zysk', $sort_column, $sort_dir); ?>
          </a>
        </th>
        <th>Szczegóły</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = mysqli_fetch_assoc($finanse)) : ?>
        <tr data-year="<?php echo $row['rok']; ?>">
          <td><?php echo $row['rok']; ?></td>
          <td><?php echo getPolishMonth($row['miesiac']); ?></td>
          <td>
            <span class="status-badge success">
              <?php echo formatAmount($row['przychody']); ?>
            </span>
          </td>
          <td>
            <span class="status-badge danger">
              <?php echo formatAmount($row['wydatki_total']); ?>
            </span>
          </td>
          <td>
            <?php
              $zysk_class = $row['zysk'] > 0 ? 'status-badge success' : ($row['zysk'] < 0 ? 'status-badge danger' : 'status-badge warning');
            ?>
            <span class="badge <?php echo $zysk_class; ?>">
              <?php echo formatAmount($row['zysk']); ?>
            </span>
          </td>
          <td>
            <button class="admin-button info" onclick="showDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)">
              <i class="fas fa-info-circle"></i>
            </button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Modal ze szczegółami -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Szczegóły finansowe</h3>
      <span class="close">&times;</span>
    </div>
    <div class="modal-body" id="detailsContent">
      <!-- Zawartość modalu będzie wypełniana przez JavaScript -->
    </div>
  </div>
</div>

<script>
function showDetails(data) {
  const modal = document.getElementById('detailsModal');
  const content = document.getElementById('detailsContent');
  
  const zyskClass = data.zysk > 0 ? 'badge-success' : (data.zysk < 0 ? 'badge-danger' : 'badge-warning');
  
  content.innerHTML = `
    <div class="details-grid">
      <div class="details-row">
        <div class="details-label">Okres:</div>
        <div class="details-value">${getPolishMonth(data.miesiac)} ${data.rok}</div>
      </div>
      <div class="details-row">
        <div class="details-label">Przychody:</div>
        <div class="details-value">
          <span class="badge badge-success">${formatAmount(data.przychody)}</span>
        </div>
      </div>
      <div class="details-row">
        <div class="details-label">Wydatki na dostawy:</div>
        <div class="details-value">
          <span class="badge badge-danger">${formatAmount(data.wydatki_dostawy)}</span>
        </div>
      </div>
      <div class="details-row">
        <div class="details-label">Wydatki na wynagrodzenia:</div>
        <div class="details-value">
          <span class="badge badge-danger">${formatAmount(data.wydatki_wynagrodzenia)}</span>
        </div>
      </div>
      <div class="details-row">
        <div class="details-label">Suma wydatków:</div>
        <div class="details-value">
          <span class="badge badge-danger">${formatAmount(data.wydatki_total)}</span>
        </div>
      </div>
      <div class="details-row">
        <div class="details-label">Zysk:</div>
        <div class="details-value">
          <span class="badge ${zyskClass}">${formatAmount(data.zysk)}</span>
        </div>
      </div>
    </div>
  `;
  
  modal.style.display = "block";
}

// Funkcje pomocnicze do formatowania
function formatAmount(amount) {
  return new Intl.NumberFormat('pl-PL', { 
    style: 'currency', 
    currency: 'PLN',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(amount);
}

function getPolishMonth(month) {
  const months = [
    'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
    'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
  ];
  return months[month - 1];
}

// Obsługa zamykania modalu
document.querySelector('.close').onclick = function() {
  document.getElementById('detailsModal').style.display = "none";
}

window.onclick = function(event) {
  const modal = document.getElementById('detailsModal');
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

// Filtrowanie tabeli
function filterTable(tableId, columnIndex) {
  const input = document.getElementById('financeSearch');
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    const cell = rows[i].getElementsByTagName('td')[columnIndex];
    if (cell) {
      const text = cell.textContent || cell.innerText;
      rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
  }
}

// Filtrowanie po roku
function selectYear(year, text) {
  document.getElementById('yearDropdownText').textContent = text;
  const rows = document.getElementById('financeTable').getElementsByTagName('tr');
  
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    if (year === '' || row.getAttribute('data-year') === year) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  }
  
  toggleDropdown('yearDropdown');
}

// Obsługa dropdownów
function toggleDropdown(dropdownId) {
  document.getElementById(dropdownId).classList.toggle('show');
}

// Zamykanie dropdownów po kliknięciu poza nie
window.onclick = function(event) {
  if (!event.target.matches('.dropdown-toggle')) {
    const dropdowns = document.getElementsByClassName('dropdown-menu');
    for (let dropdown of dropdowns) {
      if (dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
      }
    }
  }
}
</script>

<style>
.finance-details {
  margin-top: 5px;
  font-size: 0.9em;
  color: var(--text-secondary);
}

.finance-detail {
  display: flex;
  justify-content: space-between;
  margin-left: 20px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.details-section {
  background: var(--background-secondary);
  padding: 20px;
  border-radius: 8px;
}

.details-section h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--text-primary);
}

.details-subsection {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.details-subsection .total {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--border-color);
}

.income { color: var(--success); }
.expenses { color: var(--danger); }
.profit { color: var(--success); }
.loss { color: var(--danger); }

@media (max-width: 768px) {
  .finance-detail {
    margin-left: 10px;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style> 