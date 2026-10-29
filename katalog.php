<?php
// rebahan/website/public/katalog.php
// Katalog Material Sisa ReBahan dengan Filter Kategori, Wilayah Jakarta, dan Pengajuan Pengambilan

require_once __DIR__ . '/config.php';

// Inisialisasi variabel & filter
$search = trim($_GET['q'] ?? '');
$filterKategori = trim($_GET['kategori'] ?? '');
$filterWilayah = trim($_GET['wilayah'] ?? '');
$successMsg = '';
$errorMsg = '';

// Cek notifikasi redirect dari penawaran.php atau proses transaksi
if (isset($_GET['added'])) {
    $successMsg = "Material sisa Anda berhasil didaftarkan ke katalog ReBahan! Menunggu UMKM yang membutuhkan.";
}

// Tangani form "Ajukan Pengambilan" (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan_pengambilan') {
    $materialId = (int) ($_POST['material_id'] ?? 0);
    $receiverId = (int) ($_POST['receiver_id'] ?? 0);
    $namaReceiverBaru = trim($_POST['nama_receiver_baru'] ?? '');
    $kontakReceiverBaru = trim($_POST['kontak_receiver_baru'] ?? '');
    $wilayahReceiverBaru = trim($_POST['wilayah_receiver_baru'] ?? 'Jakarta Selatan');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($materialId <= 0) {
        $errorMsg = "Material yang dipilih tidak valid.";
    } else {
        try {
            // Jika memilih "Buat Profil UMKM Baru"
            if ($receiverId === 0 && !empty($namaReceiverBaru)) {
                $stmtNewUser = $pdo->prepare("
                    INSERT INTO users (nama_usaha, sektor, wilayah, kontak) 
                    VALUES (?, 'Kerajinan', ?, ?)
                ");
                $stmtNewUser->execute([$namaReceiverBaru, $wilayahReceiverBaru, $kontakReceiverBaru]);
                $receiverId = (int) $pdo->lastInsertId();
            }

            if ($receiverId > 0) {
                // Buat transaksi baru
                $stmtInsertTrx = $pdo->prepare("
                    INSERT INTO transactions (material_id, receiver_id, catatan, status) 
                    VALUES (?, ?, ?, 'Pending')
                ");
                $stmtInsertTrx->execute([$materialId, $receiverId, $catatan]);

                $successMsg = "Pengajuan pengambilan material berhasil dikirim! Status transaksi kini: Pending (menunggu konfirmasi penyedia).";
            } else {
                $errorMsg = "Silakan pilih atau isi profil UMKM pemohon pengambilan.";
            }
        } catch (\PDOException $e) {
            $errorMsg = "Gagal memproses pengajuan: " . $e->getMessage();
        }
    }
}

// Ambil daftar UMKM untuk dropdown pemohon
$allUsers = [];
try {
    $allUsers = $pdo->query("SELECT id, nama_usaha, wilayah FROM users ORDER BY nama_usaha ASC")->fetchAll();
} catch (\Throwable $e) {}

// Query Filter Material
$query = "
    SELECT m.*, u.nama_usaha, u.kontak, u.sektor
    FROM materials m
    JOIN users u ON m.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (m.nama_bahan LIKE ? OR m.deskripsi LIKE ? OR u.nama_usaha LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filterKategori !== '') {
    $query .= " AND m.kategori = ?";
    $params[] = $filterKategori;
}

if ($filterWilayah !== '') {
    $query .= " AND m.wilayah = ?";
    $params[] = $filterWilayah;
}

$query .= " ORDER BY (m.status = 'available') DESC, m.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();
} catch (\PDOException $e) {
    $materials = [];
    $errorMsg = "Gagal memuat katalog: " . $e->getMessage();
}

