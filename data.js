/**
 * data.js - State Management & Static Data Store untuk ReBahan (Client-side LocalStorage)
 */

const DEFAULT_USERS = [
  { id: 1, nama_usaha: 'Penjahit Berkah Busana', sektor: 'Tekstil', wilayah: 'Jakarta Selatan', kontak: '0812-3456-7890' },
  { id: 2, nama_usaha: 'Percetakan Salemba Grafika', sektor: 'Percetakan', wilayah: 'Jakarta Pusat', kontak: '0813-8899-1122' },
  { id: 3, nama_usaha: 'Workshop Kayu Lestari Palet', sektor: 'Kerajinan', wilayah: 'Jakarta Selatan', kontak: '0815-4455-6677' },
  { id: 4, nama_usaha: 'Kreasi Tas Daur Ulang Johar', sektor: 'Kerajinan', wilayah: 'Jakarta Pusat', kontak: '0818-9900-2233' },
  { id: 5, nama_usaha: 'Studio Sablon & Konveksi Palmerah', sektor: 'Tekstil', wilayah: 'Jakarta Barat', kontak: '0819-1234-5678' },
  { id: 6, nama_usaha: 'Mitra Penggilingan Plastik Cengkareng', sektor: 'Manufaktur', wilayah: 'Jakarta Barat', kontak: '0812-7788-9900' },
  { id: 7, nama_usaha: 'Gudang Logistik & Ekspedisi Cakung', sektor: 'Logistik', wilayah: 'Jakarta Timur', kontak: '0813-5566-7788' },
  { id: 8, nama_usaha: 'Pengrajin Mebel Bambu Kelapa Gading', sektor: 'Kerajinan', wilayah: 'Jakarta Utara', kontak: '0817-2233-4455' },
  { id: 9, nama_usaha: 'Studio Kriya Kain Perca Tebet', sektor: 'Kerajinan', wilayah: 'Jakarta Selatan', kontak: '0812-9988-7766' }
];

