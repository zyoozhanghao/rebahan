<?php
// rebahan/website/public/config.php
// Konfigurasi Koneksi Database PDO untuk ReBahan

$host = getenv('DB_HOST') ?: 'mariadb';
$db   = getenv('DB_DATABASE') ?: 'rebahan';
$user = getenv('DB_USERNAME') ?: 'Zyoo';
$pass = getenv('DB_PASSWORD') ?: 'zyooganteng';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Upaya koneksi langsung ke database target
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jika database belum dibuat (Error 1049: Unknown database), buat otomatis
    if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
        try {
            $rootDsn = "mysql:host={$host};port={$port};charset={$charset}";
            $tempPdo = new PDO($rootDsn, $user, $pass, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e2) {
            die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;'>
                <strong>Gagal Membuat Database:</strong> " . htmlspecialchars($e2->getMessage()) . "</div>");
        }
    } else {
        die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;'>
            <strong>Koneksi Database Gagal:</strong> " . htmlspecialchars($e->getMessage()) . "
            <br><small>Pastikan container mariadb berjalan dan kredensial di config.php sesuai.</small></div>");
    }
}

// Auto-migration & seeding jika tabel belum diinisialisasi
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'materials'")->fetch();
    if (!$checkTable) {
        if (file_exists(__DIR__ . '/setup.php')) {
            require_once __DIR__ . '/setup.php';
            if (function_exists('runDatabaseMigrationAndSeeding')) {
                runDatabaseMigrationAndSeeding($pdo);
            }
        }
    }
} catch (\Throwable $t) {
    // Abaikan atau log error jika tabel sudah terdaftar
    error_log("ReBahan auto-migration check: " . $t->getMessage());
}

