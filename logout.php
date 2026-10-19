<?php
// ============================================================
//  logout.php - wylogowanie uzytkownika
//  Usuwa sesje i wraca do formularza logowania
// ============================================================

session_start();

// Wyczyszczenie zmiennych sesyjnych i usuniecie sesji
session_unset();
session_destroy();

header('Location: index.php');
exit();