const DEFAULT_MATERIALS = [
  {
    id: 1,
    user_id: 1,
    nama_bahan: 'Sisa Kain Perca Katun Rayon Corak & Polos',
    kategori: 'Kain/Tekstil',
    jumlah: 35.5,
    satuan: 'kg',
    harga_per_satuan: 60000,
    satuan_jual: 'kg',
    ukuran_pecahan: '0.5m - 3m',
    kelas_bahan: 'Premium',
    jenis_motif: 'Rayon Corak Bunga & Polos',
    kondisi: 'Bersih, Kering, Potongan baru sisa jahitan dress',
    wilayah: 'Jakarta Selatan',
    deskripsi: 'Sangat cocok untuk produk kerajinan scrunchie, sarung bantal patchwork, masker kain, atau tote bag (estimasi ~6 meter/kg).',
    status: 'available',
    created_at: '2026-10-28 09:30:00'
  },
  {
    id: 2,
    user_id: 2,
    nama_bahan: 'Potongan Kertas Art Paper 260gsm & Ivory',
    kategori: 'Kertas/Karton',
    jumlah: 50.0,
    satuan: 'kg',
    harga_per_satuan: 25000,
    satuan_jual: 'kg',
    ukuran_pecahan: 'Lembaran A3 - A2',
    kelas_bahan: 'Premium',
    jenis_motif: 'Putih Bersih Glossy 260gsm',
    kondisi: 'Potongan presisi, bersih & rapi',
    wilayah: 'Jakarta Pusat',
    deskripsi: 'Sisa pemotongan cetak cover buku dan kalender meja tahunan. Cocok untuk hangtag pakaian atau notebook daur ulang.',
    status: 'available',
    created_at: '2026-10-28 10:15:00'
  },
  {
    id: 3,
    user_id: 3,
    nama_bahan: 'Sisa Kayu Palet Pinus & Jati Belanda Ex-Kargo',
    kategori: 'Kayu/Bambu',
    jumlah: 40.0,
    satuan: 'pcs',
    harga_per_satuan: 35000,
    satuan_jual: 'borongan',
    ukuran_pecahan: 'Balok & Papan 100cm - 120cm',
    kelas_bahan: 'Standar',
    jenis_motif: 'Serat Kayu Pinus Alami',
    kondisi: 'Kayu utuh, kering oven, bebas paku berlebih',
    wilayah: 'Jakarta Selatan',
    deskripsi: 'Papan palet kayu sisa unboxing kargo industri Mampang. Sangat bagus untuk rak hidroponik mini, ambalan dinding, atau meja cafe rustic.',
    status: 'available',
    created_at: '2026-10-28 11:00:00'
  },
  {
    id: 4,
    user_id: 5,
    nama_bahan: 'Sisa Gulungan Kain Kanvas Twill & Denim',
    kategori: 'Kain/Tekstil',
    jumlah: 22.0,
    satuan: 'meter',
    harga_per_satuan: 55000,
    satuan_jual: 'kg',
    ukuran_pecahan: '1m - 3.5m',
    kelas_bahan: 'Premium',
    jenis_motif: 'Twill Navy & Denim Indigo',
    kondisi: 'Bersih potongan rol ujung (lebar 40-60 cm)',
    wilayah: 'Jakarta Barat',
    deskripsi: 'Material kuat dan kokoh untuk aksesoris fashion, dompet koin, atau tatakan gelas ramah lingkungan.',
    status: 'available',
    created_at: '2026-10-28 13:45:00'
  },
  {
    id: 5,
    user_id: 6,
    nama_bahan: 'Cacahan Plastik PP & HDPE Terpilah Bersih',
    kategori: 'Plastik',
    jumlah: 75.0,
    satuan: 'kg',
    harga_per_satuan: 18000,
    satuan_jual: 'kg',
    ukuran_pecahan: 'Flakes / Serpihan 5mm',
    kelas_bahan: 'Standar',
    jenis_motif: 'Polimer Terpilah Biru & Putih',
    kondisi: 'Sudah dicuci, dipotong kecil 5mm, warna seragam',
    wilayah: 'Jakarta Barat',
    deskripsi: 'Hasil olahan pemilahan wadah botol susu dan tutup galon. Siap untuk proses injeksi moulding pot tanaman atau gantungan kunci.',
    status: 'available',
    created_at: '2026-10-28 15:20:00'
  },
  {
    id: 6,
    user_id: 7,
    nama_bahan: 'Karton Box Gelombang (Corrugated Box) Tebal Double Wall',
    kategori: 'Kertas/Karton',
    jumlah: 65.0,
    satuan: 'kg',
    harga_per_satuan: 12000,
    satuan_jual: 'kg',
    ukuran_pecahan: 'Lembaran Utuh 60x80cm',
    kelas_bahan: 'Standar',
    jenis_motif: 'Cokelat Polos Double Wall',
    kondisi: 'Sudah dipipihkan, bersih dari minyak, kering',
    wilayah: 'Jakarta Timur',
    deskripsi: 'Bahan kemasan sisa impor logistik pergudangan. Dapat dipakai kembali langsung untuk packaging produk UMKM lokal.',
    status: 'available',
    created_at: '2026-10-29 08:10:00'
  },
  {
    id: 7,
    user_id: 8,
    nama_bahan: 'Potongan Batang Bambu Petung & Tali Ijuk',
    kategori: 'Kayu/Bambu',
    jumlah: 18.0,
    satuan: 'pcs',
    harga_per_satuan: 20000,
    satuan_jual: 'borongan',
    ukuran_pecahan: 'Batang 80cm - 120cm',
    kelas_bahan: 'Premium',
    jenis_motif: 'Bambu Alami Awet',
    kondisi: 'Panjang 80-120 cm, diawetkan anti-rayap',
    wilayah: 'Jakarta Utara',
    deskripsi: 'Sisa pengerjaan gazebo dan mebel bambu pesanan taman kota. Cocok untuk kerajinan wadah aromaterapi, vas bunga, atau lampion.',
    status: 'available',
    created_at: '2026-10-29 09:00:00'
  },
  {
    id: 8,
    user_id: 1,
    nama_bahan: 'Sisa Kain Spunbond Non-Woven Putih & Krem',
    kategori: 'Kain/Tekstil',
    jumlah: 15.0,
    satuan: 'kg',
    harga_per_satuan: 25000,
    satuan_jual: 'kg',
    ukuran_pecahan: '0.8m - 2m',
    kelas_bahan: 'Standar',
    jenis_motif: 'Putih & Krem Polos',
    kondisi: 'Kondisi baru lembaran bersih',
    wilayah: 'Jakarta Selatan',
    deskripsi: 'Material sisa pembuatan kantong belanja ramah lingkungan pesanan komunitas. Bagus untuk lapisan dalam tas atau pouch.',
    status: 'completed',
    created_at: '2026-10-26 14:00:00'
  }
];

