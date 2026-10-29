<?php
// rebahan/website/public/setup.php
// Skrip Inisialisasi Database dan Data Dummy UMKM Jakarta untuk ReBahan

function runDatabaseMigrationAndSeeding(PDO $pdo): array {
    $logs = [];

    // 1. Buat Tabel `users`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama_usaha` VARCHAR(255) NOT NULL,
            `sektor` VARCHAR(100) NOT NULL,
            `wilayah` ENUM('Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara') NOT NULL,
            `kontak` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $logs[] = "Tabel 'users' siap.";

    // 2. Buat Tabel `materials`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `materials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `nama_bahan` VARCHAR(255) NOT NULL,
            `kategori` ENUM('Kain/Tekstil', 'Kertas/Karton', 'Plastik', 'Kayu/Bambu') NOT NULL,
            `jumlah` DECIMAL(10,2) NOT NULL,
            `satuan` VARCHAR(50) NOT NULL,
            `kondisi` VARCHAR(150) NOT NULL,
            `wilayah` ENUM('Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara') NOT NULL,
            `deskripsi` TEXT NULL,
            `status` ENUM('available', 'booked', 'completed') DEFAULT 'available',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_materials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $logs[] = "Tabel 'materials' siap.";

    // 3. Buat Tabel `transactions`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `transactions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `material_id` INT NOT NULL,
            `receiver_id` INT NOT NULL,
            `catatan` TEXT NULL,
            `status` ENUM('Pending', 'Disetujui', 'Dalam Pengambilan', 'Selesai') DEFAULT 'Pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_transactions_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_transactions_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $logs[] = "Tabel 'transactions' siap.";

    // 4. Seeding Data Dummy Pengguna (UMKM Jakarta)
    $stmtUserCount = $pdo->query("SELECT COUNT(*) FROM `users`");
    $userCount = (int) $stmtUserCount->fetchColumn();

    if ($userCount === 0) {
        $usersData = [
            ['Penjahit Berkah Busana', 'Tekstil', 'Jakarta Selatan', '0812-3456-7890'],
            ['Percetakan Salemba Grafika', 'Percetakan', 'Jakarta Pusat', '0813-8899-1122'],
            ['Workshop Kayu Lestari Palet', 'Kerajinan', 'Jakarta Selatan', '0815-4455-6677'],
            ['Kreasi Tas Daur Ulang Johar', 'Kerajinan', 'Jakarta Pusat', '0818-9900-2233'],
            ['Studio Sablon & Konveksi Palmerah', 'Tekstil', 'Jakarta Barat', '0819-1234-5678'],
            ['Mitra Penggilingan Plastik Cengkareng', 'Manufaktur', 'Jakarta Barat', '0812-7788-9900'],
            ['Gudang Logistik & Ekspedisi Cakung', 'Logistik', 'Jakarta Timur', '0813-5566-7788'],
            ['Pengrajin Mebel Bambu Kelapa Gading', 'Kerajinan', 'Jakarta Utara', '0817-2233-4455'],
            ['Studio Kriya Kain Perca Tebet', 'Kerajinan', 'Jakarta Selatan', '0812-9988-7766'],
        ];

        $insertUser = $pdo->prepare("
            INSERT INTO `users` (`nama_usaha`, `sektor`, `wilayah`, `kontak`) 
            VALUES (?, ?, ?, ?)
        ");
        foreach ($usersData as $u) {
            $insertUser->execute($u);
        }
        $logs[] = "Seeding data UMKM berhasil (" . count($usersData) . " UMKM ditambahkan).";
    }

    // Ambil ID UMKM untuk referensi bahan & transaksi
    $usersMap = [];
    $allUsers = $pdo->query("SELECT `id`, `nama_usaha` FROM `users`")->fetchAll();
    foreach ($allUsers as $u) {
        $usersMap[$u['nama_usaha']] = (int) $u['id'];
    }

    // 5. Seeding Data Dummy Material Sisa
    $stmtMatCount = $pdo->query("SELECT COUNT(*) FROM `materials`");
    $matCount = (int) $stmtMatCount->fetchColumn();

    if ($matCount === 0 && !empty($usersMap)) {
        $materialsData = [
            [
                $usersMap['Penjahit Berkah Busana'] ?? 1,
                'Sisa Kain Perca Katun Rayon Corak & Polos',
                'Kain/Tekstil',
                35.50,
                'kg',
                'Bersih, Kering, Potongan baru sisa jahitan dress',
                'Jakarta Selatan',
                'Sangat cocok untuk produk kerajinan scrunchie, sarung bantal patchwork, masker kain, atau tote bag.',
                'available'
            ],
            [
                $usersMap['Percetakan Salemba Grafika'] ?? 2,
                'Potongan Kertas Art Paper 260gsm & Ivory',
                'Kertas/Karton',
                50.00,
                'kg',
                'Potongan presisi, bersih & rapi',
                'Jakarta Pusat',
                'Sisa pemotongan cetak cover buku dan kalender meja tahunan. Cocok untuk hangtag pakaian atau notebook daur ulang.',
                'available'
            ],
            [
                $usersMap['Workshop Kayu Lestari Palet'] ?? 3,
                'Sisa Kayu Palet Pinus & Jati Belanda Ex-Kargo',
                'Kayu/Bambu',
                40.00,
                'pcs',
                'Kayu utuh, kering oven, bebas paku berlebih',
                'Jakarta Selatan',
                'Papan palet kayu sisa unboxing kargo industri Mampang. Sangat bagus untuk rak hidroponik mini, ambalan dinding, atau meja cafe rustic.',
                'available'
            ],
            [
                $usersMap['Studio Sablon & Konveksi Palmerah'] ?? 5,
                'Sisa Gulungan Kain Kanvas Twill & Denim',
                'Kain/Tekstil',
                22.00,
                'meter',
                'Bersih potongan rol ujung (lebar 40-60 cm)',
                'Jakarta Barat',
                'Material kuat dan kokoh untuk aksesoris fashion, dompet koin, atau tatakan gelas ramah lingkungan.',
                'available'
            ],
            [
                $usersMap['Mitra Penggilingan Plastik Cengkareng'] ?? 6,
                'Cacahan Plastik PP & HDPE Terpilah Bersih',
                'Plastik',
                75.00,
                'kg',
                'Sudah dicuci, dipotong kecil 5mm, warna seragam',
                'Jakarta Barat',
                'Hasil olahan pemilahan wadah botol susu dan tutup galon. Siap untuk proses injeksi moulding pot tanaman atau gantungan kunci.',
                'available'
            ],
            [
                $usersMap['Gudang Logistik & Ekspedisi Cakung'] ?? 7,
                'Karton Box Gelombang (Corrugated Box) Tebal Double Wall',
                'Kertas/Karton',
                65.00,
                'kg',
                'Sudah dipipihkan, bersih dari minyak, kering',
                'Jakarta Timur',
                'Bahan kemasan sisa impor logistik pergudangan. Dapat dipakai kembali langsung untuk packaging produk UMKM lokal.',
                'available'
            ],
            [
                $usersMap['Pengrajin Mebel Bambu Kelapa Gading'] ?? 8,
                'Potongan Batang Bambu Petung & Tali Ijuk',
                'Kayu/Bambu',
                18.00,
                'pcs',
                'Panjang 80-120 cm, diawetkan anti-rayap',
                'Jakarta Utara',
                'Sisa pengerjaan gazebo dan mebel bambu pesanan taman kota. Cocok untuk kerajinan wadah aromaterapi, vas bunga, atau lampion.',
                'available'
            ],
            [
                $usersMap['Penjahit Berkah Busana'] ?? 1,
                'Sisa Kain Spunbond Non-Woven Putih & Krem',
                'Kain/Tekstil',
                15.00,
                'kg',
                'Kondisi baru lembaran bersih',
                'Jakarta Selatan',
                'Material sisa pembuatan kantong belanja ramah lingkungan pesanan komunitas. Bagus untuk lapisan dalam tas atau pouch.',
                'completed' // Material ini sengaja diselesaikan untuk kalkulasi transaksi riil
            ]
        ];

        $insertMat = $pdo->prepare("
            INSERT INTO `materials` 
            (`user_id`, `nama_bahan`, `kategori`, `jumlah`, `satuan`, `kondisi`, `wilayah`, `deskripsi`, `status`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($materialsData as $m) {
            $insertMat->execute($m);
        }
        $logs[] = "Seeding data Material Sisa berhasil (" . count($materialsData) . " material ditambahkan).";
    }

    // 6. Seeding Data Dummy Transaksi (Aktif & Selesai)
    $stmtTrxCount = $pdo->query("SELECT COUNT(*) FROM `transactions`");
    $trxCount = (int) $stmtTrxCount->fetchColumn();

    if ($trxCount === 0) {
        // Ambil beberapa ID material
        $stmtMats = $pdo->query("SELECT `id`, `user_id`, `status` FROM `materials` ORDER BY `id` ASC");
        $allMats = $stmtMats->fetchAll();

        if (count($allMats) >= 4) {
            $transData = [
                [
                    $allMats[0]['id'], // Kain perca
                    $usersMap['Kreasi Tas Daur Ulang Johar'] ?? 4,
                    'Kain perca akan dimanfaatkan untuk kerajinan pouch belanja pameran UMKM Jakarta Kreatif.',
                    'Disetujui'
                ],
                [
                    $allMats[1]['id'], // Kertas Art Paper
                    $usersMap['Studio Kriya Kain Perca Tebet'] ?? 9,
                    'Dibutuhkan untuk hangtag dan label sertifikat produk ramah lingkungan.',
                    'Pending'
                ],
                [
                    $allMats[2]['id'], // Kayu Palet
                    $usersMap['Kreasi Tas Daur Ulang Johar'] ?? 4,
                    'Untuk display booth pameran UMKM sirkular di Senayan.',
                    'Dalam Pengambilan'
                ],
                [
                    $allMats[7]['id'] ?? $allMats[3]['id'], // Spunbond / Kanvas
                    $usersMap['Studio Kriya Kain Perca Tebet'] ?? 9,
                    'Selesai diambil pada tanggal 26 Oktober 2026. Bahan telah diolah menjadi 50 kantong reusable.',
                    'Selesai'
                ]
            ];

            $insertTrx = $pdo->prepare("
                INSERT INTO `transactions` (`material_id`, `receiver_id`, `catatan`, `status`) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($transData as $t) {
                $insertTrx->execute($t);
            }
            $logs[] = "Seeding data Transaksi berhasil (" . count($transData) . " transaksi awal dibuat).";
        }
    }

    return $logs;
}

// Jika dipanggil langsung via browser atau CLI
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/config.php';
    try {
        $results = runDatabaseMigrationAndSeeding($pdo);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Setup Database ReBahan</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 min-h-screen p-6 font-sans">
            <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-emerald-100 p-8">
                <div class="flex items-center space-x-3 mb-6">
                    <span class="p-3 bg-emerald-100 text-emerald-700 rounded-xl font-bold text-xl">🌱</span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Inisialisasi Database ReBahan</h1>
                        <p class="text-slate-500 text-sm">Platform Digital Ekonomi Sirkular UMKM Jakarta</p>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <?php foreach ($results as $res): ?>
                        <div class="flex items-center space-x-2 text-emerald-700 bg-emerald-50 p-3 rounded-lg border border-emerald-200 text-sm">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span><?= htmlspecialchars($res) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex space-x-3">
                    <a href="index.php" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition shadow-sm">Buka Beranda</a>
                    <a href="katalog.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-medium transition">Lihat Katalog</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    } catch (\Throwable $e) {
        echo "<h2 style='color:red;'>Setup Gagal: " . htmlspecialchars($e->getMessage()) . "</h2>";
    }
}

