<?php
// rebahan/website/public/penawaran.php
// Form Upload/Input Material Sisa Produksi UMKM Jakarta

require_once __DIR__ . '/config.php';

$errors = [];
$formData = [
    'nama_usaha'        => '',
    'sektor'            => 'Tekstil',
    'kontak'            => '',
    'wilayah'           => 'Jakarta Selatan',
    'nama_bahan'        => '',
    'kategori'          => 'Kain/Tekstil',
    'jumlah'            => '',
    'satuan'            => 'kg',
    'harga_per_satuan'  => '60000',
    'satuan_jual'       => 'kg',
    'ukuran_pecahan'    => '0.5m - 3m',
    'kelas_bahan'       => 'Premium',
    'jenis_motif'       => '',
    'kondisi'           => '',
    'deskripsi'         => ''
];

// Ambil daftar UMKM yang sudah terdaftar untuk autocomplete / dropdown opsional
$registeredUsers = [];
try {
    $registeredUsers = $pdo->query("SELECT id, nama_usaha, sektor, wilayah, kontak FROM users ORDER BY nama_usaha ASC")->fetchAll();
} catch (\Throwable $t) {}

// Pemrosesan Submit Form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $namaUsaha       = trim($_POST['nama_usaha'] ?? '');
    $sektor          = trim($_POST['sektor'] ?? 'Tekstil');
    $kontak          = trim($_POST['kontak'] ?? '');
    $wilayah         = trim($_POST['wilayah'] ?? 'Jakarta Selatan');
    $namaBahan       = trim($_POST['nama_bahan'] ?? '');
    $kategori        = trim($_POST['kategori'] ?? 'Kain/Tekstil');
    $jumlah          = trim($_POST['jumlah'] ?? '');
    $satuan          = trim($_POST['satuan'] ?? 'kg');
    $hargaPerSatuan  = trim($_POST['harga_per_satuan'] ?? '0');
    $satuanJual      = trim($_POST['satuan_jual'] ?? 'kg');
    $ukuranPecahan   = trim($_POST['ukuran_pecahan'] ?? '0.5m - 3m');
    $kelasBahan      = trim($_POST['kelas_bahan'] ?? 'Standar');
    $jenisMotif      = trim($_POST['jenis_motif'] ?? '');
    $kondisi         = trim($_POST['kondisi'] ?? '');
    $deskripsi       = trim($_POST['deskripsi'] ?? '');

    // Simpan kembali data untuk form refill bila ada error
    $formData = compact('namaUsaha', 'sektor', 'kontak', 'wilayah', 'namaBahan', 'kategori', 'jumlah', 'satuan', 'hargaPerSatuan', 'satuanJual', 'ukuranPecahan', 'kelasBahan', 'jenisMotif', 'kondisi', 'deskripsi');

    // Validasi
    if (empty($namaUsaha)) {
        $errors[] = "Nama Usaha / UMKM wajib diisi.";
    }
    if (empty($kontak)) {
        $errors[] = "Kontak WhatsApp / Telepon wajib diisi untuk koordinasi pengambil.";
    }
    if (empty($namaBahan)) {
        $errors[] = "Nama Material Sisa wajib diisi.";
    }
    if (!is_numeric($jumlah) || (float)$jumlah <= 0) {
        $errors[] = "Jumlah kuantitas harus berupa angka lebih dari 0.";
    }
    if (!is_numeric($hargaPerSatuan) || (float)$hargaPerSatuan < 0) {
        $errors[] = "Harga per satuan harus berupa angka nominal yang valid.";
    }
    if (empty($kondisi)) {
        $errors[] = "Kondisi material wajib dijelaskan (contoh: Bersih, Sisa rol potongan, dll).";
    }

    // Jika valid, proses penyimpanan ke database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Cek apakah nama_usaha sudah ada di tabel users
            $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(nama_usaha)) = LOWER(?) LIMIT 1");
            $stmtCheckUser->execute([$namaUsaha]);
            $userId = $stmtCheckUser->fetchColumn();

            // Jika belum ada, insert ke tabel users
            if (!$userId) {
                $stmtInsertUser = $pdo->prepare("
                    INSERT INTO users (nama_usaha, sektor, wilayah, kontak) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmtInsertUser->execute([$namaUsaha, $sektor, $wilayah, $kontak]);
                $userId = (int) $pdo->lastInsertId();
            } else {
                // Perbarui kontak jika sebelumnya kosong / berubah
                $stmtUpdateUser = $pdo->prepare("UPDATE users SET kontak = ?, wilayah = ? WHERE id = ?");
                $stmtUpdateUser->execute([$kontak, $wilayah, $userId]);
            }

            // 2. Insert ke tabel materials dengan atribut B2B marketplace
            $stmtInsertMat = $pdo->prepare("
                INSERT INTO materials 
                (user_id, nama_bahan, kategori, jumlah, satuan, harga_per_satuan, satuan_jual, ukuran_pecahan, kelas_bahan, jenis_motif, kondisi, wilayah, deskripsi, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
            ");
            $stmtInsertMat->execute([
                $userId,
                $namaBahan,
                $kategori,
                (float) $jumlah,
                $satuan,
                (float) $hargaPerSatuan,
                $satuanJual,
                $ukuranPecahan,
                $kelasBahan,
                $jenisMotif,
                $kondisi,
                $wilayah,
                $deskripsi
            ]);

            $newMatId = $pdo->lastInsertId();
            $pdo->commit();

            // Redirect ke katalog.php dengan notifikasi sukses
            header("Location: katalog.php?added=1&id={$newMatId}#mat-{$newMatId}");
            exit;

        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Terjadi kegagalan saat menyimpan data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReBahan: Inovasi Platform Digital untuk Kolaborasi Pemanfaatan Material Sisa dan Kebutuhan Bahan Baku UMKM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex flex-col min-h-screen">

    <!-- Reusable Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        
        <!-- Breadcrumb / Header Title -->
        <div class="mb-8 max-w-4xl mx-auto">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-2">
                <span>🌱 Formulir Pasok Material Sirkular</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                Pasang Penawaran Material Sisa Produksi
            </h1>
            <p class="text-sm text-slate-600 mt-1">
                Ubah limbah dan potongan bahan sisa usaha Anda menjadi nilai tambah ekonomi dan bantu sesama UMKM Jakarta.
            </p>
        </div>

        <!-- Alert Error jika ada -->
        <?php if (!empty($errors)): ?>
            <div class="max-w-4xl mx-auto mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
                <div class="font-bold flex items-center mb-1">
                    <svg class="w-5 h-5 mr-1.5 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Mohon perbaiki data berikut:
                </div>
                <ul class="list-disc list-inside space-y-0.5 ml-1 text-xs">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Main Form & Guidelines Grid -->
        <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Form (8 Cols) -->
            <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                
                <form method="POST" action="penawaran.php" class="space-y-6">
                    
                    <!-- BAGIAN 1: IDENTITAS UMKM PENYEDIA -->
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-2">
                            <h2 class="text-base font-bold text-slate-900 flex items-center">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center mr-2 font-black">1</span>
                                Identitas Usaha & Kontak Anda
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Informasi lokasi dan narahubung untuk pengambilan material.</p>
                        </div>

                        <!-- Dropdown Pilihan Cepat atau Ketik Nama Usaha -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="nama_usaha" class="block text-xs font-bold text-slate-700">Nama Usaha / UMKM <span class="text-rose-500">*</span></label>
                                <?php if (!empty($registeredUsers)): ?>
                                    <span class="text-[11px] text-emerald-600 cursor-pointer hover:underline" onclick="toggleQuickSelect()">
                                        Pilih dari UMKM Terdaftar
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quick Select (Hidden by default) -->
                            <?php if (!empty($registeredUsers)): ?>
                                <select id="quick_select_user" class="hidden w-full mb-2 px-3 py-2 text-xs rounded-xl border border-emerald-300 bg-emerald-50 text-slate-800 focus:outline-none" onchange="applyUserQuickSelect(this)">
                                    <option value="">-- Pilih Profil UMKM Cepat --</option>
                                    <?php foreach ($registeredUsers as $ru): ?>
                                        <option value="<?= htmlspecialchars(json_encode($ru)) ?>"><?= htmlspecialchars($ru['nama_usaha']) ?> (<?= htmlspecialchars($ru['wilayah']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <input type="text" id="nama_usaha" name="nama_usaha" required 
                                   value="<?= htmlspecialchars($formData['namaUsaha'] ?? $formData['nama_usaha'] ?? '') ?>" 
                                   placeholder="Contoh: Penjahit Modesta Busana" 
                                   class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Sektor Usaha -->
                            <div>
                                <label for="sektor" class="block text-xs font-bold text-slate-700 mb-1">Sektor Usaha</label>
                                <select id="sektor" name="sektor" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                    <option value="Tekstil" <?= ($formData['sektor'] ?? '') === 'Tekstil' ? 'selected' : '' ?>>Tekstil & Konveksi</option>
                                    <option value="Percetakan" <?= ($formData['sektor'] ?? '') === 'Percetakan' ? 'selected' : '' ?>>Percetakan & Grafika</option>
                                    <option value="Kerajinan" <?= ($formData['sektor'] ?? '') === 'Kerajinan' ? 'selected' : '' ?>>Kerajinan & Seni</option>
                                    <option value="Mebel/Kayu" <?= ($formData['sektor'] ?? '') === 'Mebel/Kayu' ? 'selected' : '' ?>>Mebel & Palet</option>
                                    <option value="Manufaktur" <?= ($formData['sektor'] ?? '') === 'Manufaktur' ? 'selected' : '' ?>>Manufaktur & Logistik</option>
                                </select>
                            </div>

                            <!-- Wilayah Jakarta -->
                            <div>
                                <label for="wilayah" class="block text-xs font-bold text-slate-700 mb-1">Wilayah Jakarta <span class="text-rose-500">*</span></label>
                                <select id="wilayah" name="wilayah" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                    <option value="Jakarta Selatan" <?= ($formData['wilayah'] ?? '') === 'Jakarta Selatan' ? 'selected' : '' ?>>Jakarta Selatan</option>
                                    <option value="Jakarta Pusat" <?= ($formData['wilayah'] ?? '') === 'Jakarta Pusat' ? 'selected' : '' ?>>Jakarta Pusat</option>
                                    <option value="Jakarta Barat" <?= ($formData['wilayah'] ?? '') === 'Jakarta Barat' ? 'selected' : '' ?>>Jakarta Barat</option>
                                    <option value="Jakarta Timur" <?= ($formData['wilayah'] ?? '') === 'Jakarta Timur' ? 'selected' : '' ?>>Jakarta Timur</option>
                                    <option value="Jakarta Utara" <?= ($formData['wilayah'] ?? '') === 'Jakarta Utara' ? 'selected' : '' ?>>Jakarta Utara</option>
                                </select>
                            </div>

                            <!-- Kontak WhatsApp -->
                            <div>
                                <label for="kontak" class="block text-xs font-bold text-slate-700 mb-1">WhatsApp / HP <span class="text-rose-500">*</span></label>
                                <input type="text" id="kontak" name="kontak" required 
                                       value="<?= htmlspecialchars($formData['kontak'] ?? '') ?>" 
                                       placeholder="08xxxxxxxxxx" 
                                       class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- BAGIAN 2: RINCIAN MATERIAL SISA -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="border-b border-slate-100 pb-2">
                            <h2 class="text-base font-bold text-slate-900 flex items-center">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center mr-2 font-black">2</span>
                                Rincian Material Sisa Produksi
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Sebutkan kategori, volume, dan kondisi fisik bahan secara detail.</p>
                        </div>

                        <!-- Nama Bahan -->
                        <div>
                            <label for="nama_bahan" class="block text-xs font-bold text-slate-700 mb-1">Nama Bahan / Material Sisa <span class="text-rose-500">*</span></label>
                            <input type="text" id="nama_bahan" name="nama_bahan" required 
                                   value="<?= htmlspecialchars($formData['namaBahan'] ?? $formData['nama_bahan'] ?? '') ?>" 
                                   placeholder="Contoh: Sisa Kain Katun Rayon Corak Bunga" 
                                   class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Kategori -->
                            <div>
                                <label for="kategori" class="block text-xs font-bold text-slate-700 mb-1">Kategori Material <span class="text-rose-500">*</span></label>
                                <select id="kategori" name="kategori" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                    <option value="Kain/Tekstil" <?= ($formData['kategori'] ?? '') === 'Kain/Tekstil' ? 'selected' : '' ?>>🧵 Kain/Tekstil</option>
                                    <option value="Kertas/Karton" <?= ($formData['kategori'] ?? '') === 'Kertas/Karton' ? 'selected' : '' ?>>📦 Kertas/Karton</option>
                                    <option value="Plastik" <?= ($formData['kategori'] ?? '') === 'Plastik' ? 'selected' : '' ?>>♻️ Plastik</option>
                                    <option value="Kayu/Bambu" <?= ($formData['kategori'] ?? '') === 'Kayu/Bambu' ? 'selected' : '' ?>>🪵 Kayu/Bambu</option>
                                </select>
                            </div>

                            <!-- Kuantitas -->
                            <div>
                                <label for="jumlah" class="block text-xs font-bold text-slate-700 mb-1">Kuantitas Stok <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.1" id="jumlah" name="jumlah" required 
                                       value="<?= htmlspecialchars($formData['jumlah'] ?? '') ?>" 
                                       placeholder="Misal: 25.5" 
                                       class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>

                            <!-- Satuan Stok -->
                            <div>
                                <label for="satuan" class="block text-xs font-bold text-slate-700 mb-1">Satuan Stok <span class="text-rose-500">*</span></label>
                                <select id="satuan" name="satuan" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                    <option value="kg" <?= ($formData['satuan'] ?? '') === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                                    <option value="meter" <?= ($formData['satuan'] ?? '') === 'meter' ? 'selected' : '' ?>>Meter (panjang)</option>
                                    <option value="pcs" <?= ($formData['satuan'] ?? '') === 'pcs' ? 'selected' : '' ?>>Pcs / Buah / Lembar</option>
                                </select>
                            </div>
                        </div>

                        <!-- SPESIFIKASI B2B MARKETPLACE (Kelompok 2 Paper Specs) -->
                        <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100 space-y-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-black uppercase text-emerald-800 tracking-wider">🏷️ Spesifikasi B2B Marketplace & Penawaran</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Harga per Satuan -->
                                <div>
                                    <label for="harga_per_satuan" class="block text-xs font-bold text-slate-700 mb-1">Harga per Satuan (Rp) <span class="text-rose-500">*</span></label>
                                    <input type="number" step="500" id="harga_per_satuan" name="harga_per_satuan" required 
                                           value="<?= htmlspecialchars($formData['hargaPerSatuan'] ?? $formData['harga_per_satuan'] ?? '60000') ?>" 
                                           placeholder="Contoh: 60000" 
                                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                </div>

                                <!-- Satuan Jual -->
                                <div>
                                    <label for="satuan_jual" class="block text-xs font-bold text-slate-700 mb-1">Satuan Jual <span class="text-rose-500">*</span></label>
                                    <select id="satuan_jual" name="satuan_jual" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                        <option value="kg" <?= ($formData['satuanJual'] ?? $formData['satuan_jual'] ?? '') === 'kg' ? 'selected' : '' ?>>Per Kg (Timbangan)</option>
                                        <option value="borongan" <?= ($formData['satuanJual'] ?? $formData['satuan_jual'] ?? '') === 'borongan' ? 'selected' : '' ?>>Borongan (Ambil Semua)</option>
                                    </select>
                                </div>

                                <!-- Ukuran Pecahan -->
                                <div>
                                    <label for="ukuran_pecahan" class="block text-xs font-bold text-slate-700 mb-1">Ukuran Pecahan <span class="text-rose-500">*</span></label>
                                    <input type="text" id="ukuran_pecahan" name="ukuran_pecahan" required 
                                           value="<?= htmlspecialchars($formData['ukuranPecahan'] ?? $formData['ukuran_pecahan'] ?? '0.5m - 3m') ?>" 
                                           placeholder="Contoh: 0.5m - 3m" 
                                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                </div>

                                <!-- Kelas Bahan -->
                                <div>
                                    <label for="kelas_bahan" class="block text-xs font-bold text-slate-700 mb-1">Kelas Bahan <span class="text-rose-500">*</span></label>
                                    <select id="kelas_bahan" name="kelas_bahan" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                        <option value="Premium" <?= ($formData['kelasBahan'] ?? $formData['kelas_bahan'] ?? '') === 'Premium' ? 'selected' : '' ?>>Premium (Kondisi Prima)</option>
                                        <option value="Standar" <?= ($formData['kelasBahan'] ?? $formData['kelas_bahan'] ?? '') === 'Standar' ? 'selected' : '' ?>>Standar (Layak Olah)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Jenis Motif / Corak -->
                            <div>
                                <label for="jenis_motif" class="block text-xs font-bold text-slate-700 mb-1">Jenis Motif / Corak & Spesifikasi Khusus</label>
                                <input type="text" id="jenis_motif" name="jenis_motif" 
                                       value="<?= htmlspecialchars($formData['jenisMotif'] ?? $formData['jenis_motif'] ?? '') ?>" 
                                       placeholder="Contoh: Rayon Corak Bunga & Polos (estimasi ~6 meter/kg)" 
                                       class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>
                        </div>

                        <!-- Kondisi Material -->
                        <div>
                            <label for="kondisi" class="block text-xs font-bold text-slate-700 mb-1">Kondisi Material <span class="text-rose-500">*</span></label>
                            <input type="text" id="kondisi" name="kondisi" required 
                                   value="<?= htmlspecialchars($formData['kondisi'] ?? '') ?>" 
                                   placeholder="Contoh: Bersih, kering, sisa potongan rapi dari rol baru" 
                                   class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <!-- Deskripsi Tambahan & Rencana Pemanfaatan -->
                        <div>
                            <label for="deskripsi" class="block text-xs font-bold text-slate-700 mb-1">Catatan Pemanfaatan / Deskripsi Tambahan</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" 
                                      placeholder="Contoh: Cocok untuk kerajinan pouch, masker kain, perca patchwork, atau kerajinan tangan lainnya..." 
                                      class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"><?= htmlspecialchars($formData['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Tombol Aksi Submit -->
                    <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                        <a href="katalog.php" class="w-full sm:w-auto text-center px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Terbitkan ke Katalog
                        </button>
                    </div>

                </form>
            </div>

            <!-- Right Sidebar: Panduan Sirkular ReBahan (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Info Card 1 -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-3xl p-6 shadow-md shadow-emerald-500/20">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl mb-4">
                        💡
                    </div>
                    <h3 class="text-base font-bold mb-2">Mengapa Menawarkan Bahan Sisa?</h3>
                    <ul class="text-xs space-y-2 opacity-95">
                        <li class="flex items-start">
                            <span class="mr-1.5 font-bold">✓</span>
                            <span><strong>Hemat Biaya Buang:</strong> Mengurangi beban retribusi sampah industri Anda.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-1.5 font-bold">✓</span>
                            <span><strong>Dukung UMKM Lokal:</strong> Menjadi pahlawan rantai pasok bagi pengrajin kecil Jakarta.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-1.5 font-bold">✓</span>
                            <span><strong>Jejak Hijau:</strong> Menjadi bagian resmi pergerakan ekonomi sirkular ibu kota.</span>
                        </li>
                    </ul>
                </div>

                <!-- Info Card 2: Kriteria Material -->
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center">
                        <span class="text-emerald-600 mr-2">📋</span>
                        Kriteria Material yang Diterima
                    </h3>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li class="flex items-start">
                            <span class="text-emerald-500 mr-1.5">●</span>
                            <span><strong>Bersih & Kering:</strong> Bebas dari kontaminasi cairan beracun, oli, atau sampah basah.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-emerald-500 mr-1.5">●</span>
                            <span><strong>Terpilah:</strong> Tidak bercampur antara kain, plastik, atau material keras.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-emerald-500 mr-1.5">●</span>
                            <span><strong>Jujur pada Kondisi:</strong> Cantumkan ukuran atau cacat potongan agar pengambil dapat mengantisipasi.</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

    </main>

    <script>
        function toggleQuickSelect() {
            const el = document.getElementById('quick_select_user');
            if (el) {
                el.classList.toggle('hidden');
            }
        }

        function applyUserQuickSelect(selectEl) {
            if (!selectEl.value) return;
            try {
                const data = JSON.parse(selectEl.value);
                document.getElementById('nama_usaha').value = data.nama_usaha || '';
                document.getElementById('kontak').value = data.kontak || '';
                if (data.sektor) {
                    document.getElementById('sektor').value = data.sektor;
                }
                if (data.wilayah) {
                    document.getElementById('wilayah').value = data.wilayah;
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        ReBahan &copy; <?= date('Y') ?> - Platform Kolaborasi Ekonomi Sirkular UMKM Jakarta.
    </footer>

</body>
</html>