const DEFAULT_TRANSACTIONS = [
  {
    id: 1,
    material_id: 1,
    receiver_id: 4,
    alasan_pengambilan: 'Kain perca akan dimanfaatkan untuk kerajinan pouch belanja pameran UMKM Jakarta Kreatif.',
    reason_notes: 'Kain perca akan dimanfaatkan untuk kerajinan pouch belanja pameran UMKM Jakarta Kreatif.',
    catatan: 'Kain perca akan dimanfaatkan untuk kerajinan pouch belanja pameran UMKM Jakarta Kreatif.',
    status: 'Disetujui',
    created_at: '2026-10-28 14:00:00'
  },
  {
    id: 2,
    material_id: 2,
    receiver_id: 9,
    alasan_pengambilan: 'Dibutuhkan untuk hangtag dan label sertifikat produk ramah lingkungan.',
    reason_notes: 'Dibutuhkan untuk hangtag dan label sertifikat produk ramah lingkungan.',
    catatan: 'Dibutuhkan untuk hangtag dan label sertifikat produk ramah lingkungan.',
    status: 'Pending',
    created_at: '2026-10-28 16:30:00'
  },
  {
    id: 3,
    material_id: 3,
    receiver_id: 4,
    alasan_pengambilan: 'Untuk display booth pameran UMKM sirkular di Senayan.',
    reason_notes: 'Untuk display booth pameran UMKM sirkular di Senayan.',
    catatan: 'Untuk display booth pameran UMKM sirkular di Senayan.',
    status: 'Dalam Pengambilan',
    created_at: '2026-10-29 09:15:00'
  },
  {
    id: 4,
    material_id: 8,
    receiver_id: 9,
    alasan_pengambilan: 'Selesai diambil pada tanggal 26 Oktober 2026. Bahan telah diolah menjadi 50 kantong reusable.',
    reason_notes: 'Selesai diambil pada tanggal 26 Oktober 2026. Bahan telah diolah menjadi 50 kantong reusable.',
    catatan: 'Selesai diambil pada tanggal 26 Oktober 2026. Bahan telah diolah menjadi 50 kantong reusable.',
    status: 'Selesai',
    created_at: '2026-10-26 16:00:00'
  }
];

// Inisialisasi Data Store di LocalStorage
function initDataStore() {
  if (!localStorage.getItem('rebahan_users')) {
    localStorage.setItem('rebahan_users', JSON.stringify(DEFAULT_USERS));
  }
  if (!localStorage.getItem('rebahan_materials')) {
    localStorage.setItem('rebahan_materials', JSON.stringify(DEFAULT_MATERIALS));
  } else {
    // Migrasi data material jika atribut B2B marketplace belum ada
    try {
      const stored = JSON.parse(localStorage.getItem('rebahan_materials')) || [];
      let updated = false;
      stored.forEach(m => {
        if (m.harga_per_satuan === undefined || m.satuan_jual === undefined) {
          const matchDefault = DEFAULT_MATERIALS.find(dm => dm.id === m.id);
          m.harga_per_satuan = matchDefault ? matchDefault.harga_per_satuan : 50000;
          m.satuan_jual = matchDefault ? matchDefault.satuan_jual : (m.satuan === 'pcs' ? 'borongan' : 'kg');
          m.ukuran_pecahan = matchDefault ? matchDefault.ukuran_pecahan : '0.5m - 3m';
          m.kelas_bahan = matchDefault ? matchDefault.kelas_bahan : 'Standar';
          m.jenis_motif = matchDefault ? matchDefault.jenis_motif : 'Polos / Corak';
          updated = true;
        }
      });
      if (updated) {
        localStorage.setItem('rebahan_materials', JSON.stringify(stored));
      }
    } catch (e) {}
  }
  if (!localStorage.getItem('rebahan_transactions')) {
    localStorage.setItem('rebahan_transactions', JSON.stringify(DEFAULT_TRANSACTIONS));
  } else {
    // Migrasi data transaksi lawas jika belum memiliki alasan_pengambilan / reason_notes
    try {
      const stored = JSON.parse(localStorage.getItem('rebahan_transactions')) || [];
      let updated = false;
      stored.forEach(t => {
        const text = t.alasan_pengambilan || t.reason_notes || t.catatan || '';
        if (!t.alasan_pengambilan || !t.reason_notes) {
          t.alasan_pengambilan = text;
          t.reason_notes = text;
          t.catatan = text;
          updated = true;
        }
      });
      if (updated) {
        localStorage.setItem('rebahan_transactions', JSON.stringify(stored));
      }
    } catch (e) {}
  }
}

