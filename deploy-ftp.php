<?php
// Egyszerű FTP feltöltő szkript módosított fájlokhoz
// .env vagy környezeti változók: FTP_HOST, FTP_USER, FTP_PASS, FTP_TARGET

$ftp_host = getenv('FTP_HOST');
$ftp_user = getenv('FTP_USER');
$ftp_pass = getenv('FTP_PASS');
$ftp_target = getenv('FTP_TARGET');

if (!$ftp_host || !$ftp_user || !$ftp_pass || !$ftp_target) {
    if (function_exists('wp_die')) {
        wp_die("Hiányzó FTP beállítások!");
    }
    // If not running under WP, stop execution (CLI path)
    if (php_sapi_name() === 'cli') exit("Hiányzó FTP beállítások!\n");
}

// Módosított fájlok listázása git alapján
exec('git status --porcelain', $output);
$changed_files = [];
foreach ($output as $line) {
    $file = trim(substr($line, 3));
    if ($file && file_exists($file)) {
        $changed_files[] = $file;
    }
}

if (empty($changed_files)) {
    if (function_exists('wp_die')) {
        wp_die("Nincs módosított fájl a feltöltéshez.");
    }
    if (php_sapi_name() === 'cli') exit("Nincs módosított fájl a feltöltéshez.\n");
}

$conn = ftp_connect($ftp_host);
if (!$conn) {
    if (function_exists('wp_die')) {
        wp_die('Nem sikerült csatlakozni az FTP szerverhez!');
    }
    if (php_sapi_name() === 'cli') exit("Nem sikerült csatlakozni az FTP szerverhez!\n");
}
if (!ftp_login($conn, $ftp_user, $ftp_pass)) {
    if (function_exists('wp_die')) {
        wp_die('FTP belépés sikertelen!');
    }
    if (php_sapi_name() === 'cli') exit("FTP belépés sikertelen!\n");
}
ftp_pasv($conn, true);

foreach ($changed_files as $local_file) {
    $remote_file = $ftp_target . '/' . $local_file;
    $remote_dir = dirname($remote_file);
    // Könyvtár létrehozása, ha kell
    $parts = explode('/', $remote_dir);
    $path = '';
    foreach ($parts as $part) {
        if (!$part) continue;
        $path .= '/' . $part;
        @ftp_mkdir($conn, $path);
    }
    if (ftp_put($conn, $remote_file, $local_file, FTP_BINARY)) {
        echo "Feltöltve: $local_file -> $remote_file\n";
    } else {
        echo "Hiba: $local_file\n";
    }
}
ftp_close($conn);
echo "Kész!\n";
