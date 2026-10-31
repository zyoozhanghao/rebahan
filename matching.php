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
    <title>Smart Matching & Transaksi Sirkular - ReBahan</title>
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
                <form method="GET" action="matching.php" class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                    <div>
                        <select name="kategori" class="text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="Kain/Tekstil" <?= $matchKategori === 'Kain/Tekstil' ? 'selected' : '' ?>>🧵 Kain/Tekstil</option>
                            <option value="Kertas/Karton" <?= $matchKategori === 'Kertas/Karton' ? 'selected' : '' ?>>📦 Kertas/Karton</option>
                            <option value="Plastik" <?= $matchKategori === 'Plastik' ? 'selected' : '' ?>>♻️ Plastik</option>
                            <option value="Kayu/Bambu" <?= $matchKategori === 'Kayu/Bambu' ? 'selected' : '' ?>>🪵 Kayu/Bambu</option>
                        </select>
                    </div>
                    <div>
                        <select name="wilayah" class="text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="Jakarta Selatan" <?= $matchWilayah === 'Jakarta Selatan' ? 'selected' : '' ?>>Jakarta Selatan</option>
                            <option value="Jakarta Pusat" <?= $matchWilayah === 'Jakarta Pusat' ? 'selected' : '' ?>>Jakarta Pusat</option>
                            <option value="Jakarta Barat" <?= $matchWilayah === 'Jakarta Barat' ? 'selected' : '' ?>>Jakarta Barat</option>
                            <option value="Jakarta Timur" <?= $matchWilayah === 'Jakarta Timur' ? 'selected' : '' ?>>Jakarta Timur</option>
                            <option value="Jakarta Utara" <?= $matchWilayah === 'Jakarta Utara' ? 'selected' : '' ?>>Jakarta Utara</option>
                        </select>
                    </div>
                    <button type="submit" class="text-xs font-bold px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($recommendedMaterials as $rm): 
                        $score = (int) ($rm['match_score'] ?? 75);
                        $isSameRegion = ($rm['wilayah'] === $matchWilayah);
                    ?>
                        <div class="bg-gradient-to-b from-white to-slate-50/70 rounded-2xl border <?= $score >= 90 ? 'border-emerald-300 shadow-sm' : 'border-slate-200' ?> p-5 flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <!-- Match Score Badge -->
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold <?= $score >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' ?>">
                                        ⚡ Skor Cocok <?= $score ?>%
                                    </span>
                                    <?php if ($isSameRegion): ?>
                                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            🌱 1 Wilayah (Hemat Emisi)
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="text-sm font-bold text-slate-900 line-clamp-1 mb-1">
                                    <?= htmlspecialchars($rm['nama_bahan']) ?>
                                </h3>

                                <div class="text-xs text-slate-500 mb-2">
                                    Kategori: <strong class="text-slate-700"><?= htmlspecialchars($rm['kategori']) ?></strong>
                                </div>

                                <div class="flex items-baseline space-x-1 my-2">
                                    <span class="text-2xl font-black text-emerald-600">
                                        <?= (float)$rm['jumlah'] == (int)$rm['jumlah'] ? (int)$rm['jumlah'] : number_format($rm['jumlah'], 1) ?>
                                    </span>
                                    <span class="text-xs font-bold text-slate-600 uppercase">
                                        <?= htmlspecialchars($rm['satuan']) ?>
                                    </span>
                                </div>

                                <div class="text-xs text-slate-600 space-y-1 pt-2 border-t border-slate-100">
                                    <p><strong>Penyedia:</strong> <?= htmlspecialchars($rm['nama_usaha']) ?></p>
                                    <p><strong>Lokasi:</strong> <?= htmlspecialchars($rm['wilayah']) ?></p>
                                    <p class="text-[11px] text-slate-500 italic line-clamp-2">"<?= htmlspecialchars($rm['kondisi']) ?>"</p>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rm['kontak']) ?>" target="_blank" class="text-xs font-bold text-emerald-600 hover:underline">
                                    Chat WA
                                </a>
                                <a href="katalog.php?detail=<?= $rm['id'] ?>#mat-<?= $rm['id'] ?>" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                                    Ambil Bahan
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ========================================================================= -->
        <!-- BAGIAN 2: TABEL TRANSAKSI AKTIF & SELESAI -->
        <!-- ========================================================================= -->
        <section class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 flex items-center">
                        <span class="mr-2 text-2xl">📋</span>
                        Daftar Transaksi & Kolaborasi Material
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau pergerakan dan status pengalihan material dari pemohon hingga diterima.
                    </p>
                </div>
                <div class="text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    Total: <strong class="text-slate-800"><?= count($transactions) ?></strong> Kolaborasi
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="text-center py-12 bg-slate-50 rounded-2xl">
                    <p class="text-sm text-slate-500">Belum ada transaksi pengajuan material.</p>
                    <a href="katalog.php" class="inline-block mt-2 text-xs font-bold text-emerald-600 hover:underline">
                        Mulai dari Katalog Material &rarr;
                    </a>
                </div>
            <?php else: ?>
                <!-- Responsive Table Wrapper -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200 uppercase tracking-wider font-semibold">
                                <th class="py-3 px-4">ID & Tanggal</th>
                                <th class="py-3 px-4">Material & Kuantitas</th>
                                <th class="py-3 px-4">Penyedia (Sumber)</th>
                                <th class="py-3 px-4">Pengambil (Pemanfaat)</th>
                                <th class="py-3 px-4">Alasan & Rencana Penggunaan</th>
                                <th class="py-3 px-4">Status Transaksi</th>
                                <th class="py-3 px-4 text-right">Ubah Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($transactions as $trx): 
                                $badge = getTrxStatusBadge($trx['status']);
                                $alasanText = $trx['alasan_pengambilan'] ?: ($trx['reason_notes'] ?? ($trx['catatan'] ?? ''));
                            ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <!-- ID & Tanggal -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="font-bold text-slate-800">#TRX-<?= str_pad($trx['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            <?= date('d M Y H:i', strtotime($trx['created_at'])) ?>
                                        </div>
                                    </td>

                                    <!-- Material & Kuantitas -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 max-w-[180px] truncate" title="<?= htmlspecialchars($trx['nama_bahan']) ?>">
                                            <?= htmlspecialchars($trx['nama_bahan']) ?>
                                        </div>
                                        <div class="text-emerald-700 font-semibold mt-0.5">
                                            <?= (float)$trx['jumlah'] == (int)$trx['jumlah'] ? (int)$trx['jumlah'] : number_format($trx['jumlah'], 1) ?> <?= htmlspecialchars($trx['satuan']) ?>
                                            <span class="text-slate-400 font-normal">(<?= htmlspecialchars($trx['kategori']) ?>)</span>
                                        </div>
                                    </td>

                                    <!-- Penyedia -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars($trx['penyedia_nama']) ?></div>
                                        <div class="text-[11px] text-slate-500"><?= htmlspecialchars($trx['material_wilayah']) ?></div>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $trx['penyedia_kontak']) ?>" target="_blank" class="text-[10px] text-emerald-600 hover:underline">
                                            WA: <?= htmlspecialchars($trx['penyedia_kontak']) ?>
                                        </a>
                                    </td>

                                    <!-- Pengambil -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars($trx['pengambil_nama']) ?></div>
                                        <div class="text-[11px] text-slate-500"><?= htmlspecialchars($trx['pengambil_wilayah']) ?></div>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $trx['pengambil_kontak']) ?>" target="_blank" class="text-[10px] text-emerald-600 hover:underline">
                                            WA: <?= htmlspecialchars($trx['pengambil_kontak']) ?>
                                        </a>
                                    </td>

                                    <!-- Alasan & Rencana Penggunaan -->
                                    <td class="py-3.5 px-4 max-w-[220px]">
                                        <p class="text-slate-700 font-medium line-clamp-3 text-[11px] leading-relaxed" title="<?= htmlspecialchars($alasanText ?: 'Tidak ada keterangan') ?>">
                                            <?= htmlspecialchars($alasanText ?: 'Tidak ada keterangan') ?>
                                        </p>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold <?= $badge['bg'] ?> <?= $badge['text'] ?> border <?= $badge['border'] ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $badge['dot'] ?> mr-1.5 animate-pulse"></span>
                                            <?= htmlspecialchars($trx['status']) ?>
                                        </span>
                                    </td>

                                    <!-- Aksi Ubah Status (Interaktif) -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <form method="POST" action="matching.php" class="inline-flex items-center space-x-1">
                                            <input type="hidden" name="action" value="update_trx_status">
                                            <input type="hidden" name="trx_id" value="<?= $trx['id'] ?>">

                                            <?php if ($trx['status'] === 'Pending'): ?>
                                                <button type="submit" name="new_status" value="Disetujui" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-[11px] font-semibold transition" title="Setujui Pengambilan">
                                                    Setujui
                                                </button>
                                            <?php elseif ($trx['status'] === 'Disetujui'): ?>
                                                <button type="submit" name="new_status" value="Dalam Pengambilan" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-md text-[11px] font-semibold transition" title="Ubah status ke proses ambil">
                                                    Diambil
                                                </button>
                                            <?php elseif ($trx['status'] === 'Dalam Pengambilan'): ?>
                                                <button type="submit" name="new_status" value="Selesai" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[11px] font-semibold transition" title="Tandai Selesai & Selamatkan Bahan">
                                                    Selesai
                                                </button>
                                            <?php else: ?>
                                                <span class="text-[11px] text-emerald-600 font-bold">✓ Terserap</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        ReBahan &copy; <?= date('Y') ?> - Platform Kolaborasi Ekonomi Sirkular UMKM Jakarta.
    </footer>

</body>
</html>

