<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Laskowski i Sołek" name="author">
  <meta content="koszyk, produkty, sklep muzyczny" name="keywords">
  <meta content="Koszyk użytkownika sklepu muzycznego" name="description">
  <meta content="index, follow" name="robots">
  <script crossorigin="anonymous" src="https://kit.fontawesome.com/da02356be8.js"></script>
  <link href="cart.css" rel="stylesheet">
  <script defer src="cart.js"></script>
  <script src="_header.js" type="module"></script>
  <title>Koszyk - Sklep Muzyczny</title>
</head>
<body>
<main class="fade-in">
  <header class="header">
    <div class="logo">
      <img alt="Logo Sklepu Muzycznego" src="assets/images/logo_sklepu.png">
    </div>
    <form class="search-bar" role="search">
      <input aria-label="Wyszukiwarka instrumentów" class="search-input" placeholder="Szukaj instrumentów..."
             type="text">
      <button aria-label="Wyszukaj" class="search-button" type="button">
        <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>
    <nav class="tray">
      <button aria-label="Koszyk - aktualnie wyświetlana podstrona" class="tray-item active_subpage"
              title="Przejdź do koszyka" type="button">
        <i aria-hidden="true" class="fa-solid fa-cart-shopping"></i>
        <span>Koszyk</span>
      </button>
      <button aria-label="Profil użytkownika" class="tray-item" title="Przejdź do swojego profilu" type="button">
        <i aria-hidden="true" class="fa-solid fa-user"></i>
        <span>Profil</span>
      </button>
      <button aria-label="Strona główna" class="tray-item" title="Przejdź do strony głównej" type="button">
        <i aria-hidden="true" class="fa-solid fa-home"></i>
        <span>Główna</span>
      </button>
    </nav>
  </header>

  <!--  <section class="cart-container-empty">-->
  <!--    <i class="fa-solid fa-box-open empty-cart-icon"></i>-->
  <!--    <h2>Twój koszyk jest pusty</h2>-->
  <!--    <button>Znajdź coś dla siebie <i class="fa-solid fa-arrow-right"></i></button>-->
  <!--  </section>-->

  <section class="cart-container-full">
    <section class="cart-container">
      <div class="cart-items">
        <h2>Koszyk</h2>

        <div class="cart-section" id="buy-section">
          <h3>Kupno</h3>
          <ul>
            <li class="cart-item">
              <img alt="Perkusja" src="assets/images/drum_set.jpg">
              <div class="cart-item-details">
                <div class="cart-item-text">
                  <div class="cart-item-name">Perkusja</div>
                  <div class="cart-item-category">Perkusyjne</div>
                </div>
                <div class="cart-item-quantity">
                  <button class="quantity-button">
                    <i class="fa-solid fa-minus"></i>
                  </button>
                  <input class="quantity-input" min="1" type="number" value="1">
                  <button class="quantity-button">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
                <div class="cart-item-price">1500 zł</div>
                <button class="remove-button">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </li>
          </ul>
        </div>

        <div class="cart-section" id="rent-section">
          <h3>Wypożyczenie</h3>
          <ul>
            <li class="cart-item">
              <img alt="Perkusja" src="assets/images/drum_set.jpg">
              <div class="cart-item-details">
                <div class="cart-item-text">
                  <div class="cart-item-name">Perkusja</div>
                  <div class="cart-item-category">Perkusyjne</div>
                </div>
                <div class="cart-item-quantity">
                  <button class="quantity-button">
                    <i class="fa-solid fa-minus"></i>
                  </button>
                  <input class="quantity-input" min="1" type="number" value="1">
                  <button class="quantity-button">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
                <div class="cart-item-price">1500 zł</div>
                <button class="remove-button">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </li>
            <li class="cart-item">
              <img alt="Perkusja" src="assets/images/drum_set.jpg">
              <div class="cart-item-details">
                <div class="cart-item-text">
                  <div class="cart-item-name">Perkusja</div>
                  <div class="cart-item-category">Perkusyjne</div>
                </div>
                <div class="cart-item-quantity">
                  <button class="quantity-button">
                    <i class="fa-solid fa-minus"></i>
                  </button>
                  <input class="quantity-input" min="1" type="number" value="1">
                  <button class="quantity-button">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
                <div class="cart-item-price">1500 zł</div>
                <button class="remove-button">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </li>
          </ul>
        </div>

      </div>

      <aside class="cart-summary">
        <div class="cart-summary-inner">
          <h2>Podsumowanie</h2>

          <div class="promo-code-container">
            <input class="promo-code-input" id="promo-code" placeholder="Kod promocyjny" type="text" maxlength="16">
          </div>

          <div class="cart-summary-section">
            <p>Kupno: <span id="total-buy">2000 zł</span></p>
            <p>Wypożyczenie: <span id="total-rent">300 zł</span></p>
          </div>

          <hr>

          <div class="cart-summary-section">
            <p>Koszyk: <span id="subtotal">2300 zł</span></p>
            <p>Zniżka: <span id="discount">0 zł</span></p>
            <p>Dostawa: <span id="delivery">20 zł</span></p>
            <p>Podatek: <span id="tax">0 zł</span></p>
          </div>

          <hr>

          <div class="cart-summary-section">
            <p>Łączna kwota: <span id="total-amount">2320 zł</span></p>
          </div>

        </div>
        <button class="checkout-button">Przejdź do kasy</button>
      </aside>
    </section>
  </section>
</main>
</body>
</html>
