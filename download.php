<?php
// ============================================================
//  download.php - pobieranie pliku z dysku sieciowego
//  Plik przekazywany jest parametrem GET "file" (sciezka wzgledem
//  katalogu macierzystego uzytkownika). Skrypt wysyla plik do
//  przegladarki z odpowiednim typem MIME.
// ============================================================

session_start();

// Pobieranie plikow tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$username = (string)($_SESSION['username'] ?? '');

$home     = ensure_user_home_dir($username);
$homeReal = realpath($home);

// Sciezka pliku wzgledem katalogu macierzystego
$file = isset($_GET['file']) ? (string)$_GET['file'] : '';
$file = str_replace('\\', '/', $file);
$file = trim($file, '/');

// Walidacja sciezki - odrzucenie znakow niebezpiecznych
$ok = true;
if ($file === '') {
    $ok = false;
} else {
    foreach (explode('/', $file) as $c) {
        if ($c === '' || $c === '.' || $c === '..' || strpos($c, "\0") !== false) {
            $ok = false;
            break;
        }
    }
}

$target     = $home . DIRECTORY_SEPARATOR . $file;
$targetReal = realpath($target);

// Zabezpieczenie przed pobraniem pliku spoza katalogu macierzystego
if (!$ok || $targetReal === false || $homeReal === false
    || strpos($targetReal, $homeReal) !== 0 || !is_file($targetReal)) {
    http_response_code(404);
    echo 'Plik nie istnieje.';
    exit();
}

$nazwa = basename($targetReal);
$mime  = mime_z_rozszerzenia($nazwa);

// Rozszerzenia wyswietlane bezposrednio w przegladarce (inline),
// pozostale typy plikow sa pobierane na dysk (attachment).
$inline = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'pdf',
           'mp3', 'wav', 'ogg', 'm4a', 'mp4', 'webm', 'mov'];

$ext = strtolower(pathinfo($nazwa, PATHINFO_EXTENSION));

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($targetReal));
header('X-Content-Type-Options: nosniff');

if (in_array($ext, $inline, true)) {
    header('Content-Disposition: inline; filename="' . $nazwa . '"');
} else {
    // Bezpieczna nazwa pliku do pobrania (obsługa polskich znaków)
    $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nazwa);
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nazwa));
}

readfile($targetReal);
exit();