// Getters
function getUsers() {
  initDataStore();
  return JSON.parse(localStorage.getItem('rebahan_users')) || [];
}

function getMaterials() {
  initDataStore();
  const users = getUsers();
  const materials = JSON.parse(localStorage.getItem('rebahan_materials')) || [];
  return materials.map(m => {
    const user = users.find(u => u.id === m.user_id) || { nama_usaha: 'UMKM Jakarta', kontak: '0812-0000-0000', sektor: 'Kerajinan' };
    return {
      ...m,
      harga_per_satuan: parseFloat(m.harga_per_satuan) || 50000,
      satuan_jual: m.satuan_jual || 'kg',
      ukuran_pecahan: m.ukuran_pecahan || '0.5m - 3m',
      kelas_bahan: m.kelas_bahan || 'Standar',
      jenis_motif: m.jenis_motif || 'Polos / Corak',
      nama_usaha: user.nama_usaha,
      kontak: user.kontak,
      sektor: user.sektor
    };
  });
}

function getTransactions() {
  initDataStore();
  const materials = getMaterials();
  const users = getUsers();
  const transactions = JSON.parse(localStorage.getItem('rebahan_transactions')) || [];

  return transactions.map(t => {
    const material = materials.find(m => m.id === t.material_id) || {
      nama_bahan: 'Material Dihapus',
      jumlah: 0,
      satuan: 'kg',
      kategori: 'Lainnya',
      wilayah: 'Jakarta',
      nama_usaha: 'Penyedia',
      kontak: '08xx'
    };
    const receiver = users.find(u => u.id === t.receiver_id) || {
      nama_usaha: 'Pemohon UMKM',
      wilayah: 'Jakarta',
      kontak: '08xx'
    };
    const reason = t.alasan_pengambilan || t.reason_notes || t.catatan || '';
    return {
      ...t,
      alasan_pengambilan: reason,
      reason_notes: reason,
      catatan: reason,
      nama_bahan: material.nama_bahan,
      jumlah: material.jumlah,
      satuan: material.satuan,
      kategori: material.kategori,
      material_wilayah: material.wilayah,
      penyedia_nama: material.nama_usaha,
      penyedia_kontak: material.kontak,
      pengambil_nama: receiver.nama_usaha,
      pengambil_wilayah: receiver.wilayah,
      pengambil_kontak: receiver.kontak
    };
  });
}

// Tambah UMKM Baru
function addUser(userData) {
  const users = getUsers();
  const newId = users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1;
  const newUser = {
    id: newId,
    nama_usaha: userData.nama_usaha,
    sektor: userData.sektor || 'Kerajinan',
    wilayah: userData.wilayah || 'Jakarta Selatan',
    kontak: userData.kontak || '08xx'
  };
  users.push(newUser);
  localStorage.setItem('rebahan_users', JSON.stringify(users));
  return newUser;
}

// Tambah Material Baru
function addMaterial(matData) {
  const materials = JSON.parse(localStorage.getItem('rebahan_materials')) || [];
  const users = getUsers();

  let user = users.find(u => u.nama_usaha.toLowerCase().trim() === matData.nama_usaha.toLowerCase().trim());
  if (!user) {
    user = addUser({
      nama_usaha: matData.nama_usaha,
      sektor: matData.sektor || 'Tekstil',
      wilayah: matData.wilayah,
      kontak: matData.kontak
    });
  } else {
    // Update contact / wilayah
    user.kontak = matData.kontak;
    user.wilayah = matData.wilayah;
    localStorage.setItem('rebahan_users', JSON.stringify(users));
  }

  const newId = materials.length > 0 ? Math.max(...materials.map(m => m.id)) + 1 : 1;
  const now = new Date().toISOString().replace('T', ' ').substring(0, 19);

  const newMaterial = {
    id: newId,
    user_id: user.id,
    nama_bahan: matData.nama_bahan,
    kategori: matData.kategori,
    jumlah: parseFloat(matData.jumlah),
    satuan: matData.satuan,
    harga_per_satuan: parseFloat(matData.harga_per_satuan) || 0,
    satuan_jual: matData.satuan_jual || 'kg',
    ukuran_pecahan: matData.ukuran_pecahan || '0.5m - 3m',
    kelas_bahan: matData.kelas_bahan || 'Standar',
    jenis_motif: matData.jenis_motif || 'Polos',
    kondisi: matData.kondisi,
    wilayah: matData.wilayah,
    deskripsi: matData.deskripsi || '',
    status: 'available',
    created_at: now
  };

  materials.unshift(newMaterial);
  localStorage.setItem('rebahan_materials', JSON.stringify(materials));
  return newMaterial;
}

