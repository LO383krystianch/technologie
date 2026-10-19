<?php
// ============================================================
//  register.php - rejestracja nowego uzytkownika
//  Odbiera dane z formularza (rejestracja.php)
//  Opcjonalnie zapisuje avatar uzytkownika (plik graficzny)
// ============================================================

require_once 'config.php';

$user  = isset($_POST['user'])  ? trim($_POST['user']) : '';
$pass  = isset($_POST['pass'])  ? $_POST['pass'] : '';
$pass2 = isset($_POST['pass2']) ? $_POST['pass2'] : '';

$blad = '';

// Walidacja danych wejsciowych
if ($user === '' || $pass === '' || $pass2 === '') {
    $blad = '1'; // puste pola
} elseif (mb_strlen($user) < 3 || mb_strlen($user) > 50) {
    $blad = '2'; // zla dlugosc loginu
} elseif (!preg_match('/^[A-Za-z0-9_]+$/', $user)) {
    $blad = '7'; // login zawiera niedozwolone znaki (dozwolone: litery, cyfry, podkreslenie)
} elseif (mb_strlen($pass) < 4) {
    $blad = '3'; // za krotkie haslo
} elseif ($pass !== $pass2) {
    $blad = '4'; // hasla sie roznia
}

if ($blad !== '') {
    header('Location: rejestracja.php?blad=' . $blad);
    exit();
}

$link = db_connect();

// Upewnij sie, ze tabela users istnieje oraz posiada kolumne avatar
db_ensure_users_table($link);

// Sprawdzenie, czy login nie jest juz zajety
$stmt = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, 's', $user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    header('Location: rejestracja.php?blad=5'); // login zajety
    exit();
}
mysqli_stmt_close($stmt);

// Obsluga avatara (opcjonalna) - domyslnie domyslna ikona uzytkownika
$avatar = 'media/avatars/default.svg';

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $tmp     = $_FILES['avatar']['tmp_name'];
    $nazwa   = $_FILES['avatar']['name'];
    $rozmiar = $_FILES['avatar']['size'];

    $info = @getimagesize($tmp);
    $dozwolone = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];

    if ($info !== false && isset($dozwolone[$info['mime']]) && $rozmiar <= 2 * 1024 * 1024) {
        $katalog = __DIR__ . '/media/avatars/';
        if (!is_dir($katalog)) {
            mkdir($katalog, 0777, true);
        }
        $rozszerzenie = $dozwolone[$info['mime']];
        $nowaNazwa = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $rozszerzenie;
        if (move_uploaded_file($tmp, $katalog . $nowaNazwa)) {
            $avatar = 'media/avatars/' . $nowaNazwa;
        }
    }
}

// Dodanie uzytkownika do bazy (haslo zapisywane tak, jak w skryptach z poprzedniego zadania)
$stmt = mysqli_prepare($link, "INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $user, $pass, $avatar);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

if ($ok) {
    // Utworzenie katalogu macierzystego uzytkownika (nazwa = login)
    ensure_user_home_dir($user);

    header('Location: index.php?rej=1'); // sukces - przejdz do logowania
    exit();
} else {
    header('Location: rejestracja.php?blad=6'); // blad zapisu do bazy
    exit();
}
