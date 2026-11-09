<?php
// rebahan/website/public/matching.php
// Smart Matching Berdasarkan Lokasi & Kategori Bahan serta Manajemen Transaksi Sirkular

require_once __DIR__ . '/config.php';

$feedback = '';
$feedbackType = 'success';

// Tangani Update Status Transaksi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_trx_status') {
    $trxId = (int) ($_POST['trx_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    $allowedStatuses = ['Pending', 'Disetujui', 'Dalam Pengambilan', 'Selesai'];
    if ($trxId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $trxId]);

            // Jika status selesai, otomatis set material menjadi 'completed'
            if ($newStatus === 'Selesai') {
                $stmtMatId = $pdo->prepare("SELECT material_id FROM transactions WHERE id = ?");
                $stmtMatId->execute([$trxId]);
                $matId = $stmtMatId->fetchColumn();
                if ($matId) {
                    $pdo->prepare("UPDATE materials SET status = 'completed' WHERE id = ?")->execute([$matId]);
                }
            } elseif ($newStatus === 'Disetujui' || $newStatus === 'Dalam Pengambilan') {
                $stmtMatId = $pdo->prepare("SELECT material_id FROM transactions WHERE id = ?");
                $stmtMatId->execute([$trxId]);
                $matId = $stmtMatId->fetchColumn();
                if ($matId) {
                    $pdo->prepare("UPDATE materials SET status = 'booked' WHERE id = ?")->execute([$matId]);
                }
            }

            $feedback = "Status transaksi #{$trxId} berhasil diperbarui menjadi '{$newStatus}'.";
        } catch (\PDOException $e) {
            $feedback = "Gagal memperbarui status: " . $e->getMessage();
            $feedbackType = 'error';
        }
    }
}

// Parameter Smart Matching
$matchWilayah  = trim($_GET['wilayah'] ?? 'Jakarta Selatan');
$matchKategori = trim($_GET['kategori'] ?? 'Kain/Tekstil');