// Helper badge dan icon kategori
function getCategoryMeta($kategori) {
    return match($kategori) {
        'Kain/Tekstil'  => ['bg' => 'bg-pink-50', 'text' => 'text-pink-700', 'border' => 'border-pink-200', 'icon' => '🧵', 'color' => 'pink'],
        'Kertas/Karton' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => '📦', 'color' => 'amber'],
        'Plastik'       => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200', 'icon' => '♻️', 'color' => 'cyan'],
        'Kayu/Bambu'    => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => '🪵', 'color' => 'emerald'],
        default         => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => '✨', 'color' => 'slate']
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Material Sisa - ReBahan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex flex-col min-h-screen">

    <!-- Reusable Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        
        <!-- Header Banner -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Bursa Sirkular
                        </span>
                        <span class="text-xs text-slate-500">• Terbuka untuk seluruh pelaku usaha di Jakarta</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">
                        Katalog Material Sisa Produksi
                    </h1>
                    <p class="text-slate-600 text-sm mt-1">
                        Temukan sisa bahan baku kain, kertas, kayu, dan plastik dari UMKM mitra untuk kebutuhan usaha Anda.
                    </p>
                </div>
                <a href="penawaran.php" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-700 shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tawarkan Material Sisa Anda
                </a>
            </div>
        </div>

        <!-- Success/Error Alert -->
        <?php if ($successMsg): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start space-x-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm">
                    <strong>Berhasil!</strong> <?= htmlspecialchars($successMsg) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start space-x-3 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm">
                    <strong>Pemberitahuan:</strong> <?= htmlspecialchars($errorMsg) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- FILTER & SEARCH PANEL -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs mb-8">
            <form method="GET" action="katalog.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                <!-- Search Keyword -->
                <div class="lg:col-span-5">
                    <label for="q" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cari Bahan / UMKM</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="q" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Contoh: Kain katun, Art paper, Palet kayu..." 
                               class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Filter Kategori -->
                <div class="lg:col-span-3">
                    <label for="kategori" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Material</label>
                    <select id="kategori" name="kategori" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white">
                        <option value="">Semua Kategori</option>
                        <option value="Kain/Tekstil" <?= $filterKategori === 'Kain/Tekstil' ? 'selected' : '' ?>>🧵 Kain/Tekstil</option>
                        <option value="Kertas/Karton" <?= $filterKategori === 'Kertas/Karton' ? 'selected' : '' ?>>📦 Kertas/Karton</option>
                        <option value="Plastik" <?= $filterKategori === 'Plastik' ? 'selected' : '' ?>>♻️ Plastik</option>
                        <option value="Kayu/Bambu" <?= $filterKategori === 'Kayu/Bambu' ? 'selected' : '' ?>>🪵 Kayu/Bambu</option>
                    </select>
                </div>

                <!-- Filter Wilayah Jakarta -->
                <div class="lg:col-span-2">
                    <label for="wilayah" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Wilayah Jakarta</label>
                    <select id="wilayah" name="wilayah" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white">
                        <option value="">Semua Wilayah</option>
                        <option value="Jakarta Selatan" <?= $filterWilayah === 'Jakarta Selatan' ? 'selected' : '' ?>>Jakarta Selatan</option>
                        <option value="Jakarta Pusat" <?= $filterWilayah === 'Jakarta Pusat' ? 'selected' : '' ?>>Jakarta Pusat</option>
                        <option value="Jakarta Barat" <?= $filterWilayah === 'Jakarta Barat' ? 'selected' : '' ?>>Jakarta Barat</option>
                        <option value="Jakarta Timur" <?= $filterWilayah === 'Jakarta Timur' ? 'selected' : '' ?>>Jakarta Timur</option>
                        <option value="Jakarta Utara" <?= $filterWilayah === 'Jakarta Utara' ? 'selected' : '' ?>>Jakarta Utara</option>
                    </select>
                </div>

                <!-- Submit Filter Buttons -->
                <div class="lg:col-span-2 flex items-end space-x-2">
                    <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-xl transition shadow-xs">
                        Terapkan
                    </button>
                    <?php if ($search !== '' || $filterKategori !== '' || $filterWilayah !== ''): ?>
                        <a href="katalog.php" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition" title="Reset Filter">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Material Counter Banner -->
        <div class="flex items-center justify-between mb-6 text-sm text-slate-500">
            <div>
                Menampilkan <span class="font-bold text-slate-800"><?= count($materials) ?></span> material terdaftar
                <?php if ($filterKategori): ?>
                    dalam kategori <span class="font-semibold text-emerald-600"><?= htmlspecialchars($filterKategori) ?></span>
                <?php endif; ?>
                <?php if ($filterWilayah): ?>
                    di wilayah <span class="font-semibold text-emerald-600"><?= htmlspecialchars($filterWilayah) ?></span>
                <?php endif; ?>
            </div>
            <div class="text-xs">
                Diperbarui secara real-time
            </div>
        </div>

        <!-- MATERIAL GRID -->
        <?php if (empty($materials)): ?>
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-xl mx-auto shadow-sm my-8">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
                    🔍
                </div>
                <h3 class="text-lg font-bold text-slate-800">Tidak Menemukan Material yang Sesuai</h3>
                <p class="text-slate-500 text-sm mt-1 mb-6">
                    Coba ubah kata kunci pencarian atau bersihkan filter kategori dan wilayah.
                </p>
                <div class="flex justify-center space-x-3">
                    <a href="katalog.php" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-sm rounded-xl transition">
                        Reset Filter
                    </a>
                    <a href="penawaran.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl transition">
                        Pasang Bahan Baru
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($materials as $mat): 
                    $catMeta = getCategoryMeta($mat['kategori']);
                    $isAvailable = ($mat['status'] === 'available');
                ?>
                    <div id="mat-<?= $mat['id'] ?>" class="bg-white rounded-2xl border <?= $isAvailable ? 'border-slate-200 hover:border-emerald-300' : 'border-slate-200 bg-slate-50/70 opacity-80' ?> overflow-hidden shadow-xs hover:shadow-md transition-all duration-200 flex flex-col">
                        
                        <!-- Card Header & Badge -->
                        <div class="p-5 flex-1">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $catMeta['bg'] ?> <?= $catMeta['text'] ?> border <?= $catMeta['border'] ?>">
                                    <span class="mr-1"><?= $catMeta['icon'] ?></span>
                                    <?= htmlspecialchars($mat['kategori']) ?>
                                </span>

                                <?php if ($isAvailable): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                        Tersedia
                                    </span>
                                <?php elseif ($mat['status'] === 'booked'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                                        Dipesan
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-300 uppercase">
                                        Selesai
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Nama Bahan -->
                            <h3 class="text-lg font-bold text-slate-900 mb-1 leading-snug">
                                <?= htmlspecialchars($mat['nama_bahan']) ?>
                            </h3>

                            <!-- Kuantitas & Satuan -->
                            <div class="flex items-baseline space-x-1.5 my-2.5">
                                <span class="text-3xl font-black text-emerald-600">
                                    <?= (float)$mat['jumlah'] == (int)$mat['jumlah'] ? (int)$mat['jumlah'] : number_format($mat['jumlah'], 1) ?>
                                </span>
                                <span class="text-sm font-bold text-slate-700 uppercase">
                                    <?= htmlspecialchars($mat['satuan']) ?>
                                </span>
                            </div>

                            <!-- Kondisi Bahan -->
                            <div class="mb-3">
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kondisi:</div>
                                <div class="text-xs text-slate-700 font-medium bg-slate-50 p-2 rounded-lg border border-slate-100 mt-0.5">
                                    <?= htmlspecialchars($mat['kondisi']) ?>
                                </div>
                            </div>

                            <!-- Deskripsi Singkat -->
                            <?php if (!empty($mat['deskripsi'])): ?>
                                <p class="text-xs text-slate-500 mb-4 line-clamp-2">
                                    <?= htmlspecialchars($mat['deskripsi']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Info Penyedia & Lokasi -->
                            <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center">
                                        <svg class="w-3.5 h-3.5 text-slate-400 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <strong class="text-slate-700"><?= htmlspecialchars($mat['wilayah']) ?></strong>
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        <?= date('d/m/Y', strtotime($mat['created_at'])) ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between bg-emerald-50/50 p-2 rounded-lg border border-emerald-100/60">
                                    <div class="truncate">
                                        <span class="block text-[10px] text-emerald-800 font-semibold uppercase">Penyedia Material:</span>
                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($mat['nama_usaha']) ?></span>
                                    </div>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $mat['kontak']) ?>" target="_blank" class="px-2 py-1 text-[11px] font-bold text-emerald-700 bg-white hover:bg-emerald-100 rounded-md border border-emerald-200 flex items-center shadow-xs">
                                        WA
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Button -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100">
                            <?php if ($isAvailable): ?>
                                <button type="button" 
                                        onclick="openClaimModal(<?= $mat['id'] ?>, '<?= addslashes(htmlspecialchars($mat['nama_bahan'])) ?>', '<?= (float)$mat['jumlah'] ?> <?= htmlspecialchars($mat['satuan']) ?>', '<?= addslashes(htmlspecialchars($mat['nama_usaha'])) ?>', '<?= htmlspecialchars($mat['wilayah']) ?>')"
                                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Ajukan Pengambilan
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full py-2.5 px-4 bg-slate-200 text-slate-400 font-medium text-xs rounded-xl cursor-not-allowed">
                                    Sudah Tidak Tersedia
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- MODAL POPUP PENGAJUAN PENGAMBILAN MATERIAL -->
    <div id="claimModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 relative">
            
            <!-- Close Button -->
            <button type="button" onclick="closeClaimModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Modal Header -->
            <div class="mb-5">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                    Konfirmasi Sirkular
                </span>
                <h3 class="text-xl font-bold text-slate-900 mt-2">
                    Ajukan Pengambilan Material
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Kolaborasikan pemanfaatan material sisa secara langsung dengan UMKM penyedia.
                </p>
            </div>

            <!-- Material Summary Box -->
            <div class="bg-emerald-50/60 border border-emerald-200 rounded-2xl p-4 mb-5 text-xs text-slate-700 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Material:</span>
                    <strong id="modalMatName" class="text-slate-900 text-right">-</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Jumlah:</span>
                    <strong id="modalMatQty" class="text-emerald-700">-</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Penyedia:</span>
                    <span id="modalMatOwner" class="font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Lokasi:</span>
                    <span id="modalMatLoc" class="font-medium text-slate-800">-</span>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="katalog.php" class="space-y-4">
                <input type="hidden" name="action" value="ajukan_pengambilan">
                <input type="hidden" id="modalMatId" name="material_id" value="0">

                <!-- Pilih Profil UMKM Pemohon -->
                <div>
                    <label for="receiver_id" class="block text-xs font-bold text-slate-700 mb-1">Pilih UMKM Pemohon / Pengambil</label>
                    <select id="receiver_id" name="receiver_id" onchange="toggleNewReceiverFields(this.value)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        <option value="">-- Pilih Profil UMKM Anda --</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_usaha']) ?> (<?= htmlspecialchars($u['wilayah']) ?>)</option>
                        <?php endforeach; ?>
                        <option value="0">+ Daftarkan UMKM Baru Saya</option>
                    </select>
                </div>

                <!-- Input UMKM Baru jika belum terdaftar -->
                <div id="newReceiverFields" class="hidden space-y-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Usaha UMKM Anda</label>
                        <input type="text" name="nama_receiver_baru" placeholder="Contoh: Kriya Manik Johar" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Wilayah Anda</label>
                            <select name="wilayah_receiver_baru" class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                <option value="Jakarta Selatan">Jakarta Selatan</option>
                                <option value="Jakarta Pusat">Jakarta Pusat</option>
                                <option value="Jakarta Barat">Jakarta Barat</option>
                                <option value="Jakarta Timur">Jakarta Timur</option>
                                <option value="Jakarta Utara">Jakarta Utara</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak WhatsApp</label>
                            <input type="text" name="kontak_receiver_baru" placeholder="08xxxxxxxxxx" class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        </div>
                    </div>
                </div>

                <!-- Catatan Rencana Penggunaan -->
                <div>
                    <label for="catatan" class="block text-xs font-bold text-slate-700 mb-1">Rencana Pemanfaatan & Catatan Logistik</label>
                    <textarea id="catatan" name="catatan" rows="3" required placeholder="Jelaskan produk apa yang akan dibuat dengan material ini, dan perkiraan tanggal penjemputan..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeClaimModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openClaimModal(id, name, qty, owner, loc) {
            document.getElementById('modalMatId').value = id;
            document.getElementById('modalMatName').textContent = name;
            document.getElementById('modalMatQty').textContent = qty;
            document.getElementById('modalMatOwner').textContent = owner;
            document.getElementById('modalMatLoc').textContent = loc;
            document.getElementById('claimModal').classList.remove('hidden');
        }

        function closeClaimModal() {
            document.getElementById('claimModal').classList.add('hidden');
        }

        function toggleNewReceiverFields(val) {
            const el = document.getElementById('newReceiverFields');
            if (val === '0') {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }
    </script>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        ReBahan &copy; <?= date('Y') ?> - Platform Kolaborasi Ekonomi Sirkular UMKM Jakarta.
    </footer>

</body>
</html>