// Buat Transaksi Baru
function createTransaction(materialId, receiverId, alasanPengambilan, jumlahAmbil = null, estimasiBiaya = null) {
  const transactions = JSON.parse(localStorage.getItem('rebahan_transactions')) || [];
  const newId = transactions.length > 0 ? Math.max(...transactions.map(t => t.id)) + 1 : 1;
  const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
  const text = alasanPengambilan || '';

  const newTrx = {
    id: newId,
    material_id: parseInt(materialId),
    receiver_id: parseInt(receiverId),
    jumlah_ambil: jumlahAmbil !== null ? parseFloat(jumlahAmbil) : null,
    estimasi_biaya: estimasiBiaya !== null ? parseFloat(estimasiBiaya) : null,
    alasan_pengambilan: text,
    reason_notes: text,
    catatan: text,
    status: 'Pending',
    created_at: now
  };

  transactions.unshift(newTrx);
  localStorage.setItem('rebahan_transactions', JSON.stringify(transactions));
  return newTrx;
}

// Update Status Transaksi
function updateTransactionStatus(trxId, newStatus) {
  const transactions = JSON.parse(localStorage.getItem('rebahan_transactions')) || [];
  const materials = JSON.parse(localStorage.getItem('rebahan_materials')) || [];

  const trx = transactions.find(t => t.id === parseInt(trxId));
  if (trx) {
    trx.status = newStatus;
    localStorage.setItem('rebahan_transactions', JSON.stringify(transactions));

    const mat = materials.find(m => m.id === trx.material_id);
    if (mat) {
      if (newStatus === 'Selesai') {
        mat.status = 'completed';
      } else if (newStatus === 'Disetujui' || newStatus === 'Dalam Pengambilan') {
        mat.status = 'booked';
      }
      localStorage.setItem('rebahan_materials', JSON.stringify(materials));
    }
    return true;
  }
  return false;
}

// Perhitungan Statistik Impact
function getImpactStats() {
  const materials = JSON.parse(localStorage.getItem('rebahan_materials')) || [];
  const users = getUsers();
  const transactions = JSON.parse(localStorage.getItem('rebahan_transactions')) || [];

  let totalKg = 0;
  materials.forEach(m => {
    if (m.status === 'completed') {
      let weight = m.jumlah;
      if (m.satuan === 'meter') weight *= 0.35;
      else if (m.satuan === 'pcs') weight *= 1.5;
      totalKg += weight;
    }
  });

  const totalUmkm = users.length;
  const totalTrx = transactions.filter(t => ['Selesai', 'Disetujui', 'Dalam Pengambilan'].includes(t.status)).length;

  return {
    totalKg: totalKg.toFixed(1),
    totalUmkm,
    totalTrx
  };
}

// Metadata Kategori
function getCategoryMeta(kategori) {
  switch (kategori) {
    case 'Kain/Tekstil':
      return { bg: 'bg-pink-50', text: 'text-pink-700', border: 'border-pink-200', icon: '🧵' };
    case 'Kertas/Karton':
      return { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', icon: '📦' };
    case 'Plastik':
      return { bg: 'bg-cyan-50', text: 'text-cyan-700', border: 'border-cyan-200', icon: '♻️' };
    case 'Kayu/Bambu':
      return { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', icon: '🪵' };
    default:
      return { bg: 'bg-slate-50', text: 'text-slate-700', border: 'border-slate-200', icon: '✨' };
  }
}

// Metadata Status Transaksi
function getTrxStatusMeta(status) {
  switch (status) {
    case 'Pending':
      return { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' };
    case 'Disetujui':
      return { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' };
    case 'Dalam Pengambilan':
      return { bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-200', dot: 'bg-purple-500' };
    case 'Selesai':
      return { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' };
    default:
      return { bg: 'bg-slate-50', text: 'text-slate-700', border: 'border-slate-200', dot: 'bg-slate-400' };
  }
}

// Inisialisasi awal saat script dimuat
initDataStore();

