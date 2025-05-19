-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 19, 2025 at 07:19 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sm`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `dostawa_szczegoly`
--

CREATE TABLE `dostawa_szczegoly` (
  `id` int(11) NOT NULL,
  `dostawa_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `ilosc` int(11) NOT NULL CHECK (`ilosc` > 0),
  `cena_zakupu` decimal(10,2) NOT NULL CHECK (`cena_zakupu` > 0),
  `status` enum('oczekiwana','dostarczona','anulowana') NOT NULL DEFAULT 'oczekiwana'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dostawa_szczegoly`
--

INSERT INTO `dostawa_szczegoly` (`id`, `dostawa_id`, `instrument_id`, `ilosc`, `cena_zakupu`, `status`) VALUES
(1, 1, 1, 5, 1200.00, 'dostarczona'),
(2, 1, 2, 3, 2000.00, 'dostarczona'),
(3, 1, 5, 2, 600.00, 'dostarczona'),
(4, 2, 3, 2, 3500.00, 'oczekiwana'),
(6, 3, 4, 4, 1800.00, 'oczekiwana'),
(7, 3, 1, 2, 1200.00, 'oczekiwana'),
(8, 3, 2, 1, 2000.00, 'oczekiwana'),
(10, 10, 3, 1, 2599.00, 'oczekiwana'),
(11, 10, 3, 2, 2599.00, 'oczekiwana'),
(12, 11, 2, 2, 1599.00, 'oczekiwana'),
(13, 11, 1, 1, 799.00, 'oczekiwana'),
(14, 12, 5, 5, 599.00, 'dostarczona'),
(15, 12, 2, 2, 1599.00, 'dostarczona');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `dostawy`
--

CREATE TABLE `dostawy` (
  `id` int(11) NOT NULL,
  `data_zamowienia` datetime NOT NULL DEFAULT current_timestamp(),
  `data_dostawy` datetime DEFAULT NULL,
  `status` enum('oczekiwana','dostarczona','anulowana') NOT NULL DEFAULT 'oczekiwana',
  `producent_id` int(11) NOT NULL,
  `pracownik_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dostawy`
--

INSERT INTO `dostawy` (`id`, `data_zamowienia`, `data_dostawy`, `status`, `producent_id`, `pracownik_id`) VALUES
(1, '2025-05-10 09:15:00', '2025-05-15 14:30:00', 'dostarczona', 1, 3),
(2, '2025-05-12 11:20:00', NULL, 'oczekiwana', 2, 4),
(3, '2025-05-14 14:45:00', NULL, 'oczekiwana', 4, 3),
(10, '2025-05-19 00:45:06', NULL, 'anulowana', 3, 2),
(11, '2025-05-19 00:45:55', NULL, 'anulowana', 2, 2),
(12, '2025-05-19 00:47:06', '2025-05-19 00:57:06', 'dostarczona', 5, 2);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `instrumenty`
--

CREATE TABLE `instrumenty` (
  `id` int(11) NOT NULL,
  `kod_produktu` varchar(16) NOT NULL,
  `nazwa` varchar(255) NOT NULL,
  `opis` text NOT NULL,
  `cena_sprzedazy` decimal(10,2) NOT NULL COMMENT 'Cena sprzedaży detalicznej',
  `cena_kupna` decimal(10,2) NOT NULL COMMENT 'Cena zakupu od producenta',
  `cena_wypozyczenia_dzien` decimal(10,2) NOT NULL COMMENT 'Cena wypożyczenia za dzień',
  `stan_magazynowy` int(11) NOT NULL DEFAULT 0 CHECK (`stan_magazynowy` >= 0),
  `producent_id` int(11) NOT NULL,
  `kategoria_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instrumenty`
--

INSERT INTO `instrumenty` (`id`, `kod_produktu`, `nazwa`, `opis`, `cena_sprzedazy`, `cena_kupna`, `cena_wypozyczenia_dzien`, `stan_magazynowy`, `producent_id`, `kategoria_id`) VALUES
(1, 'YAM1234', 'Yamaha Pacifica 112V', 'Gitary elektryczna typu stratocaster', 1499.99, 799.99, 299.99, 20, 1, 1),
(2, 'FEN5678', 'Fender Stratocaster', 'Klasyczna gitara elektryczna', 2499.99, 1599.99, 699.99, 22, 2, 1),
(3, 'GIB4321', 'Gibson Les Paul Standard', 'Luksusowa gitara elektryczna', 3999.99, 2599.99, 1599.99, 10, 3, 1),
(4, 'IBA9876', 'Ibanez RG550', 'Gitara elektryczna o agresywnym brzmieniu', 1899.99, 999.99, 599.99, 25, 4, 1),
(5, 'ROL8765', 'Roland FP-30', 'Keyboard cyfrowy, idealny dla początkujących', 799.99, 599.99, 100.99, 35, 5, 4),
(6, 'KOR6543', 'Korg Kronos', 'Profesjonalny syntezator keyboardowy', 2999.99, 1699.99, 1000.99, 9, 6, 4),
(7, 'FGX800C', 'Yamaha FGX800C Electro-Acoustic Guitar', 'Klasyczna gitara elektroakustyczna typu dreadnought z wycięciem, wykonana z drewna świerkowego i nato. Wyposażona w preamp System66 z wbudowanym tunerem, zapewniającym czyste i dynamiczne brzmienie. Idealna zarówno do gry akustycznej, jak i koncertów na żywo.', 1799.00, 0.00, 0.00, 30, 1, 2);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `instrument_oceny`
--

CREATE TABLE `instrument_oceny` (
  `id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ocena` int(11) NOT NULL CHECK (`ocena` >= 1 and `ocena` <= 5),
  `komentarz` text NOT NULL,
  `czy_edytowana` tinyint(1) NOT NULL DEFAULT 0,
  `data_oceny` datetime NOT NULL DEFAULT current_timestamp(),
  `data_edycji` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instrument_oceny`
