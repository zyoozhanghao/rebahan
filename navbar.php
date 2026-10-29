<?php
// rebahan/website/public/navbar.php
// Komponen Navigasi Utama ReBahan (Responsive & Modern)

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');

$navLinks = [
    [
        'label' => 'Dashboard & Impact',
        'href'  => 'index.php',
        'icon'  => '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>'
    ],
    [
        'label' => 'Katalog Material',
        'href'  => 'katalog.php',
        'icon'  => '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>'
    ],
    [
        'label' => 'Pasang Penawaran',
        'href'  => 'penawaran.php',
        'icon'  => '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    ],
    [
        'label' => 'Smart Matching',
        'href'  => 'matching.php',
        'icon'  => '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>'
    ],
];
?>

<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-emerald-100 shadow-sm transition-all duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Brand Logo & Tagline -->
            <div class="flex items-center space-x-3">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform duration-200">
                        <!-- Recycle / Circular Economy Leaf SVG -->
                        <svg class="w-6 h-6 animate-[spin_10s_linear_infinite]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl font-black tracking-tight text-slate-800">
                                Re<span class="text-emerald-600">Bahan</span>
                            </span>
                            <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold tracking-wide bg-emerald-50 text-emerald-700 border border-emerald-200">
                                SIRKULAR UMKM
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 hidden sm:block">Pemanfaatan Material Sisa Jakarta</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                <?php foreach ($navLinks as $nav): 
                    $isActive = ($currentPage === $nav['href']);
                ?>
                    <a href="<?= $nav['href'] ?>" 
                       class="flex items-center px-3.5 py-2 rounded-xl text-sm font-medium transition-all duration-150 <?= $isActive 
                            ? 'bg-emerald-50 text-emerald-700 font-semibold shadow-xs' 
                            : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <?= $nav['icon'] ?>
                        <?= htmlspecialchars($nav['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Action CTA Button -->
            <div class="hidden md:flex items-center space-x-3">
                <a href="penawaran.php" 
                   class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm shadow-emerald-600/30 transition-all duration-150 transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Pasang Bahan
                </a>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center md:hidden">
                <button type="button" id="mobile-menu-btn" 
                        class="p-2 rounded-xl text-slate-600 hover:text-emerald-600 hover:bg-slate-100 focus:outline-none"
                        aria-label="Toggle menu">
                    <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-slate-100 bg-white/95 px-4 pt-3 pb-5 space-y-2">
        <?php foreach ($navLinks as $nav): 
            $isActive = ($currentPage === $nav['href']);
        ?>
            <a href="<?= $nav['href'] ?>" 
               class="flex items-center px-3 py-2.5 rounded-xl text-base font-medium <?= $isActive 
                    ? 'bg-emerald-50 text-emerald-700 font-semibold' 
                    : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?>">
                <?= $nav['icon'] ?>
                <?= htmlspecialchars($nav['label']) ?>
            </a>
        <?php endforeach; ?>
        <div class="pt-2 border-t border-slate-100">
            <a href="penawaran.php" 
               class="w-full flex items-center justify-center px-4 py-2.5 rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Pasang Material Sisa
            </a>
        </div>
    </div>
</nav>

<script>
    // Toggle mobile menu navigation
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const hamburgerIcon = document.getElementById('hamburger-icon');
    const closeIcon = document.getElementById('close-icon');

    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', () => {
            const isExpanded = !mobileMenu.classList.contains('hidden');
            if (isExpanded) {
                mobileMenu.classList.add('hidden');
                hamburgerIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
            } else {
                mobileMenu.classList.remove('hidden');
                hamburgerIcon.classList.add('hidden');
                closeIcon.classList.remove('hidden');
            }
        });
    }
</script>

