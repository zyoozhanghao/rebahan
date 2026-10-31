<?php
// rebahan/website/public/index.php
// Landing Page & Impact Tracker ReBahan

require_once __DIR__ . '/config.php';

// Inisialisasi variabel statistik
$totalTerselamatkanKg = 0;
$totalUmkm = 0;
$totalTransaksiBerhasil = 0;
$latestMaterials = [];
$dbError = null;

try {
    // 1. Total Material Terselamatkan (dalam satuan kg)
    // Dihitung dari material yang berstatus 'completed' atau transaksi 'Selesai'
    $stmtKg = $pdo->query("
        SELECT COALESCE(SUM(
            CASE 
                WHEN satuan = 'kg' THEN jumlah 
                WHEN satuan = 'meter' THEN jumlah * 0.35 -- estimasi konversi berat kain
                WHEN satuan = 'pcs' THEN jumlah * 1.5   -- estimasi konversi kayu/karton
                ELSE jumlah 
            END
        ), 0) AS total_kg
        FROM materials 
        WHERE status = 'completed'
    ");
    $totalTerselamatkanKg = round((float) $stmtKg->fetchColumn(), 1);

    // Jika 0, tambahkan agregat transaksi berstatus 'Selesai'
    if ($totalTerselamatkanKg == 0) {
        $stmtTrxKg = $pdo->query("
            SELECT COALESCE(SUM(m.jumlah), 0)
            FROM transactions t
            JOIN materials m ON t.material_id = m.id
            WHERE t.status = 'Selesai' AND m.satuan = 'kg'
        ");
        $totalTerselamatkanKg = round((float) $stmtTrxKg->fetchColumn(), 1);
    }

    // 2. Total UMKM Terhubung
    $stmtUmkm = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUmkm = (int) $stmtUmkm->fetchColumn();

    // 3. Total Transaksi Berhasil / Disetujui
    $stmtTrx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status IN ('Selesai', 'Disetujui', 'Dalam Pengambilan')");
    $totalTransaksiBerhasil = (int) $stmtTrx->fetchColumn();

    // 4. Preview Material Sisa Terbaru yang Tersedia
    $stmtLatest = $pdo->query("
        SELECT m.*, u.nama_usaha, u.kontak, u.sektor
        FROM materials m
        JOIN users u ON m.user_id = u.id
        WHERE m.status = 'available'
        ORDER BY m.created_at DESC
        LIMIT 6
    ");
    $latestMaterials = $stmtLatest->fetchAll();

} catch (\PDOException $e) {
    $dbError = $e->getMessage();
}

// Helper badge kategori warna
function getCategoryBadge($cat) {
    return match($cat) {
        'Kain/Tekstil'  => ['bg' => 'bg-pink-50', 'text' => 'text-pink-700', 'border' => 'border-pink-200', 'icon' => '🧵'],
        'Kertas/Karton' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => '📦'],
        'Plastik'       => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200', 'icon' => '♻️'],
        'Kayu/Bambu'    => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => '🪵'],
        default         => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => '✨']
    };
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReBahan: Inovasi Platform Digital untuk Kolaborasi Pemanfaatan Material Sisa dan Kebutuhan Bahan Baku UMKM</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex flex-col min-h-screen">

    <!-- Reusable Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Error Banner jika terjadi masalah database -->
    <?php if ($dbError): ?>
        <div class="bg-red-50 border-b border-red-200 px-4 py-3 text-red-700 text-sm">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <span><strong>Peringatan Database:</strong> <?= htmlspecialchars($dbError) ?></span>
                <a href="setup.php" class="underline font-semibold">Jalankan Setup Ulang</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- HERO SECTION -->
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50/70 via-white to-slate-50 pt-14 pb-20 border-b border-emerald-100/60">
        <!-- Background Decorative SVG Circle -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-300/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-teal-300/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Text Content -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-emerald-100/80 text-emerald-800 text-xs font-semibold tracking-wide border border-emerald-200 shadow-xs">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Gerakan Ekonomi Sirkular Wilayah DKI Jakarta</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.15]">
                        Dari <span class="text-emerald-600 underline decoration-emerald-300 decoration-wavy decoration-2">Material Sisa</span>, Jadi Berkah & Bahan Baku UMKM.
                    </h1>

                    <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                        <strong>ReBahan</strong> menjembatani UMKM penghasil material produksi sisa (konveksi, percetakan, pengrajin kayu) dengan UMKM kreatif yang membutuhkan bahan baku terjangkau di 5 wilayah Jakarta. Kurangi sampah, tingkatkan nilai ekonomi!
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="katalog.php" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-lg shadow-emerald-600/25 transition-all transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Jelajahi Katalog Material
                        </a>
                        <a href="penawaran.php" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-sm transition-all hover:border-emerald-300">
                            <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Pasang Penawaran Sisa
                        </a>
                        <a href="matching.php" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3.5 rounded-xl text-base font-medium text-emerald-700 bg-emerald-50/80 hover:bg-emerald-100/80 border border-emerald-200 transition-all">
                            ⚡ Smart Match
                        </a>
                    </div>

                    <!-- Micro Social Proof -->
                    <div class="pt-4 flex items-center justify-center lg:justify-start space-x-6 text-xs text-slate-500 font-medium">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Bebas Biaya Perantara
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Fokus 5 Wilayah DKI Jakarta
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Ekonomi Sirkular Terverifikasi
                        </div>
                    </div>
                </div>

                <!-- Circular Graphic Card Illustration -->
                <div class="lg:col-span-5">
                    <div class="relative mx-auto max-w-md bg-white rounded-3xl p-6 shadow-xl shadow-slate-200/60 border border-slate-200/80">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                                Ekosistem Sirkular Aktif
                            </span>
                        </div>

                        <!-- Mini Flow Interactive Diagram -->
                        <div class="py-6 space-y-4">
                            <!-- Flow item 1 -->
                            <div class="flex items-center space-x-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="w-10 h-10 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                    🧵
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">UMKM Sumber Sisa Material</p>
                                    <p class="text-sm font-semibold text-slate-800 truncate">Penjahit & Konveksi Tebet (Jaksel)</p>
                                </div>
                                <span class="text-xs font-bold text-pink-600 bg-pink-50 px-2 py-0.5 rounded">35 kg Kain</span>
                            </div>

                            <!-- Flow arrow -->
                            <div class="flex justify-center -my-2 text-emerald-500">
                                <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>

                            <!-- ReBahan Match Hub -->
                            <div class="p-3 rounded-xl bg-emerald-500 text-white shadow-md shadow-emerald-500/20 text-center">
                                <p class="text-xs uppercase tracking-wider font-semibold opacity-90">Algoritma ReBahan Matching</p>
                                <p class="text-sm font-bold mt-0.5">Mencocokkan Wilayah Terdekat & Efisiensi Bahan</p>
                            </div>

                            <!-- Flow arrow -->
                            <div class="flex justify-center -my-2 text-emerald-500">
                                <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>

                            <!-- Flow item 2 -->
                            <div class="flex items-center space-x-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                    👜
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">UMKM Pengrajin Kreatif</p>
                                    <p class="text-sm font-semibold text-slate-800 truncate">Kreasi Tas & Pouch Daur Ulang</p>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Terserap 100%</span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="pt-3 border-t border-slate-100 text-center">
                            <span class="text-xs text-slate-500 font-medium">
                                Mengurangi emisi TPA Bantar Gebang & menekan biaya modal UMKM hingga 40%.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 STAT KPI CARDS SECTION (IMPACT TRACKER) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- KPI Card 1: Total Material Terselamatkan -->
            <div class="bg-white rounded-2xl p-6 shadow-md border border-emerald-100 hover:shadow-lg transition-all duration-200 group">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold uppercase tracking-wider text-emerald-600">Dampak Lingkungan</span>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-4xl font-extrabold text-slate-900 tracking-tight">
                        <?= number_format($totalTerselamatkanKg, 1, ',', '.') ?>
                    </span>
                    <span class="text-xl font-bold text-emerald-600">kg</span>
                </div>
                <h3 class="text-sm font-bold text-slate-700 mt-1">Total Material Terselamatkan</h3>
                <p class="text-xs text-slate-500 mt-1">Material sisa teralihkan dari TPA menuju siklus produksi baru di Jakarta.</p>
            </div>

            <!-- KPI Card 2: UMKM Terhubung -->
            <div class="bg-white rounded-2xl p-6 shadow-md border border-blue-100 hover:shadow-lg transition-all duration-200 group">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold uppercase tracking-wider text-blue-600">Ekosistem Bisnis</span>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-4xl font-extrabold text-slate-900 tracking-tight">
                        <?= number_format($totalUmkm) ?>
                    </span>
                    <span class="text-xl font-bold text-blue-600">Mitra</span>
                </div>
                <h3 class="text-sm font-bold text-slate-700 mt-1">UMKM Terhubung</h3>
                <p class="text-xs text-slate-500 mt-1">Produsen sisa & pengrajin kreatif dari 5 wilayah administratif DKI Jakarta.</p>
            </div>

            <!-- KPI Card 3: Transaksi Berhasil -->
            <div class="bg-white rounded-2xl p-6 shadow-md border border-amber-100 hover:shadow-lg transition-all duration-200 group">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold uppercase tracking-wider text-amber-600">Sirkularitas Riil</span>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-4xl font-extrabold text-slate-900 tracking-tight">
                        <?= number_format($totalTransaksiBerhasil) ?>
                    </span>
                    <span class="text-xl font-bold text-amber-600">Transaksi</span>
                </div>
                <h3 class="text-sm font-bold text-slate-700 mt-1">Kolaborasi Berhasil</h3>
                <p class="text-xs text-slate-500 mt-1">Pertukaran material sisa yang telah disepakati dan dalam proses pemanfaatan.</p>
            </div>
        </div>
    </section>

    <!-- CARA KERJA REBAHAN SECTION -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                Alur Mudah 3 Langkah
            </span>
            <h2 class="text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">
                Bagaimana ReBahan Bekerja?
            </h2>
            <p class="text-slate-600 text-sm sm:text-base mt-2">
                Menghubungkan rantai pasok ekonomi sirkular lokal secara cepat, transparan, dan terarah.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Langkah 1 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 relative group hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 font-extrabold text-xl flex items-center justify-center mb-4">
                    1
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Pasang Penawaran Sisa</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    UMKM yang memiliki sisa potongan kain, kertas, kayu palet, atau plastik cukup memotret dan mengisi form detail kondisi serta lokasi di Jakarta.
                </p>
            </div>

            <!-- Langkah 2 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 relative group hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 font-extrabold text-xl flex items-center justify-center mb-4">
                    2
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Pencocokan Cerdas (Smart Match)</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Sistem mencocokkan kebutuhan material pengrajin dengan penyedia terdekat di wilayah Jakarta yang sama untuk menekan jejak karbon logistik.
                </p>
            </div>

            <!-- Langkah 3 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 relative group hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 font-extrabold text-xl flex items-center justify-center mb-4">
                    3
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Ambil & Produksi Berkelanjutan</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Pengrajin mengajukan pengambilan, mengonfirmasi kontak WhatsApp UMKM penyedia, dan mengolah material sisa menjadi produk bernilai tinggi.
                </p>
            </div>
        </div>
    </section>

    <!-- PREVIEW SECTION: LATEST AVAILABLE MATERIALS -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 pb-4 border-b border-slate-200 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    Katalog Langsung
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 tracking-tight">
                    Material Sisa Tersedia Hari Ini
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Bahan berkualitas tinggi yang siap dimanfaatkan oleh UMKM kerajinan & manufaktur.
                </p>
            </div>
            <a href="katalog.php" class="inline-flex items-center text-sm font-bold text-emerald-600 hover:text-emerald-700 group">
                Lihat Semua di Katalog
                <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </a>
        </div>

        <?php if (empty($latestMaterials)): ?>
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-200 p-8">
                <div class="text-4xl mb-3">📦</div>
                <h3 class="text-lg font-bold text-slate-700">Belum Ada Material Tersedia</h3>
                <p class="text-sm text-slate-500 mt-1">Jadilah UMKM pertama yang menawarkan sisa material produksi Anda.</p>
                <a href="penawaran.php" class="mt-4 inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700">
                    + Pasang Penawaran Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($latestMaterials as $mat): 
                    $catMeta = getCategoryBadge($mat['kategori']);
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-emerald-300 transition-all duration-200 flex flex-col">
                        <!-- Top Header Card -->
                        <div class="p-5 flex-1">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $catMeta['bg'] ?> <?= $catMeta['text'] ?> border <?= $catMeta['border'] ?>">
                                    <span class="mr-1"><?= $catMeta['icon'] ?></span>
                                    <?= htmlspecialchars($mat['kategori']) ?>
                                </span>
                                <div class="flex items-center space-x-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= ($mat['kelas_bahan'] ?? 'Standar') === 'Premium' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-200' ?> uppercase">
                                        <?= htmlspecialchars($mat['kelas_bahan'] ?? 'Standar') ?>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                        Tersedia
                                    </span>
                                </div>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-600 line-clamp-1 mb-1">
                                <?= htmlspecialchars($mat['nama_bahan']) ?>
                            </h3>

                            <div class="flex items-baseline justify-between my-2">
                                <div class="flex items-baseline space-x-1.5">
                                    <span class="text-2xl font-black text-emerald-600">
                                        <?= (float)$mat['jumlah'] == (int)$mat['jumlah'] ? (int)$mat['jumlah'] : number_format($mat['jumlah'], 1) ?>
                                    </span>
                                    <span class="text-sm font-bold text-slate-600 uppercase">
                                        <?= htmlspecialchars($mat['satuan']) ?>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 block font-medium">Harga Penawaran:</span>
                                    <span class="text-sm font-extrabold text-slate-800">Rp <?= number_format($mat['harga_per_satuan'] ?? 50000, 0, ',', '.') ?></span>
                                    <span class="text-[10px] text-slate-500 font-semibold uppercase">/<?= htmlspecialchars($mat['satuan_jual'] ?? 'kg') ?></span>
                                </div>
                            </div>

                            <!-- B2B Specs Badges -->
                            <div class="flex flex-wrap gap-1.5 mb-2.5">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold border border-slate-200" title="Ukuran Pecahan">
                                    📏 <?= htmlspecialchars($mat['ukuran_pecahan'] ?? '0.5m - 3m') ?>
                                </span>
                                <span class="px-2 py-0.5 rounded bg-teal-50 text-teal-800 text-[10px] font-semibold border border-teal-200" title="Satuan Jual">
                                    📦 Jual: <?= ($mat['satuan_jual'] ?? '') === 'borongan' ? 'Borongan' : 'Per Kg' ?>
                                </span>
                                <?php if (!empty($mat['jenis_motif'])): ?>
                                    <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-800 text-[10px] font-semibold border border-purple-200 truncate max-w-[170px]" title="Motif / Corak">
                                        🎨 <?= htmlspecialchars($mat['jenis_motif']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-xs text-slate-500 line-clamp-2 mb-3">
                                <?= htmlspecialchars($mat['deskripsi'] ?: 'Kondisi: ' . $mat['kondisi']) ?>
                            </p>

                            <!-- Meta Details -->
                            <div class="space-y-1.5 text-xs text-slate-600 pt-3 border-t border-slate-100">
                                <div class="flex items-center">
                                    <svg class="w-3.5 h-3.5 text-slate-400 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="font-medium text-slate-700"><?= htmlspecialchars($mat['wilayah']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-3.5 h-3.5 text-slate-400 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="truncate"><?= htmlspecialchars($mat['nama_usaha']) ?> (<?= htmlspecialchars($mat['sektor'] ?? 'UMKM') ?>)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">
                                <?= date('d M Y', strtotime($mat['created_at'])) ?>
                            </span>
                            <a href="katalog.php?detail=<?= $mat['id'] ?>#mat-<?= $mat['id'] ?>" 
                               class="inline-flex items-center text-xs font-bold text-emerald-700 bg-white hover:bg-emerald-50 px-3 py-1.5 rounded-lg border border-slate-200 hover:border-emerald-300 shadow-xs transition">
                                Ajukan Pengambilan
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- FOOTER -->
    <footer class="mt-auto bg-slate-900 text-slate-400 text-sm border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold">
                            ♻️
                        </div>
                        <span class="text-xl font-black text-white">Re<span class="text-emerald-400">Bahan</span></span>
                    </div>
                    <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                        Platform Digital Kolaborasi Ekonomi Sirkular untuk Pemanfaatan Material Sisa dan Pemenuhan Kebutuhan Bahan Baku UMKM di 5 Wilayah DKI Jakarta.
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3">Navigasi Fitur</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="index.php" class="hover:text-emerald-400 transition">Dashboard & Impact</a></li>
                        <li><a href="katalog.php" class="hover:text-emerald-400 transition">Katalog Material Sisa</a></li>
                        <li><a href="penawaran.php" class="hover:text-emerald-400 transition">Pasang Penawaran Bahan</a></li>
                        <li><a href="matching.php" class="hover:text-emerald-400 transition">Smart Matching & Transaksi</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3">Wilayah Jangkauan</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Jakarta Selatan, Jakarta Pusat, Jakarta Barat, Jakarta Timur, dan Jakarta Utara.
                    </p>
                    <div class="mt-3 inline-flex items-center text-[11px] text-emerald-400 font-semibold bg-emerald-950/60 border border-emerald-800 px-2.5 py-1 rounded-md">
                        🌱 Menuju Jakarta Zero Waste 2030
                    </div>
                </div>
            </div>
            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> ReBahan. Dilindungi oleh Inisiatif Ekonomi Sirkular UMKM.</p>
                <p class="mt-2 sm:mt-0">Ditenagai oleh PHP 8.3 & MariaDB</p>
            </div>
        </div>
    </footer>

</body>
</html>