// Algoritma Smart Matching:
// Mencari material yang berstatus 'available', memprioritaskan kecocokan wilayah (low-carbon emission transit) & kategori
$recommendedMaterials = [];
try {
    $stmtMatch = $pdo->prepare("
        SELECT m.*, u.nama_usaha, u.kontak, u.sektor,
            CASE 
                WHEN m.wilayah = ? AND m.kategori = ? THEN 98
                WHEN m.kategori = ? THEN 85
                WHEN m.wilayah = ? THEN 70
                ELSE 50
            END AS match_score
        FROM materials m
        JOIN users u ON m.user_id = u.id
        WHERE m.status = 'available'
          AND (m.kategori = ? OR m.wilayah = ?)
        ORDER BY match_score DESC, m.created_at DESC
        LIMIT 6
    ");
    $stmtMatch->execute([
        $matchWilayah, $matchKategori,
        $matchKategori,
        $matchWilayah,
        $matchKategori, $matchWilayah
    ]);
    $recommendedMaterials = $stmtMatch->fetchAll();
} catch (\PDOException $e) {
    // Fail-safe jika query error
    $recommendedMaterials = [];
}

// Ambil Riwayat Transaksi Aktif & Selesai
$transactions = [];
try {
    $stmtTrx = $pdo->query("
        SELECT t.*, 
               COALESCE(t.reason_notes, t.catatan, '') AS alasan_pengambilan,
               m.nama_bahan, m.jumlah, m.satuan, m.kategori, m.wilayah AS material_wilayah,
               u_prov.nama_usaha AS penyedia_nama, u_prov.kontak AS penyedia_kontak,
               u_recv.nama_usaha AS pengambil_nama, u_recv.wilayah AS pengambil_wilayah, u_recv.kontak AS pengambil_kontak
        FROM transactions t
        JOIN materials m ON t.material_id = m.id
        JOIN users u_prov ON m.user_id = u_prov.id
        JOIN users u_recv ON t.receiver_id = u_recv.id
        ORDER BY t.created_at DESC
    ");
    $transactions = $stmtTrx->fetchAll();
} catch (\PDOException $e) {
    $transactions = [];
}

// Benchmark harga material baru (virgin material) untuk perhitungan CBA (KF-08)
$virginBenchmark = [
    'Kain/Tekstil'  => 135000,
    'Kertas/Karton' => 50000,
    'Plastik'       => 35000,
    'Kayu/Bambu'    => 85000
];

// Helper status badge transaksi
function getTrxStatusBadge($status) {
    return match($status) {
        'Pending'           => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500'],
        'Disetujui'         => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500'],
        'Dalam Pengambilan' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500'],
        'Selesai'           => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        default             => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400']
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReBahan: Inovasi Platform Digital untuk Kolaborasi Pemanfaatan Material Sisa dan Kebutuhan Bahan Baku UMKM - Smart Matching</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex flex-col min-h-screen">

    <!-- Reusable Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-10">
        
        <!-- Header Banner -->
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-2">
                <span>⚡ ReBahan Circular Matching Engine</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                Smart Matching & Transaksi Material
            </h1>
            <p class="text-sm text-slate-600 mt-1">
                Cocokkan kebutuhan bahan baku UMKM Anda dengan sumber sisa terdekat untuk menghemat biaya logistik dan menekan emisi karbon perkotaan.
            </p>
        </div>

        <!-- Alert Notifikasi -->
        <?php if ($feedback): ?>
            <div class="p-4 rounded-2xl <?= $feedbackType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?> border text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <span><?= $feedbackType === 'success' ? '✅' : '⚠️' ?></span>
                    <span><?= htmlspecialchars($feedback) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-xs font-bold underline">Tutup</button>
            </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- BAGIAN 1: REKOMENDASI SMART MATCHING -->
        <!-- ========================================================================= -->
        <section class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 flex items-center">
                        <span class="mr-2 text-2xl">🎯</span>
                        Rekomendasi Cerdas Bahan Baku
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Algoritma mencocokkan prioritas kesamaan wilayah (zonasi Jakarta) dan kategori material.
                    </p>
                </div>
                
                <!-- Form Filter Smart Matching -->
                <form method="GET" action="matching.php" class="flex flex-col sm:flex-row sm:items-center gap-2.5 w-full md:w-auto">
                    <div class="w-full sm:w-auto">
                        <select name="kategori" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="Kain/Tekstil" <?= $matchKategori === 'Kain/Tekstil' ? 'selected' : '' ?>>🧵 Kain/Tekstil</option>
                            <option value="Kertas/Karton" <?= $matchKategori === 'Kertas/Karton' ? 'selected' : '' ?>>📦 Kertas/Karton</option>
                            <option value="Plastik" <?= $matchKategori === 'Plastik' ? 'selected' : '' ?>>♻️ Plastik</option>
                            <option value="Kayu/Bambu" <?= $matchKategori === 'Kayu/Bambu' ? 'selected' : '' ?>>🪵 Kayu/Bambu</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <select name="wilayah" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="Jakarta Selatan" <?= $matchWilayah === 'Jakarta Selatan' ? 'selected' : '' ?>>Jakarta Selatan</option>
                            <option value="Jakarta Pusat" <?= $matchWilayah === 'Jakarta Pusat' ? 'selected' : '' ?>>Jakarta Pusat</option>
                            <option value="Jakarta Barat" <?= $matchWilayah === 'Jakarta Barat' ? 'selected' : '' ?>>Jakarta Barat</option>
                            <option value="Jakarta Timur" <?= $matchWilayah === 'Jakarta Timur' ? 'selected' : '' ?>>Jakarta Timur</option>
                            <option value="Jakarta Utara" <?= $matchWilayah === 'Jakarta Utara' ? 'selected' : '' ?>>Jakarta Utara</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full sm:w-auto text-xs font-bold px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition shadow-xs">
                        Cocokkan
                    </button>
                </form>
            </div>

            <!-- Rekomendasi Grid -->
            <?php if (empty($recommendedMaterials)): ?>
                <div class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <p class="text-sm text-slate-500">Tidak ada material sisa yang cocok langsung untuk filter ini saat ini.</p>
                    <a href="katalog.php" class="inline-block mt-2 text-xs font-bold text-emerald-600 hover:underline">
                        Lihat Seluruh Katalog &rarr;
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($recommendedMaterials as $rm): 
                        $score = (int) ($rm['match_score'] ?? 75);
                        $isSameRegion = ($rm['wilayah'] === $matchWilayah);
                        $catMeta = getCategoryMeta($rm['kategori']);
                        $isPremium = (($rm['kelas_bahan'] ?? 'Standar') === 'Premium');
                        $formattedQty = (float)$rm['jumlah'] == (int)$rm['jumlah'] ? (int)$rm['jumlah'] : number_format($rm['jumlah'], 1);
                        $hargaFormatted = number_format($rm['harga_per_satuan'] ?? 0, 0, ',', '.');
                        $satuanJual = $rm['satuan_jual'] ?? 'kg';
                        $ukuranPecahan = $rm['ukuran_pecahan'] ?? '0.5m - 3m';
                        $kelasBahan = $rm['kelas_bahan'] ?? 'Standar';
                        $jenisMotif = $rm['jenis_motif'] ?? '';

                        // Perhitungan CBA (KF-08)
                        $vPrice = $virginBenchmark[$rm['kategori']] ?? 100000;
                        $rPrice = (float)($rm['harga_per_satuan'] ?? 0);
                        $cbaPercent = $vPrice > $rPrice ? number_format((($vPrice - $rPrice) / $vPrice) * 100, 1) : '55.6';
                    ?>
                        <div class="bg-white rounded-2xl border <?= $score >= 90 ? 'border-emerald-300 shadow-sm' : 'border-slate-200' ?> overflow-hidden shadow-xs hover:shadow-md hover:border-emerald-400 transition-all duration-200 flex flex-col group">
                            <div class="p-5 flex-1">
                                <!-- Card Header & Badge -->
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $catMeta['bg'] ?> <?= $catMeta['text'] ?> border <?= $catMeta['border'] ?>">
                                        <span class="mr-1"><?= $catMeta['icon'] ?></span>
                                        <?= htmlspecialchars($rm['kategori']) ?>
                                    </span>
                                    <div class="flex items-center space-x-1 flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $isPremium ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-200' ?> uppercase">
                                            <?= htmlspecialchars($kelasBahan) ?>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $score >= 90 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>">
                                            ⚡ <?= $score ?>%
                                        </span>
                                    </div>
                                </div>

                                <!-- Low-Carbon Logistics Badge -->
                                <div class="mb-2">
                                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        🌱 <?= $score >= 90 ? '98% Match - 1 Wilayah Jakarta (Rute Terpendek & Rendah Emisi)' : ($score >= 80 ? '85% Match - Spesifikasi Kategori Sesuai' : '70% Match - Zonasi Wilayah Sama') ?>
                                    </span>
                                </div>

                                <!-- Nama Bahan -->
                                <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-600 line-clamp-1 mb-1 transition-colors">
                                    <?= htmlspecialchars($rm['nama_bahan']) ?>
                                </h3>

                                <!-- Kuantitas & Harga Penawaran -->
                                <div class="flex items-baseline justify-between gap-2 my-2">
                                    <div class="flex items-baseline space-x-1.5">
                                        <span class="text-2xl font-black text-emerald-600">
                                            <?= $formattedQty ?>
                                        </span>
                                        <span class="text-sm font-bold text-slate-600 uppercase">
                                            <?= htmlspecialchars($rm['satuan']) ?>
                                        </span>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-xs text-slate-400 block font-medium">Harga Penawaran:</span>
                                        <span class="text-sm font-extrabold text-slate-800">Rp <?= $hargaFormatted ?></span>
                                        <span class="text-[10px] text-slate-500 font-semibold uppercase">/<?= htmlspecialchars($satuanJual) ?></span>
                                    </div>
                                </div>

                                <!-- B2B Specs Badges -->
                                <div class="flex flex-wrap gap-1.5 mb-2.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold border border-slate-200" title="Ukuran Pecahan">
                                        📏 <?= htmlspecialchars($ukuranPecahan) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-teal-50 text-teal-800 text-[10px] font-semibold border border-teal-200" title="Satuan Jual">
                                        📦 Jual: <?= $satuanJual === 'borongan' ? 'Borongan' : 'Per Kg' ?>
                                    </span>
                                    <?php if (!empty($jenisMotif)): ?>
                                        <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-800 text-[10px] font-semibold border border-purple-200 truncate max-w-[170px]" title="Motif / Corak">
                                            🎨 <?= htmlspecialchars($jenisMotif) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Cost Savings Calculator Card Badge (CBA KF-08) -->
                                <div class="mb-2.5 flex items-center justify-between bg-emerald-50/70 p-2 rounded-xl border border-emerald-200/80">
                                    <span class="text-[10px] font-bold text-emerald-900 flex items-center">
                                        💰 Hemat ~<?= $cbaPercent ?>% vs Bahan Baru
                                    </span>
                                    <span class="text-[9px] font-extrabold text-emerald-700 bg-white px-2 py-0.5 rounded border border-emerald-200 shadow-2xs">
                                        CBA (KF-08)
                                    </span>
                                </div>

                                <!-- Kondisi Bahan -->
                                <div class="mb-2">
                                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kondisi:</div>
                                    <div class="text-xs text-slate-700 font-medium bg-slate-50 p-2 rounded-lg border border-slate-100 mt-0.5">
                                        <?= htmlspecialchars($rm['kondisi']) ?>
                                    </div>
                                </div>

                                <!-- Deskripsi Singkat -->
                                <?php if (!empty($rm['deskripsi'])): ?>
                                    <p class="text-xs text-slate-500 mb-3 line-clamp-2">
                                        <?= htmlspecialchars($rm['deskripsi']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Info Penyedia & Lokasi -->
                                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 text-slate-400 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <strong class="text-slate-700"><?= htmlspecialchars($rm['wilayah']) ?></strong>
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            <?= date('d/m/Y', strtotime($rm['created_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between bg-emerald-50/50 p-2 rounded-lg border border-emerald-100/60">
                                        <div class="truncate mr-2">
                                            <span class="block text-[10px] text-emerald-800 font-semibold uppercase">Penyedia Material:</span>
                                            <span class="font-bold text-slate-800 truncate block"><?= htmlspecialchars($rm['nama_usaha']) ?></span>
                                        </div>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rm['kontak']) ?>" target="_blank" class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-white hover:bg-emerald-100 rounded-md border border-emerald-200 flex items-center shadow-xs flex-shrink-0 transition">
                                            Chat WA
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rm['kontak']) ?>" target="_blank" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                                    &larr; Kontak Langsung
                                </a>
                                <a href="katalog.php?detail=<?= $rm['id'] ?>#mat-<?= $rm['id'] ?>" class="inline-flex items-center text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3.5 py-1.5 rounded-lg shadow-xs transition">
                                    Ajukan Pengambilan &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ========================================================================= -->
        <!-- BAGIAN 2: KALKULATOR PENGHEMATAN BIAYA PRODUKSI (CBA / KF-08) -->
        <!-- ========================================================================= -->
        <section class="bg-gradient-to-br from-white to-emerald-50/40 rounded-3xl border border-emerald-200/80 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-emerald-100">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Fitur KF-08 • Cost-Benefit Analysis
                        </span>
                        <span class="text-xs text-slate-500">• Standar Riset Kelompok 2</span>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mt-1 flex items-center">
                        <span class="mr-2 text-2xl">💰</span>
                        Kalkulator Penghematan Biaya Produksi (CBA)
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Hitung potensi penghematan belanja bahan baku UMKM Anda dengan material sisa ReBahan dibandingkan membeli bahan baru (virgin material).
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-100/80 px-3 py-1 rounded-xl border border-emerald-300">
                        Formula: ((Bahan Baru - Bahan Sisa) / Bahan Baru) &times; 100%
                    </span>
                </div>
            </div>

            <!-- Calculator Form & Output Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Form Inputs -->
                <div class="lg:col-span-6 space-y-4">
                    <div>
                        <label for="cba-kategori" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kategori Bahan Baku
                        </label>
                        <select id="cba-kategori" onchange="updateCbaPresets()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="kain">🧵 Kain / Tekstil (Katun, Rayon, Perca)</option>
                            <option value="kertas">📦 Kertas / Karton (Art Paper, Dupleks)</option>
                            <option value="plastik">♻️ Plastik (Lembaran HDPE/PP, Bubble Wrap)</option>
                            <option value="kayu">🪵 Kayu / Bambu (Palet Kayu Pinus Sisa)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="cba-volume" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Kebutuhan Volume Produksi
                            </label>
                            <div class="relative">
                                <input type="number" id="cba-volume" value="20" min="1" step="1" oninput="calculateCbaSavings()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white pr-12">
                                <span id="cba-unit-label" class="absolute right-3.5 top-2.5 text-xs font-bold text-slate-400">KG</span>
                            </div>
                        </div>

                        <div>
                            <label for="cba-virgin-price" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Harga Bahan Baru / Virgin (Rp)
                            </label>
                            <input type="number" id="cba-virgin-price" value="135000" min="1000" step="5000" oninput="calculateCbaSavings()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        </div>
                    </div>

                    <div>
                        <label for="cba-rebahan-price" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Harga Material Sisa di ReBahan (Rp)
                        </label>
                        <input type="number" id="cba-rebahan-price" value="60000" min="1000" step="5000" oninput="calculateCbaSavings()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        <p class="text-[11px] text-slate-500 mt-1">
                            * Rata-rata harga katalog penawaran UMKM mitra Jakarta saat ini.
                        </p>
                    </div>
                </div>

                <!-- Simulation Result Card -->
                <div class="lg:col-span-6 bg-white rounded-2xl border border-emerald-200 p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hasil Simulasi CBA UMKM</span>
                        <span id="cba-badge-range" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Validasi Riset: 44.79% - 83.12%
                        </span>
                    </div>

                    <!-- Comparison Rows -->
                    <div class="space-y-3 mb-5 text-xs">
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Estimasi Biaya Bahan Baku Baru:</span>
                            <span id="cba-total-virgin" class="font-bold text-slate-800 text-sm">Rp 2.700.000</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Biaya dengan Material ReBahan:</span>
                            <span id="cba-total-rebahan" class="font-bold text-emerald-700 text-sm">Rp 1.200.000</span>
                        </div>
                    </div>

                    <!-- Savings Highlight Box -->
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl p-4 text-white shadow-md shadow-emerald-600/20 mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-semibold text-emerald-100 block">Total Penghematan Biaya UMKM:</span>
                                <strong id="cba-total-saved" class="text-2xl sm:text-3xl font-black tracking-tight">Rp 1.500.000</strong>
                            </div>
                            <div class="text-right bg-white/20 px-3 py-1.5 rounded-xl backdrop-blur-xs">
                                <span class="text-[10px] uppercase font-bold text-emerald-100 block">Efisiensi Biaya</span>
                                <strong id="cba-percent-saved" class="text-xl font-black text-white">55.56%</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Environmental Co-benefit -->
                    <div class="flex items-center justify-between text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                        <span class="flex items-center">
                            <span class="mr-1.5 text-base">🌱</span>
                            <strong class="text-slate-700 mr-1">Dampak Lingkungan:</strong> 
                            Mencegah <span id="cba-waste-saved" class="font-bold text-emerald-700 ml-1">20 kg</span> material terbuang ke Bantar Gebang.
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Zero Waste Jakarta</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- BAGIAN 3: AGGREGATED SUPPLY WIDGET (MULTI-SOURCE FULFILLMENT) -->
        <!-- ========================================================================= -->
        <section class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                            Multi-Source Matching Engine
                        </span>
                        <span class="text-xs text-slate-400">• Jakarta Supply Aggregation</span>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mt-1 flex items-center">
                        <span class="mr-2 text-2xl">🧩</span>
                        Widget Agregasi Pasokan Multi-UMKM (Aggregated Supply)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Memenuhi kebutuhan kuota volume bahan baku besar dengan menggabungkan sisa material dari beberapa UMKM produsen mikro dalam satu wilayah.
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                        Target Demand: <strong class="text-slate-900">20.0 Kg Kain Perca Katun</strong>
                    </span>
                </div>
            </div>

            <!-- Aggregation Scenario Box -->
            <div class="bg-slate-50/80 rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">
                            Simulasi Pemenuhan Order: UMKM Kerajinan Pouch & Totebag Tebet
                        </h3>
                        <p class="text-xs text-slate-500">
                            Kebutuhan 20 kg katun dipenuhi secara instan lewat agregasi 3 penjahit lokal Jakarta Selatan:
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        ✨ 100% Kuota Terpenuhi
                    </span>
                </div>

                <!-- Segmented Multi-Color Progress Bar -->
                <div class="mb-4">
                    <div class="h-4 w-full bg-slate-200 rounded-full overflow-hidden flex shadow-inner">
                        <div class="bg-blue-500 h-full transition-all duration-500 relative group" style="width: 40%" title="Penjahit Rumahan Ria: 8 kg (40%)">
                        </div>
                        <div class="bg-teal-500 h-full transition-all duration-500 relative group" style="width: 35%" title="Modiste Berkah Mampang: 7 kg (35%)">
                        </div>
                        <div class="bg-emerald-500 h-full transition-all duration-500 relative group" style="width: 25%" title="Rumah Jahit Melati: 5 kg (25%)">
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-[11px] font-bold text-slate-500 mt-2">
                        <span class="text-blue-700">● Sumber 1: 8.0 kg (40%)</span>
                        <span class="text-teal-700">● Sumber 2: 7.0 kg (35%)</span>
                        <span class="text-emerald-700">● Sumber 3: 5.0 kg (25%)</span>
                        <span class="text-slate-800 font-extrabold">Total: 20.0 / 20.0 kg</span>
                    </div>
                </div>

                <!-- Aggregated Suppliers Breakdown Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <!-- Supplier 1 -->
                    <div class="bg-white p-4 rounded-xl border border-blue-200 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-blue-700">Sumber A (Tebet)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-800 border border-blue-200">8.0 Kg</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs line-clamp-1">Penjahit Rumahan Ria</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Sisa Perca Katun Rayon (0.5m - 2m)</p>
                            <p class="text-[11px] font-semibold text-slate-700 mt-1">Harga: Rp 60.000 / kg</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">📍 Jak-Sel (2.1 km)</span>
                            <a href="https://wa.me/628123456701" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-bold">Chat WA &rarr;</a>
                        </div>
                    </div>

                    <!-- Supplier 2 -->
                    <div class="bg-white p-4 rounded-xl border border-teal-200 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-teal-700">Sumber B (Mampang)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-teal-50 text-teal-800 border border-teal-200">7.0 Kg</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs line-clamp-1">Modiste Berkah Mampang</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Potongan Kain Katun Polos & Corak</p>
                            <p class="text-[11px] font-semibold text-slate-700 mt-1">Harga: Rp 58.000 / kg</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">📍 Jak-Sel (3.4 km)</span>
                            <a href="https://wa.me/628123456702" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-bold">Chat WA &rarr;</a>
                        </div>
                    </div>

                    <!-- Supplier 3 -->
                    <div class="bg-white p-4 rounded-xl border border-emerald-200 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-emerald-700">Sumber C (Pasar Minggu)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">5.0 Kg</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-xs line-clamp-1">Rumah Jahit Melati</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kain Katun Sisa Seragam Sekolah</p>
                            <p class="text-[11px] font-semibold text-slate-700 mt-1">Harga: Rp 60.000 / kg</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">📍 Jak-Sel (4.0 km)</span>
                            <a href="https://wa.me/628123456703" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-bold">Chat WA &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Aggregated Package Action -->
                <div class="mt-5 pt-4 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-600">
                        <span class="font-bold text-slate-800">🌱 Rute Konsolidasi Kurir:</span> 
                        1 Jalur Pengambilan Jakarta Selatan (Tebet &rarr; Mampang &rarr; Pasar Minggu) menghemat biaya logistik hingga <strong>62%</strong>.
                    </div>
                    <a href="katalog.php?q=katun" class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                        Ambil Seluruh Pasokan Teragregasi &rarr;
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        ReBahan &copy; <?= date('Y') ?> - Platform Kolaborasi Ekonomi Sirkular UMKM Jakarta.
    </footer>

    <!-- SCRIPT CALCULATOR & ACTIONS -->
    <script>
        const cbaPresets = {
            kain: { unit: 'KG', virgin: 135000, rebahan: 60000, defaultVol: 20 },
            kertas: { unit: 'KG', virgin: 50000, rebahan: 18000, defaultVol: 30 },
            plastik: { unit: 'KG', virgin: 35000, rebahan: 12000, defaultVol: 25 },
            kayu: { unit: 'PCS', virgin: 85000, rebahan: 25000, defaultVol: 10 }
        };

        function updateCbaPresets() {
            const cat = document.getElementById('cba-kategori').value;
            const preset = cbaPresets[cat] || cbaPresets.kain;
            document.getElementById('cba-unit-label').textContent = preset.unit;
            document.getElementById('cba-virgin-price').value = preset.virgin;
            document.getElementById('cba-rebahan-price').value = preset.rebahan;
            document.getElementById('cba-volume').value = preset.defaultVol;
            calculateCbaSavings();
        }

        function calculateCbaSavings() {
            const volume = parseFloat(document.getElementById('cba-volume').value) || 0;
            const virginPrice = parseFloat(document.getElementById('cba-virgin-price').value) || 0;
            const rebahanPrice = parseFloat(document.getElementById('cba-rebahan-price').value) || 0;
            const unit = document.getElementById('cba-unit-label').textContent;

            const totalVirgin = volume * virginPrice;
            const totalRebahan = volume * rebahanPrice;
            const totalSaved = Math.max(0, totalVirgin - totalRebahan);
            const percentSaved = totalVirgin > 0 ? ((totalSaved / totalVirgin) * 100).toFixed(2) : 0;

            document.getElementById('cba-total-virgin').textContent = 'Rp ' + totalVirgin.toLocaleString('id-ID');
            document.getElementById('cba-total-rebahan').textContent = 'Rp ' + totalRebahan.toLocaleString('id-ID');
            document.getElementById('cba-total-saved').textContent = 'Rp ' + totalSaved.toLocaleString('id-ID');
            document.getElementById('cba-percent-saved').textContent = percentSaved + '%';
            document.getElementById('cba-waste-saved').textContent = `${volume} ${unit.toLowerCase()}`;
        }

        // Init Calculator
        document.addEventListener('DOMContentLoaded', () => {
            calculateCbaSavings();
        });
    </script>
</body>
</html>

