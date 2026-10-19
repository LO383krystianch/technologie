<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
// ============================================================
//  kasuj2.php - interpretuje dane formularza kasuj1.php
//  i usuwa podany host/port z tabeli domeny (DELETE ... WHERE)
// ============================================================
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$host = isset($_POST['host']) ? trim($_POST['host']) : '';
$port = isset($_POST['port']) ? (int)$_POST['port'] : 0;

if ($host === '' || $port < 1 || $port > 65535) {
    header('Location: kasuj1.php');
    exit();
}

$user_id  = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? '');
$is_admin = ($username === 'admin');

$link = db_connect();
db_ensure_users_table($link);
db_ensure_domeny_table($link);

// Usuwanie hosta/portu - zwykly uzytkownik moze usunac tylko ze swojej puli,
// administrator ma mozliwosc usuniecia dowolnego rekordu.
if ($is_admin) {
    $stmt = mysqli_prepare($link, "DELETE FROM domeny WHERE host = ? AND port = ?");
    mysqli_stmt_bind_param($stmt, 'si', $host, $port);
} else {
    $stmt = mysqli_prepare($link, "DELETE FROM domeny WHERE host = ? AND port = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $user_id);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

header('Location: index1.php');
exit();