--

INSERT INTO `instrument_oceny` (`id`, `instrument_id`, `user_id`, `ocena`, `komentarz`, `czy_edytowana`, `data_oceny`, `data_edycji`) VALUES
(1, 1, 1, 5, 'Świetna gitara do gry na żywo, bardzo komfortowa.', 0, '2025-05-08 14:33:12', NULL),
(2, 2, 1, 4, 'Bardzo dobra gitara, jednak cena jest dość wysoka.', 0, '2025-05-08 14:33:12', NULL),
(3, 3, 1, 5, 'Klasik, świetne brzmienie.', 0, '2025-05-08 14:33:12', NULL),
(4, 4, 1, 5, 'Bardzo szybka i wygodna gitara do shredowania.', 0, '2025-05-08 14:33:12', NULL),
(5, 5, 1, 4, 'Świetny keyboard do nauki, ale brakuje niektórych funkcji profesjonalnych modeli.', 0, '2025-05-08 14:33:12', NULL),
(10, 2, 5, 5, 'Zajebisty produkt, cena świetna, kocham żeńchłopców.', 0, '2025-05-19 17:04:52', NULL),
(11, 5, 5, 5, 'Świetny', 1, '2025-05-19 17:52:43', '2025-05-19 17:54:10');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `instrument_zdjecia`
--

