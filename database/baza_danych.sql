-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 27, 2025 at 12:52 AM
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
(6, 3, 4, 4, 1800.00, 'dostarczona'),
(7, 3, 1, 2, 1200.00, 'dostarczona'),
(8, 3, 2, 1, 2000.00, 'dostarczona'),
(10, 10, 3, 1, 2599.00, 'oczekiwana'),
(11, 10, 3, 2, 2599.00, 'oczekiwana'),
(12, 11, 2, 2, 1599.00, 'oczekiwana'),
(13, 11, 1, 1, 799.00, 'oczekiwana'),
(14, 12, 5, 5, 599.00, 'dostarczona'),
(15, 12, 2, 2, 1599.00, 'dostarczona'),
(16, 13, 1, 10, 799.00, 'dostarczona');

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
(3, '2025-05-14 14:45:00', '2025-05-20 01:41:39', 'dostarczona', 4, 3),
(10, '2025-05-19 00:45:06', NULL, 'anulowana', 3, 2),
(11, '2025-05-19 00:45:55', NULL, 'anulowana', 2, 2),
(12, '2025-05-19 00:47:06', '2025-05-19 00:57:06', 'dostarczona', 5, 2),
(13, '2025-05-22 22:32:13', '2025-05-22 22:32:20', 'dostarczona', 1, 3);

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
(1, 'YAM1234', 'Yamaha Pacifica 112V', 'Gitary elektryczna typu stratocaster', 1499.99, 799.99, 299.99, 0, 1, 1),
(2, 'FEN5678', 'Fender Stratocaster', 'Klasyczna gitara elektryczna', 2499.99, 1599.99, 699.99, 23, 2, 1),
(3, 'GIB4321', 'Gibson Les Paul Standard', 'Luksusowa gitara elektryczna', 3999.99, 2599.99, 1599.99, 10, 3, 1),
(4, 'IBA9876', 'Ibanez RG550', 'Gitara elektryczna o agresywnym brzmieniu', 1899.99, 999.99, 599.99, 26, 4, 1),
(5, 'ROL8765', 'Roland FP-30', 'Keyboard cyfrowy, idealny dla początkujących', 799.99, 599.99, 100.99, 34, 5, 4),
(6, 'KOR6543', 'Korg Kronos', 'Profesjonalny syntezator keyboardowy', 2999.99, 1699.99, 1000.99, 9, 6, 4),
(7, 'FGX800C', 'Yamaha FGX800C', 'Klasyczna gitara elektroakustyczna typu dreadnought z wycięciem, wykonana z drewna świerkowego i nato. Wyposażona w preamp System66 z wbudowanym tunerem.', 1799.00, 899.00, 299.00, 30, 1, 2),
(8, 'CASS100', 'Casio CT-S100', 'Lekki i przenośny keyboard z 61 klawiszami, idealny dla początkujących. Wyposażony w 400 brzmień i 77 rytmów.', 899.99, 499.99, 99.99, 15, 7, 4),
(9, 'PEA1234', 'Pearl Export', 'Komplet perkusyjny dla początkujących i średniozaawansowanych. Zestaw 5-częściowy z talerzami.', 3499.99, 1999.99, 499.99, 8, 8, 5),
(10, 'GRE5678', 'Gretsch G2622 Streamliner', 'Gitara elektryczna typu hollow body, idealna do jazzu i bluesa. Wyposażona w przetworniki BroadTron™.', 2299.99, 1299.99, 399.99, 12, 9, 1),
(12, 'IBA3456', 'Ibanez SR300E', 'Gitara basowa 4-strunowa z aktywną elektroniką. Idealna do muzyki rockowej i metalowej.', 1599.99, 899.99, 299.99, 14, 4, 3),
(13, 'FEN7890', 'Fender Precision Bass', 'Klasyczna gitara basowa 4-strunowa. Kultowy instrument używany przez największych basistów.', 2999.99, 1799.99, 499.99, 10, 2, 3),
(14, 'KOR9012', 'Korg MS-20', 'Legendarny syntezator monofoniczny z filtrem Korg 35. Używany przez największych artystów.', 3999.99, 2499.99, 799.99, 5, 6, 8),
(15, 'ROL3456', 'Roland JD-XA', 'Hybrydowy syntezator łączący analogowe i cyfrowe brzmienia. 49 klawiszy z aftertouch.', 4999.99, 2999.99, 999.99, 7, 5, 8),
(16, 'CAS7600', 'Casio WK-7600', 'Zaawansowany keyboard z 76 klawiszami. Wyposażony w 820 brzmień i 260 rytmów.', 1999.99, 1199.99, 299.99, 20, 7, 4),
(17, 'PEA5678', 'Pearl Masters', 'Profesjonalny zestaw perkusyjny. Najwyższa jakość wykonania i brzmienia.', 8999.99, 4999.99, 1499.99, 3, 8, 5),
(18, 'GRE9012', 'Gretsch Catalina Club', 'Kompaktowy zestaw perkusyjny w stylu jazzowym. Idealny do małych klubów i studyjnych nagrań.', 3499.99, 1999.99, 499.99, 6, 9, 5),
(19, 'FEN9012', 'Fender American Professional II', 'Gitara elektryczna typu stratocaster, wykonana w USA. Najwyższa jakość i brzmienie.', 4999.99, 2999.99, 999.99, 8, 2, 1),
(20, 'GIB5678', 'Gibson ES-335', 'Kultowa gitara elektryczna typu semi-hollow body. Używana przez największych gitarzystów bluesowych i jazzowych.', 5999.99, 3499.99, 1199.99, 5, 3, 1);

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
(29, 3, 'Gibson/gibson-les-paul-standard-60s-plain-top-ebony-top_neck_back.avif', 'Gibson Les Paul Standard - widok gryfu z tyłu', 5),
(30, 8, 'Casio/casio-ct-s100_front.avif', 'Casio CT-S100 - widok z przodu', 1),
(31, 8, 'Casio/casio-ct-s100_front_side.avif', 'Casio CT-S100 - widok z przodu i boku', 2),
(32, 8, 'Casio/casio-ct-s100_front_side_second.avif', 'Casio CT-S100 - widok z przodu i boku (2)', 3),
(33, 8, 'Casio/casio-ct-s100_top_front.avif', 'Casio CT-S100 - widok z góry i przodu', 4),
(34, 8, 'Casio/casio-ct-s100_back.avif', 'Casio CT-S100 - widok z tyłu', 5),
(35, 8, 'Casio/casio-ct-s100_closeup_keys.avif', 'Casio CT-S100 - zbliżenie na klawiaturę', 6),
(36, 8, 'Casio/casio-ct-s100_closeup_back.avif', 'Casio CT-S100 - zbliżenie na tył instrumentu', 7),
(37, 16, 'Casio/WK-7600_1_top.jpg', 'Casio WK-7600 - widok z góry', 1),
(38, 16, 'Casio/WK-7600_2_front.jpg', 'Casio WK-7600 - widok przodu', 2),
(39, 16, 'Casio/WK-7600_3_side.jpg', 'Casio WK-7600 - widok z boku', 3),
(40, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb.avif', 'Fender Precision Bass - widok ogólny', 1),
(41, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb (1).avif', 'Fender Precision Bass - widok z przodu', 2),
(42, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb (2).avif', 'Fender Precision Bass - widok z boku', 3),
(43, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb (3).avif', 'Fender Precision Bass - widok z tyłu', 4),
(44, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb (4).avif', 'Fender Precision Bass - widok gryfu', 5),
(45, 13, 'Fender/fender-de-player-precision-bass-mn-sns-dtb (5).avif', 'Fender Precision Bass - widok szczegółowy', 6),
(51, 18, 'Gretsch/gretsch-catalina-maple-satin-deep-cherry-burst-rock-set.avif', 'Gretsch Catalina Club - widok zestawu perkusyjnego', 1),
(52, 20, 'Fender/0113900803_fen_ins_bck_1_rl.png', 'Fender American Professional II - widok tył', 4),
(53, 20, 'Fender/0113900803_fen_ins_cbr_1_nr.png', 'Fender American Professional II - widok całości', 2),
(54, 20, 'Fender/0113900803_fen_ins_fbd_1_nr.png', 'Fender American Professional II - widok z przodu', 1),
(55, 20, 'Fender/0113900803_fen_ins_frt_1_rr.png', 'Fender American Professional II - widok bokiem z przodu', 3),
(56, 20, 'Fender/0113900803_fen_ins_hbk_1_nr.png', 'Fender American Professional II - widok gryfu od tyłu', 6),
(57, 20, 'Fender/0113900803_fen_ins_hft_1_nr.png', 'Fender American Professional II - widok gryfu z przodu', 5),
(58, 10, 'Gretsch/gretsch-g2622t-streamliner-arb.avif', 'Gretsch G2622 Streamliner - widok ogólny', 1),
(59, 10, 'Gretsch/gretsch-g2622t-streamliner-arb (1).avif', 'Gretsch G2622 Streamliner - widok z tyłu', 3),
(60, 10, 'Gretsch/gretsch-g2622t-streamliner-arb (2).avif', 'Gretsch G2622 Streamliner - widok z przodu', 2),
(61, 10, 'Gretsch/gretsch-g2622t-streamliner-arb (3).avif', 'Gretsch G2622 Streamliner - widok gryfu z przodu', 4),
(62, 10, 'Gretsch/gretsch-g2622t-streamliner-arb (4).avif', 'Gretsch G2622 Streamliner - widok gryfu z tyłu', 5),
(63, 14, 'Korg/korg-ms-20-mini.avif', 'Korg MS-20 - widok z przodu', 1),
(64, 14, 'Korg/korg-ms-20-mini (1).avif', 'Korg MS-20 - widok z góry', 2),
(65, 14, 'Korg/korg-ms-20-mini (2).avif', 'Korg MS-20 - widok z przodu (kable)', 3),
(66, 14, 'Korg/korg-ms-20-mini (3).avif', 'Korg MS-20 - widok z boku', 4),
(67, 14, 'Korg/korg-ms-20-mini (4).avif', 'Korg MS-20 - widok z boku (2)', 5),
(68, 14, 'Korg/korg-ms-20-mini (5).avif', 'Korg MS-20 - widok pokręteł', 6),
(69, 14, 'Korg/korg-ms-20-mini (6).avif', 'Korg MS-20 - widok wejść', 7),
(70, 14, 'Korg/korg-ms-20-mini (7).avif', 'Korg MS-20 - widok z tyłu', 8),
(71, 7, 'Yamaha/yamaha-fgx800c-nt.avif', 'Yamaha FGX800C - widok ogólny', 1),
(72, 9, 'Pearl/pol_pl_Pearl-Export-Rock-Jet-Black-31-shellset-4958_5.webp', 'Pearl Export - widok z przodu', 1),
(73, 9, 'Pearl/pol_pl_Pearl-Export-Rock-Jet-Black-31-shellset-4958_3.webp', 'Pearl Export - widok z boku', 2),
(74, 9, 'Pearl/pol_pl_Pearl-Export-Rock-Jet-Black-31-shellset-4958_4.webp', 'Pearl Export - widok z tyłu', 3),
(75, 9, 'Pearl/pol_pl_Pearl-Export-Rock-Jet-Black-31-shellset-4958_2.webp', 'Pearl Export - widok przybliżony bębnów', 4),
(76, 9, 'Pearl/pol_pl_Pearl-Export-Rock-Jet-Black-31-shellset-4958_6.webp', 'Pearl Export - widok bębna', 5),
(78, 12, 'Ibanez/ibanez-sr300e-iron-pewter (2).avif', 'Ibanez SR300E - widok przybliżony z przodu', 1),
(79, 12, 'Ibanez/ibanez-sr300e-iron-pewter.avif', 'Ibanez SR300E - widok z ogólny z przodu', 2),
(80, 12, 'Ibanez/ibanez-sr300e-iron-pewter (1).avif', 'Ibanez SR300E - widok ogólny z tyłu', 3),
(81, 12, 'Ibanez/ibanez-sr300e-iron-pewter (3).avif', 'Ibanez SR300E - widok gryfu z przodu', 4),
(82, 12, 'Ibanez/ibanez-sr300e-iron-pewter (4).avif', 'Ibanez SR300E - widok gryfu z tyłu', 5),
(83, 19, 'Fender/0113902705_fen_ins_bck_1_rl.png', 'Fender American Professional II - widok z tyłu', 3),
(84, 19, 'Fender/0113902705_fen_ins_cbr_1_nr.png', 'Fender American Professional II - widok całości', 2),
(85, 19, 'Fender/0113902705_fen_ins_fbd_1_nr.png', 'Fender American Professional II - widok z przodu', 1),
(86, 19, 'Fender/0113902705_fen_ins_hbk_1_nr.png', 'Fender American Professional II - widok gryfu od tyłu', 5),
(87, 19, 'Fender/0113902705_fen_ins_hft_1_nr.png', 'Fender American Professional II - widok gryfu z przodu', 4),
(88, 6, 'Korg/korg-kronos-3-61.avif', 'Korg Kronos - widok z góry', 1),
(89, 6, 'Korg/korg-kronos-3-61 (1).avif', 'Korg Kronos - widok z przodu', 2),
(90, 6, 'Korg/korg-kronos-3-61 (2).avif', 'Korg Kronos - widok z boku', 3),
(91, 6, 'Korg/korg-kronos-3-61 (3).avif', 'Korg Kronos - widok z tyłu', 4),
(92, 17, 'Pearl/pol_pl_Pearl-Masters-Maple-Complete-MCT904XEPC414-6244_1.webp', 'Pearl Masters - widok ogólny', 1),
(93, 17, 'Pearl/pol_pl_Pearl-Masters-Maple-Complete-MCT904XEPC414-6244_2.webp', 'Pearl Masters - widok przybliżony bębnów', 2),
(94, 17, 'Pearl/pol_pl_Pearl-Masters-Maple-Complete-MCT904XEPC414-6244_3.webp', 'Pearl Masters - widok przybliżony bębna', 3),
(95, 15, 'Roland/jd-xa_gal.jpg', 'Roland JD-XA - widok z góry', 1),
(96, 15, 'Roland/jd-xa_dr_gal.jpg', 'Roland JD-XA - widok z boku', 2),
(97, 1, 'Yamaha/B4851F1DBB7B4AC2980E5C7F8AE91628_12073_97cc329d2145a06c31d1bd7195b15b53.jpg', 'Yamaha Pacifica 112V - widok ogólny', 1);

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
(8, 'Syntezatory');

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
(5, 5),
(10, 12),
(11, 13);

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
(5, 5),
(8, 10),
(25, 11);

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
  `typ` enum('buy','rent') NOT NULL DEFAULT 'buy',
  `okres_wypozyczenia` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `koszyk_szczegoly`
--

INSERT INTO `koszyk_szczegoly` (`id`, `koszyk_id`, `instrument_id`, `ilosc`, `cena`, `typ`, `okres_wypozyczenia`) VALUES
(4, 4, 4, 1, 1899.99, 'buy', '2025-05-31'),
(30, 3, 4, 26, 1899.99, 'buy', '2025-05-24'),
(31, 25, 2, 1, 2499.99, 'buy', '2025-05-26');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 'Casio'),
(2, 'Fender'),
(3, 'Gibson'),
(9, 'Gretsch'),
(4, 'Ibanez'),
(6, 'Korg'),
(8, 'Pearl'),
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
(1, 'Janek', 'jan.kowalski@example.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-08 14:33:12', 'pracownik'),
(2, 'Anna', 'anna.nowak@example.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-08 14:33:12', 'pracownik'),
(3, 'Piotr', 'piotr.zielinski@example.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-08 14:33:12', 'pracownik'),
(4, 'Maria', 'maria.wisniewska@gmail.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-08 14:33:12', 'pracownik'),
(5, 'Adam', 'adam.kaczmarek@example.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-08 14:33:12', 'pracownik'),
(12, 'Herbert', 'herbert@example.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-20 01:44:54', 'klient'),
(13, 'Grzegorz', 'g.braun@gmail.com', '$2y$10$41nV0mCXv7ywXA6yVm8nwOXSFyfciUW7bLXaFAs89gXxP4gxjDEhm', '2025-05-26 23:34:35', 'klient');

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
  `status` enum('nowa','w_trakcie','zakonczona') NOT NULL DEFAULT 'nowa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wiadomosci`
--

INSERT INTO `wiadomosci` (`id`, `email`, `temat`, `tresc`, `data_wyslania`, `status`) VALUES
(1, 'g.braun@wp.pl', 'Odzyskajmy razem niepodległość', '!', '2025-05-24 19:42:24', 'nowa'),
(2, 'piotr.zielinski@example.com', 'tralalelo tralaleli', 'skibidi toilet', '2025-05-24 21:02:52', 'nowa'),
(4, 'piotr.zielinski@example.com', 'asda', 'asdasa', '2025-05-24 21:06:06', 'nowa'),
(5, 'piotr.zielinski@example.com', 'asdsaasdad', 'asdadsadaadas', '2025-05-24 21:07:31', 'nowa'),
(6, 'anna.nowak@example.com', 'PYTANIE', 'WITAM CZY MOGĘ OTRZYMAĆ PRODUKT #14 ZA DARMO ???', '2025-05-25 15:57:03', 'w_trakcie'),
(7, 'anna.nowak@example.com', 'test', 'testowa wiadomość', '2025-05-25 16:41:22', 'zakonczona');

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
  `kod_promocyjny_id` int(11) DEFAULT NULL,
  `adres_wysylki` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zamowienia`
--

INSERT INTO `zamowienia` (`id`, `klient_id`, `data_zamowienia`, `status`, `kod_promocyjny_id`, `adres_wysylki`) VALUES
(1, 1, '2025-05-08 14:33:13', 'w przygotowaniu', NULL, 'Grochowa 15'),
(2, 2, '2025-05-08 14:33:13', 'wysłane', NULL, 'Grochowa 15'),
(3, 3, '2025-05-08 14:33:13', 'anulowane', NULL, 'Grochowa 15'),
(4, 4, '2025-05-08 14:33:13', 'anulowane', NULL, 'Grochowa 15'),
(5, 5, '2025-05-08 14:33:13', 'wysłane', NULL, 'Grochowa 15'),
(7, 5, '2025-05-20 01:30:08', 'w przygotowaniu', NULL, 'Grochowa 15'),
(8, 2, '2025-05-21 22:08:23', 'w przygotowaniu', NULL, 'Solskiego 6/19'),
(9, 2, '2025-05-21 23:09:35', 'w przygotowaniu', NULL, 'Kocjana 10/12'),
(10, 2, '2025-05-21 23:15:24', 'w przygotowaniu', 1, 'Niepodległości 2/15'),
(11, 2, '2025-05-22 18:14:29', 'w przygotowaniu', NULL, 'Chesty Big o\' Field 889'),
(12, 2, '2025-05-22 18:17:19', 'w przygotowaniu', NULL, 'Chesty Big o\' Field 889'),
(14, 2, '2025-05-22 20:52:03', 'w przygotowaniu', NULL, 'Ananasowa 6/19'),
(15, 3, '2025-05-22 22:33:16', 'w przygotowaniu', NULL, 'Grochowa 32'),
(16, 3, '2025-05-22 22:40:15', 'w przygotowaniu', NULL, 'Lodowa 56/1'),
(17, 3, '2025-05-22 23:14:52', 'w przygotowaniu', NULL, 'Wielopole Skrzyńskie'),
(18, 5, '2025-05-23 21:00:51', 'w przygotowaniu', 1, 'Chesty Big o\' Field'),
(19, 3, '2025-05-24 20:53:17', 'w przygotowaniu', NULL, 'Niepodległości 51/123');

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
(5, 5, 5, 1, 799.99),
(7, 7, 5, 1, 799.99),
(8, 8, 2, 3, 2499.99),
(9, 8, 4, 1, 1899.99),
(10, 8, 5, 1, 799.99),
(11, 8, 14, 3, 3999.99),
(12, 8, 5, 1, 799.99),
(13, 9, 2, 3, 2499.99),
(14, 9, 4, 1, 1899.99),
(15, 9, 5, 1, 799.99),
(16, 9, 14, 3, 3999.99),
(17, 9, 5, 1, 799.99),
(18, 10, 4, 1, 1899.99),
(19, 11, 5, 1, 799.99),
(20, 12, 2, 599, 2499.99),
(21, 14, 4, 1, 1899.99),
(22, 15, 1, 8, 1499.99),
(23, 16, 1, 31, 1499.99),
(24, 17, 1, 1, 1499.99),
(25, 18, 4, 1, 1899.99),
(26, 19, 4, 1, 1899.99),
(27, 19, 4, 1, 1899.99);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `dostawy`
--
ALTER TABLE `dostawy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `instrumenty`
--
ALTER TABLE `instrumenty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `instrument_oceny`
--
ALTER TABLE `instrument_oceny`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `instrument_zdjecia`
--
ALTER TABLE `instrument_zdjecia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `kategorie_instrumentow`
--
ALTER TABLE `kategorie_instrumentow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `klienci`
--
ALTER TABLE `klienci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kody_promocyjne`
--
ALTER TABLE `kody_promocyjne`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `koszyk`
--
ALTER TABLE `koszyk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `koszyk_szczegoly`
--
ALTER TABLE `koszyk_szczegoly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `pracownicy`
--
ALTER TABLE `pracownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `producenci`
--
ALTER TABLE `producenci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stanowiska`
--
ALTER TABLE `stanowiska`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wiadomosci`
--
ALTER TABLE `wiadomosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wypozyczenia`
--
ALTER TABLE `wypozyczenia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `zamowienia`
--
ALTER TABLE `zamowienia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `zamowienie_szczegoly`
--
ALTER TABLE `zamowienie_szczegoly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
  ADD CONSTRAINT `instrument_oceny_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_koszyk_klient` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `pracownicy_ibfk_2` FOREIGN KEY (`stanowisko_id`) REFERENCES `stanowiska` (`id`);

--
-- Constraints for table `wypozyczenia`
--
ALTER TABLE `wypozyczenia`
  ADD CONSTRAINT `fk_wypozyczenia_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wypozyczenia_klient` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zamowienia`
--
ALTER TABLE `zamowienia`
  ADD CONSTRAINT `fk_zamowienia_klient` FOREIGN KEY (`klient_id`) REFERENCES `klienci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zamowienia_ibfk_2` FOREIGN KEY (`kod_promocyjny_id`) REFERENCES `kody_promocyjne` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `zamowienie_szczegoly`
--
ALTER TABLE `zamowienie_szczegoly`
  ADD CONSTRAINT `fk_zamowienie_szczegoly_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zamowienie_szczegoly_ibfk_1` FOREIGN KEY (`zamowienie_id`) REFERENCES `zamowienia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zamowienie_szczegoly_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instrumenty` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
