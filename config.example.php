<?php
// ============================================================
//  Konfiguracja polaczenia z baza danych MySQL
//  (dane z pliku "dostep do mysql")
// ============================================================

// UWAGA: Ten plik jest SZABLONEM. Skopiuj go do pliku "config.php"
// i uzupełnij poniżej prawdziwe dane dostępowe do bazy danych MySQL.
// Plik "config.php" (z prawdziwymi danymi) jest ignorowany przez Git (.gitignore).
define('DB_HOST',     'localhost');
define('DB_USER',     'TU_WPISZ_UZYTKOWNIKA');
define('DB_PASSWORD', 'TU_WPISZ_HASLO');
define('DB_NAME',     'TU_WPISZ_NAZWE_BAZY');

// Funkcja zwracajaca polaczenie z baza danych
function db_connect()
{
    $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if (!$link) {
        die('Blad polaczenia z baza danych: ' . mysqli_connect_error());
    }

    mysqli_set_charset($link, 'utf8'); // poprawne wyswietlanie polskich znakow

    return $link;
}

// Funkcja zapewniajaca poprawna strukture tabeli users
// (tworzy tabele jesli nie istnieje oraz dodaje kolumne avatar, jesli jej brak)
function db_ensure_users_table($link)
{
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

    $res = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'avatar'");
    if (mysqli_num_rows($res) === 0) {
        mysqli_query($link, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    }

    // Kolumna worker (typu boolowskiego) - informuje, czy uzytkownik jest pracownikiem
    // odpowiedzialnym za monitorowanie hostow (wykorzystywana w formularzu staff.php)
    $res = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'worker'");
    if (mysqli_num_rows($res) === 0) {
        mysqli_query($link, "ALTER TABLE users ADD COLUMN worker TINYINT(1) DEFAULT 0");
    }
}

// Funkcja zapewniajaca poprawna strukture tabeli goscieportalu
// (rejestracja wizyt gosci portalu - geolokalizacja)
// Dodatkowo tabela przechowuje informacje o probach logowania (zadanie 5):
//   username - login, na ktory probowano sie zalogowac
//   status   - wynik proby logowania ('success' lub 'failed')
function db_ensure_goscieportalu_table($link)
{
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS goscieportalu (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ipaddress VARCHAR(45) NOT NULL,
        `datetime` DATETIME NOT NULL,
        browser VARCHAR(255) DEFAULT NULL,
        screen_res VARCHAR(50) DEFAULT NULL,
        window_res VARCHAR(50) DEFAULT NULL,
        color_depth VARCHAR(20) DEFAULT NULL,
        cookies VARCHAR(20) DEFAULT NULL,
        java VARCHAR(20) DEFAULT NULL,
        jezyk VARCHAR(50) DEFAULT NULL,
        username VARCHAR(50) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

    $res = mysqli_query($link, "SHOW COLUMNS FROM goscieportalu LIKE 'username'");
    if (mysqli_num_rows($res) === 0) {
        mysqli_query($link, "ALTER TABLE goscieportalu ADD COLUMN username VARCHAR(50) DEFAULT NULL");
    }

    $res = mysqli_query($link, "SHOW COLUMNS FROM goscieportalu LIKE 'status'");
    if (mysqli_num_rows($res) === 0) {
        mysqli_query($link, "ALTER TABLE goscieportalu ADD COLUMN `status` VARCHAR(20) DEFAULT NULL");
    }
}

// Funkcja zapewniajaca poprawna strukture tabeli domeny
// (monitorowanie hostow i portow - zadanie 4)
// Kolumny:
//   id           - klucz glowny (autonumerowanie)
//   host         - nazwa domenowa / numer IP monitorowanego hosta
//   port         - numer monitorowanego portu
//   user_id      - identyfikator wlasciciela hosta (relacja z tabela users)
//   errors       - ilosc kolejnych nieskutecznych prob polaczenia
//   failure      - data i godzina pierwszego braku polaczenia (w serii)
//   total_errors - laczna ilosc nieudanych prob polaczenia od poczatku
function db_ensure_domeny_table($link)
{
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS domeny (
        id INT AUTO_INCREMENT PRIMARY KEY,
        host TEXT NOT NULL,
        port INT NOT NULL,
        user_id INT DEFAULT NULL,
        errors INT DEFAULT 0,
        failure DATETIME DEFAULT NULL,
        total_errors INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

// Funkcja zapewniajaca istnienie konta administratora (admin/admin)
function db_ensure_admin_user($link)
{
    db_ensure_users_table($link);

    $res = mysqli_query($link, "SELECT id FROM users WHERE username = 'admin'");
    if (mysqli_num_rows($res) === 0) {
        $stmt = mysqli_prepare($link, "INSERT INTO users (username, password, avatar) VALUES ('admin', 'admin', 'media/avatars/default.svg')");
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// ============================================================
//  Funkcje zwiazane z dyskiem sieciowym MyCloud (zadanie 5)
// ============================================================

// Katalog bazowy, w ktorym przechowywane sa katalogi macierzyste uzytkownikow
define('USERS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'users');

// Zwraca sciezke do katalogu macierzystego uzytkownika (nazwa = login)
function user_home_dir(string $username): string
{
    return USERS_DIR . DIRECTORY_SEPARATOR . $username;
}

// Tworzy katalog macierzysty uzytkownika, jesli jeszcze nie istnieje
function ensure_user_home_dir(string $username): string
{
    $dir = user_home_dir($username);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

// Zwraca typ MIME na podstawie rozszerzenia nazwy pliku
// (uzywane do poprawnego pobierania plikow z dysku)
function mime_z_rozszerzenia(string $nazwa): string
{
    $ext = strtolower(pathinfo($nazwa, PATHINFO_EXTENSION));
    $map = [
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif'  => 'image/gif',  'svg'  => 'image/svg+xml', 'webp' => 'image/webp',
        'bmp'  => 'image/bmp',
        'pdf'  => 'application/pdf',
        'mp3'  => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
        'mp4'  => 'video/mp4',  'webm' => 'video/webm', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo',
        'txt'  => 'text/plain', 'csv' => 'text/csv',
        'zip'  => 'application/zip', 'rar' => 'application/x-rar-compressed',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'json' => 'application/json', 'xml' => 'application/xml',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}
