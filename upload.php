<?php
// ============================================================
//  upload.php - odbior pliku przeslanego przez uzytkownika
//  Odbiera dane z formularza (select.php) i zapisuje plik
//  w katalogu macierzystym uzytkownika lub jego podkatalogu.
// ============================================================

session_start();

// Przesylanie plikow tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$username = (string)($_SESSION['username'] ?? '');

// Katalog macierzysty uzytkownika
$home     = ensure_user_home_dir($username);
$homeReal = realpath($home);

// Podkatalog docelowy (wzgledem katalogu macierzystego)
$sub = isset($_POST['sub']) ? (string)$_POST['sub'] : '';
$sub = str_replace('\\', '/', $sub);
$sub = trim($sub, '/');

// Walidacja nazwy podkatalogu
if ($sub !== '') {
    $czesc = explode('/', $sub);
    $ok = true;
    foreach ($czesc as $c) {
        if ($c === '' || $c === '.' || $c === '..' || strpos($c, "\0") !== false) {
            $ok = false;
            break;
        }
    }
    if (!$ok) {
        $sub = '';
    }
}

$targetDir     = $home . ($sub !== '' ? DIRECTORY_SEPARATOR . $sub : '');
$targetDirReal = realpath($targetDir);

// Zabezpieczenie przed zapisem poza katalogiem macierzystym
if ($targetDirReal === false || $homeReal === false || strpos($targetDirReal, $homeReal) !== 0) {
    $targetDir = $home;
    $sub       = '';
}

// Odczyt przeslanego pliku
if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
    // basename() usuwa sciezke z nazwy pliku - ochrona przed podaniem pelnej sciezki
    $nazwa      = basename($_FILES['fileToUpload']['name']);
    $targetFile = $targetDir . DIRECTORY_SEPARATOR . $nazwa;

    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $targetFile)) {
        header('Location: mycloud.php?sub=' . urlencode($sub) . '&ok=1');
        exit();
    }

    header('Location: select.php?sub=' . urlencode($sub) . '&blad=1');
    exit();
}

header('Location: select.php?sub=' . urlencode($sub));
exit();
