<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
// ============================================================
//  dodaj2.php - interpretuje dane formularza dodaj1.php
//  i dodaje hosta oraz port do tabeli domeny (INSERT INTO)
// ============================================================
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$host = isset($_POST['host']) ? trim($_POST['host']) : '';
$port = isset($_POST['port']) ? (int)$_POST['port'] : 0;

// Prosta walidacja danych
if ($host === '' || $port < 1 || $port > 65535) {
    header('Location: dodaj1.php');
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

$link = db_connect();
db_ensure_users_table($link);
db_ensure_domeny_table($link);

// Dodanie nowego hosta/portu do tabeli domeny (kolumna user_id wiaze rekord z uzytkownikiem)
$stmt = mysqli_prepare($link, "INSERT INTO domeny (host, port, user_id, errors, total_errors) VALUES (?, ?, ?, 0, 0)");
mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

// Powrot do widoku monitorowania hostow
header('Location: index1.php');
exit();