CREATE TABLE `instrument_zdjecia` (
  `id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL,
  `alt_text` varchar(255) NOT NULL,
  `kolejnosc` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instrument_zdjecia`
--

INSERT INTO `instrument_zdjecia` (`id`, `instrument_id`, `url`, `alt_text`, `kolejnosc`) VALUES
(1, 1, 'https://example.com/yamaha_pacifica.jpg', 'Yamaha Pacifica 112V', 1),
(5, 5, 'Roland/FP-30_BK_Top_gal.jpg', 'Roland FP-30 - widok z góry', 1),
(6, 5, 'Roland/FP-30_BK_gal.jpg', 'Roland FP-30 - widok ogólny', 2),
(7, 5, 'Roland/FP-30_BK_ipad_gal.jpg', 'Roland FP-30 - widok z iPadem', 3),
(8, 5, 'Roland/FP-30_BK_jack_gal.jpg', 'Roland FP-30 - widok gniazd', 4),
(9, 5, 'Roland/FP-30_BK_rear_gal.jpg', 'Roland FP-30 - widok z tyłu', 5),
(10, 5, 'Roland/FP-30_BK_DP10_gal.jpg', 'Roland FP-30 z pedałem DP-10', 6),
(11, 5, 'Roland/FP-30_BK_DP2_gal.jpg', 'Roland FP-30 z pedałem DP-2', 7),
(12, 5, 'Roland/FP-30_BK_DR_gal.jpg', 'Roland FP-30 - widok z prawej strony', 8),
(13, 5, 'Roland/FP-30_BK_KS-12.DP-2_gal.jpg', 'Roland FP-30 z podkładką KS-12 i pedałem DP-2', 9),
(14, 5, 'Roland/FP-30_BK_Panel_gal.jpg', 'Roland FP-30 - widok panelu sterowania', 10),
(15, 2, 'Fender/0266420560_fen_ins_hbk_1_nr.png', 'Fender Stratocaster - widok gryfa od tyłu', 5),
(16, 2, 'Fender/0266420560_fen_ins_hft_1_nr.png', 'Fender Stratocaster - widok gryfa z przodu', 4),
(17, 2, 'Fender/0266420560_fen_ins_fbd_1_nr.png', 'Fender Stratocaster - widok z przodu', 1),
(18, 2, 'Fender/0266420560_fen_ins_bck_1_rl.png', 'Fender Stratocaster - widok bokiem od tyłu', 3),
(19, 2, 'Fender/0266420560_fen_ins_frt_1_rr.png', 'Fender Stratocaster - widok bokiem z przodu', 2),
(20, 4, 'Ibanez/ibanez-rg550-purple-neon_front.avif', 'Ibanez RG550 - widok z przodu', 2),
(21, 4, 'Ibanez/ibanez-rg550-purple-neon_back.avif', 'Ibanez RG550 - widok z tyłu', 3),
(22, 4, 'Ibanez/ibanez-rg550-purple-neon_body_front.avif', 'Ibanez RG550 - widok korpusu z przodu', 1),
(23, 4, 'Ibanez/ibanez-rg550-purple-neon_neck_front.avif', 'Ibanez RG550 - widok gryfu z przodu', 4),
(24, 4, 'Ibanez/ibanez-rg550-purple-neon_neck_back.avif', 'Ibanez RG550 - widok gryfu z tyłu', 5),
(25, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_body_front.avif', 'Gibson Les Paul Standard - widok korpusu z przodu', 1),
(26, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_side_front.avif', 'Gibson Les Paul Standard - widok boczny z przodu', 2),
(27, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_side_back.avif', 'Gibson Les Paul Standard - widok boczny z tyłu', 3),
(28, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_neck_front.avif', 'Gibson Les Paul Standard - widok gryfu z przodu', 4),
(29, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_neck_back.avif', 'Gibson Les Paul Standard - widok gryfu z tyłu', 5);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kategorie_instrumentow`
--

CREATE TABLE `kategorie_instrumentow` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategorie_instrumentow`
--

INSERT INTO `kategorie_instrumentow` (`id`, `nazwa`) VALUES
(3, 'Basy'),
(2, 'Gitary akustyczne'),
(1, 'Gitary elektryczne'),
(4, 'Keyboardy'),
(5, 'Perkusja'),
(8, 'Syntezatory'),
(6, 'Wiosła');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `klienci`
--

CREATE TABLE `klienci` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klienci`
--

INSERT INTO `klienci` (`id`, `uzytkownik_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kody_promocyjne`
--

CREATE TABLE `kody_promocyjne` (
  `id` int(11) NOT NULL,
  `kod` varchar(16) NOT NULL,
  `znizka` decimal(5,2) NOT NULL CHECK (`znizka` > 0 and `znizka` <= 100),
  `data_rozpoczecia` datetime NOT NULL,
  `data_zakonczenia` datetime NOT NULL,
  `aktywna` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kody_promocyjne`
--

INSERT INTO `kody_promocyjne` (`id`, `kod`, `znizka`, `data_rozpoczecia`, `data_zakonczenia`, `aktywna`) VALUES
(1, 'WIOSNA2025', 15.00, '2025-03-01 00:00:00', '2025-06-30 00:00:00', 1),
(2, 'BLACKFRIDAY', 30.00, '2025-11-27 00:00:00', '2025-11-29 00:00:00', 1),
(3, 'XMAS2025', 10.00, '2025-12-19 22:00:00', '2025-12-24 22:00:00', 1),
(4, 'LATO2025', 15.00, '2025-06-01 23:03:00', '2025-08-31 23:03:00', 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `koszyk`
--

CREATE TABLE `koszyk` (
  `id` int(11) NOT NULL,
  `klient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `koszyk`
--

INSERT INTO `koszyk` (`id`, `klient_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `koszyk_szczegoly`
--

CREATE TABLE `koszyk_szczegoly` (
  `id` int(11) NOT NULL,
  `koszyk_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `ilosc` int(11) NOT NULL DEFAULT 1 CHECK (`ilosc` > 0),
  `cena` decimal(10,2) NOT NULL CHECK (`cena` > 0),
  `typ` enum('kupno','wypozyczenie') NOT NULL DEFAULT 'kupno',
  `okres_wypozyczenia` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `koszyk_szczegoly`
--

INSERT INTO `koszyk_szczegoly` (`id`, `koszyk_id`, `instrument_id`, `ilosc`, `cena`, `typ`, `okres_wypozyczenia`) VALUES
(1, 1, 1, 1, 1499.99, 'kupno', '2025-05-08'),
(2, 2, 2, 1, 2499.99, 'kupno', '2025-05-08'),
(4, 4, 4, 1, 1899.99, 'wypozyczenie', '2025-05-31'),
(5, 5, 5, 1, 799.99, 'kupno', '2025-05-08'),
(7, 3, 3, 1, 3999.99, '', '2025-05-19'),
(8, 3, 4, 1, 1899.99, '', '2025-05-19');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `pracownicy`
--

CREATE TABLE `pracownicy` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `stanowisko_id` int(11) NOT NULL,
  `data_zatrudnienia` datetime NOT NULL DEFAULT current_timestamp(),
  `identyfikator` varchar(4) NOT NULL,
  `data_zwolnienia` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `pracownicy`
--

INSERT INTO `pracownicy` (`id`, `uzytkownik_id`, `stanowisko_id`, `data_zatrudnienia`, `identyfikator`, `data_zwolnienia`) VALUES
(1, 1, 1, '2025-05-08 14:33:12', '0159', NULL),
(2, 2, 2, '2025-05-08 14:33:12', '1594', NULL),
(3, 3, 3, '2025-05-08 14:33:12', '7494', NULL),
(4, 4, 4, '2025-05-08 14:33:12', '2690', NULL),
(5, 5, 5, '2025-05-08 14:33:12', '0969', NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `producenci`
--

CREATE TABLE `producenci` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `producenci`
--

INSERT INTO `producenci` (`id`, `nazwa`) VALUES
(2, 'Fender'),
(3, 'Gibson'),
(4, 'Ibanez'),
(6, 'Korg'),
(5, 'Roland'),
(1, 'Yamaha');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `stanowiska`
--

CREATE TABLE `stanowiska` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL,
  `wynagrodzenie_miesieczne` decimal(10,2) NOT NULL CHECK (`wynagrodzenie_miesieczne` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stanowiska`
--

INSERT INTO `stanowiska` (`id`, `nazwa`, `wynagrodzenie_miesieczne`) VALUES
(1, 'pracownik', 4000.00),
(2, 'manager', 6000.00),
(3, 'właściciel', 10000.00),
(4, 'informatyk', 7000.00),
(5, 'sekretarka', 4500.00);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `nazwa_uzytkownika` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `data_rejestracji` datetime NOT NULL DEFAULT current_timestamp(),
  `typ` enum('pracownik','klient') NOT NULL DEFAULT 'klient'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `nazwa_uzytkownika`, `email`, `haslo`, `data_rejestracji`, `typ`) VALUES
(1, 'Jan', 'jan.kowalski@example.com', 'password123', '2025-05-08 14:33:12', 'pracownik'),
(2, 'Anna', 'anna.nowak@example.com', 'password123', '2025-05-08 14:33:12', 'pracownik'),
(3, 'Piotr', 'piotr.zielinski@example.com', 'password123', '2025-05-08 14:33:12', 'pracownik'),
(4, 'Maria', 'maria.wisniewska@gmail.com', 'password123', '2025-05-08 14:33:12', 'pracownik'),
(5, 'Adam', 'adam.kaczmarek@example.com', 'password123', '2025-05-08 14:33:12', 'pracownik');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wiadomosci`
--

CREATE TABLE `wiadomosci` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `temat` varchar(255) NOT NULL,
  `tresc` text NOT NULL,
  `data_wyslania` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('nowa','w_trakcie','zakonczona','archiwalna') NOT NULL DEFAULT 'nowa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wypozyczenia`
--

CREATE TABLE `wypozyczenia` (
  `id` int(11) NOT NULL,
  `klient_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `data_wypozyczenia` datetime NOT NULL DEFAULT current_timestamp(),
  `data_zwrotu` datetime DEFAULT NULL,
  `cena_wypozyczenia` decimal(10,2) NOT NULL CHECK (`cena_wypozyczenia` > 0),
  `status` enum('wypożyczone','zwrócone','uszkodzone','anulowane') NOT NULL DEFAULT 'wypożyczone'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wypozyczenia`
--

INSERT INTO `wypozyczenia` (`id`, `klient_id`, `instrument_id`, `data_wypozyczenia`, `data_zwrotu`, `cena_wypozyczenia`, `status`) VALUES
(1, 1, 4, '2025-05-08 14:33:13', NULL, 100.00, 'wypożyczone'),
(2, 2, 5, '2025-05-08 14:33:13', NULL, 50.00, 'wypożyczone'),
(3, 3, 4, '2025-05-08 14:33:13', NULL, 100.00, 'wypożyczone'),
(4, 4, 3, '2025-05-08 14:33:13', NULL, 200.00, 'uszkodzone'),
(5, 5, 2, '2025-05-08 14:33:13', NULL, 120.00, 'wypożyczone');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zamowienia`
--

CREATE TABLE `zamowienia` (
  `id` int(11) NOT NULL,
  `klient_id` int(11) NOT NULL,
  `data_zamowienia` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('w przygotowaniu','wysłane','dostarczone','anulowane') NOT NULL DEFAULT 'w przygotowaniu',
  `kod_promocyjny_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zamowienia`
--

INSERT INTO `zamowienia` (`id`, `klient_id`, `data_zamowienia`, `status`, `kod_promocyjny_id`) VALUES
(1, 1, '2025-05-08 14:33:13', 'w przygotowaniu', NULL),
(2, 2, '2025-05-08 14:33:13', 'wysłane', NULL),
(3, 3, '2025-05-08 14:33:13', 'dostarczone', NULL),
(4, 4, '2025-05-08 14:33:13', 'anulowane', NULL),
(5, 5, '2025-05-08 14:33:13', 'wysłane', NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zamowienie_szczegoly`
--

CREATE TABLE `zamowienie_szczegoly` (
  `id` int(11) NOT NULL,
  `zamowienie_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `ilosc` int(11) NOT NULL CHECK (`ilosc` > 0),
  `cena` decimal(10,2) NOT NULL CHECK (`cena` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zamowienie_szczegoly`
--

INSERT INTO `zamowienie_szczegoly` (`id`, `zamowienie_id`, `instrument_id`, `ilosc`, `cena`) VALUES
(1, 1, 2, 1, 1499.99),
(2, 2, 2, 1, 2499.99),
(3, 3, 3, 1, 3999.99),
(4, 4, 4, 1, 1899.99),
(5, 5, 5, 1, 799.99);

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `dostawa_szczegoly`
--
ALTER TABLE `dostawa_szczegoly`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dostawa_id` (`dostawa_id`),
  ADD KEY `instrument_id` (`instrument_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeksy dla tabeli `dostawy`
--
ALTER TABLE `dostawy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `producent_id` (`producent_id`),
  ADD KEY `pracownik_id` (`pracownik_id`);

--
-- Indeksy dla tabeli `instrumenty`
--
ALTER TABLE `instrumenty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kod_produktu` (`kod_produktu`),
  ADD UNIQUE KEY `nazwa` (`nazwa`),
  ADD KEY `idx_kod_produktu` (`kod_produktu`),
  ADD KEY `idx_producent_id` (`producent_id`),
  ADD KEY `idx_kategoria_id` (`kategoria_id`);

--
-- Indeksy dla tabeli `instrument_oceny`
--
ALTER TABLE `instrument_oceny`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_user_instrument` (`user_id`,`instrument_id`),
  ADD KEY `idx_instrument_id_ocena` (`instrument_id`,`ocena`),
  ADD KEY `idx_instrument_user` (`instrument_id`,`user_id`);

--
-- Indeksy dla tabeli `instrument_zdjecia`
--
ALTER TABLE `instrument_zdjecia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instrument_id` (`instrument_id`);

--
-- Indeksy dla tabeli `kategorie_instrumentow`
--
ALTER TABLE `kategorie_instrumentow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `klienci`
--
ALTER TABLE `klienci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `kody_promocyjne`
--
ALTER TABLE `kody_promocyjne`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kod` (`kod`),
  ADD KEY `idx_kod_promocyjny` (`kod`);

--
-- Indeksy dla tabeli `koszyk`
--
ALTER TABLE `koszyk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_klient_id` (`klient_id`);

--
-- Indeksy dla tabeli `koszyk_szczegoly`
--
ALTER TABLE `koszyk_szczegoly`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_koszyk_id` (`koszyk_id`),
  ADD KEY `idx_instrument_id` (`instrument_id`);

--
-- Indeksy dla tabeli `pracownicy`
--
ALTER TABLE `pracownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identyfikator` (`identyfikator`),
  ADD KEY `idx_uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `idx_stanowisko_id` (`stanowisko_id`);

--
-- Indeksy dla tabeli `producenci`
--
ALTER TABLE `producenci`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `stanowiska`
--
ALTER TABLE `stanowiska`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indeksy dla tabeli `wiadomosci`
--
ALTER TABLE `wiadomosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeksy dla tabeli `wypozyczenia`
--
ALTER TABLE `wypozyczenia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instrument_id` (`instrument_id`),
  ADD KEY `idx_klient_id_status` (`klient_id`,`status`);

--
-- Indeksy dla tabeli `zamowienia`
--
ALTER TABLE `zamowienia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kod_promocyjny_id` (`kod_promocyjny_id`),
  ADD KEY `idx_klient_id_status` (`klient_id`,`status`);

--
-- Indeksy dla tabeli `zamowienie_szczegoly`
--
ALTER TABLE `zamowienie_szczegoly`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_zamowienie_id` (`zamowienie_id`),
  ADD KEY `idx_instrument_id` (`instrument_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dostawa_szczegoly`
--
ALTER TABLE `dostawa_szczegoly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dostawy`
--
ALTER TABLE `dostawy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `instrumenty`
--
ALTER TABLE `instrumenty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `instrument_oceny`
--
ALTER TABLE `instrument_oceny`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `instrument_zdjecia`
--
ALTER TABLE `instrument_zdjecia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `kategorie_instrumentow`
--
ALTER TABLE `kategorie_instrumentow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `klienci`
--
ALTER TABLE `klienci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `kody_promocyjne`
--
ALTER TABLE `kody_promocyjne`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `koszyk`
--
ALTER TABLE `koszyk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `koszyk_szczegoly`
--
ALTER TABLE `koszyk_szczegoly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pracownicy`
--
ALTER TABLE `pracownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `producenci`
--
ALTER TABLE `producenci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stanowiska`
--
ALTER TABLE `stanowiska`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wiadomosci`
--
ALTER TABLE `wiadomosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wypozyczenia`
--
ALTER TABLE `wypozyczenia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `zamowienia`
--
ALTER TABLE `zamowienia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `zamowienie_szczegoly`
--
ALTER TABLE `zamowienie_szczegoly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dostawa_szczegoly`
--
ALTER TABLE `dostawa_szczegoly`
  ADD CONSTRAINT `dostawa_szczegoly_ibfk_1` FOREIGN KEY (`dostawa_id`) REFERENCES `dostawy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dostawa_szczegoly_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dostawy`
--
ALTER TABLE `dostawy`
  ADD CONSTRAINT `dostawy_ibfk_1` FOREIGN KEY (`producent_id`) REFERENCES `producenci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dostawy_ibfk_2` FOREIGN KEY (`pracownik_id`) REFERENCES `pracownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instrumenty`
--
ALTER TABLE `instrumenty`
  ADD CONSTRAINT `instrumenty_ibfk_1` FOREIGN KEY (`producent_id`) REFERENCES `producenci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instrumenty_ibfk_2` FOREIGN KEY (`kategoria_id`) REFERENCES `kategorie_instrumentow` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instrument_oceny`
--
ALTER TABLE `instrument_oceny`
  ADD CONSTRAINT `instrument_oceny_ibfk_1` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instrument_oceny_user_fk` FOREIGN KEY (`user_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instrument_zdjecia`
--
ALTER TABLE `instrument_zdjecia`
  ADD CONSTRAINT `instrument_zdjecia_ibfk_1` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `klienci`
--
ALTER TABLE `klienci`
  ADD CONSTRAINT `klienci_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `koszyk`
--
ALTER TABLE `koszyk`
  ADD CONSTRAINT `koszyk_ibfk_1` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `koszyk_szczegoly`
--
ALTER TABLE `koszyk_szczegoly`
  ADD CONSTRAINT `koszyk_szczegoly_ibfk_1` FOREIGN KEY (`koszyk_id`) REFERENCES `koszyk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `koszyk_szczegoly_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pracownicy`
--
ALTER TABLE `pracownicy`
  ADD CONSTRAINT `pracownicy_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pracownicy_ibfk_2` FOREIGN KEY (`stanowisko_id`) REFERENCES `stanowiska` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wypozyczenia`
--
ALTER TABLE `wypozyczenia`
  ADD CONSTRAINT `wypozyczenia_ibfk_1` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wypozyczenia_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zamowienia`
--
ALTER TABLE `zamowienia`
  ADD CONSTRAINT `zamowienia_ibfk_1` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zamowienia_ibfk_2` FOREIGN KEY (`kod_promocyjny_id`) REFERENCES `kody_promocyjne` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `zamowienie_szczegoly`
--
ALTER TABLE `zamowienie_szczegoly`
  ADD CONSTRAINT `zamowienie_szczegoly_ibfk_1` FOREIGN KEY (`zamowienie_id`) REFERENCES `zamowienia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zamowienie_szczegoly_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
