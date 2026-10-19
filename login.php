<?php
// ============================================================
//  login.php - weryfikacja loginu i hasla oraz rozpoczecie sesji
//  Odbiera dane z formularza (index.php)
//  Dodatkowo (zadanie 5) rejestruje w tabeli goscieportalu
//  date i godzine pomyslnych oraz blednych prob logowania.
// ============================================================

session_start();

require_once 'config.php';

$user = isset($_POST['user']) ? trim($_POST['user']) : '';
$pass = isset($_POST['pass']) ? $_POST['pass'] : '';

$link = db_connect();

// Upewnij sie, ze tabele posiadaja wymagana strukture
db_ensure_users_table($link);
db_ensure_goscieportalu_table($link);

// Zapewnienie istnienia konta administratora (admin/admin)
db_ensure_admin_user($link);

// Wyszukanie uzytkownika o podanym loginie (zapytanie przygotowane - ochrona przed SQL injection)
$stmt = mysqli_prepare($link, "SELECT id, username, password, avatar FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, 's', $user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rekord = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

$ip       = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$datetime = date('Y-m-d H:i:s');

// Sprawdzenie, czy uzytkownik istnieje i czy haslo sie zgadza
if ($rekord && $rekord['password'] === $pass) {
    // Poprawne logowanie - zapisanie zmiennych sesyjnych
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $rekord['username'];
    $_SESSION['user_id'] = $rekord['id'];
    $_SESSION['avatar'] = (!empty($rekord['avatar']) ? $rekord['avatar'] : 'media/avatars/default.svg');

    // Sciezka do katalogu macierzystego uzytkownika (potrzebna do operacji dyskowych)
    $_SESSION['home_dir'] = ensure_user_home_dir($rekord['username']);

    // Rejestracja pomyslnego logowania
    $stmt = mysqli_prepare($link, "INSERT INTO goscieportalu (ipaddress, `datetime`, username, `status`) VALUES (?, ?, ?, 'success')");
    mysqli_stmt_bind_param($stmt, 'sss', $ip, $datetime, $rekord['username']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_close($link);

    // Po zalogowaniu - automatycznie wyswietl liste plikow dysku sieciowego
    header('Location: mycloud.php');
    exit();
} else {
    // Niepoprawny login lub haslo - rejestracja blednej proby logowania
    $stmt = mysqli_prepare($link, "INSERT INTO goscieportalu (ipaddress, `datetime`, username, `status`) VALUES (?, ?, ?, 'failed')");
    mysqli_stmt_bind_param($stmt, 'sss', $ip, $datetime, $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_close($link);

    // Powrot do formularza logowania z komunikatem o bledzie
    header('Location: index.php?blad=1');
    exit();
}
