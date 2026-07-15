<?php
function createPdoConnection($host, $dbname, $username, $password) {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

// Production database configuration (Hostinger)
$productionHost = 'localhost';
$productionDbname = 'u926715344_kphub';
$productionUsername = 'u926715344_kphub';
$productionPassword = 'RictuR82025$';

// Local development fallback
$localHost = 'localhost';
$localDbname = 'kphub_dev';
$localUsername = 'root';
$localPassword = '';

try {
    $pdo = createPdoConnection($productionHost, $productionDbname, $productionUsername, $productionPassword);
} catch (PDOException $productionError) {
    try {
        $pdo = createPdoConnection($localHost, $localDbname, $localUsername, $localPassword);
        error_log('Using local database fallback: ' . $localDbname);
    } catch (PDOException $localError) {
        error_log('Database connection failed: ' . $productionError->getMessage());
        error_log('Local fallback connection failed: ' . $localError->getMessage());
        die('Database connection failed. Please try again later.');
    }
}
?>
