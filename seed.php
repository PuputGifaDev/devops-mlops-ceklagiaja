<?php
/**
 * seed.php - Setup and populate complete realistic database
 */
require_once __DIR__ . '/config/app.php';

echo "=== MEMULAI RESET & SEEDING DATABASE ===" . PHP_EOL;

// 1. Matikan Foreign Key Checks sementara
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// Drop existing tables
$tables = [
    'activity_logs', 'finding_photos', 'follow_ups', 'inspection_findings',
    'inspection_details', 'inspection_reviews', 'inspections',
    'checklist_items', 'inspection_categories', 'buildings', 'users', 'inspeksi'
];

foreach ($tables as $tbl) {
    $pdo->exec("DROP TABLE IF EXISTS `$tbl`");
    echo "Tabel $tbl dihapus (jika ada)." . PHP_EOL;
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// 2. Baca inspeksi_bangunan.sql dan jalankan pembuatan tabel
$sqlFile = __DIR__ . '/inspeksi_bangunan.sql';
if (!file_exists($sqlFile)) {
    die("File inspeksi_bangunan.sql tidak ditemukan!");
}

$sqlContent = file_get_contents($sqlFile);
// Jalankan skema
$pdo->exec($sqlContent);
echo "Skema tabel dari inspeksi_bangunan.sql berhasil dibuat!" . PHP_EOL;

// 3. SEED USERS
$passAdmin = password_hash('Admin123!', PASSWORD_DEFAULT);
$passUser  = password_hash('User123!', PASSWORD_DEFAULT);

$usersData = [
    ['Administrator', 'admin', 'admin@inspeksi.local', $passAdmin, 'admin', '081234567890'],
    ['Dwi Gifa Sefani Putri', 'puput', 'puput@inspeksi.local', $passUser, 'user', '081234567891'],
    ['Layra Amarsha', 'layra', 'layra@inspeksi.local', $passUser, 'user', '081234567892'],
    ['Agung Rifandi', 'agung', 'agung@inspeksi.local', $passUser, 'user', '081234567893'],
    ['Memasya Elsha Julidda', 'elsha', 'elsha@inspeksi.local', $passUser, 'user', '081234567894'],
    ['Budi Santoso, S.T.', 'budi', 'budi@inspeksi.local', $passUser, 'user', '081234567895']
];

$stmtUser = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, role, phone, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
foreach ($usersData as $u) {
    $stmtUser->execute($u);
}
echo "Users berhasil di-seed (" . count($usersData) . " users)." . PHP_EOL;

// 4. SEED BUILDINGS
$buildingsData = [
    [
        'GD-RKT01', 'Gedung Rektorat & Administrasi Pusat', 'Perkantoran', 
        'Jl. Tri Lomba Juang No. 1, Mugassari, Semarang Selatan', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Selatan', 'Mugassari', '50249',
        -6.9928000, 110.4215000, 5, 2018, 4500.00, 
        'Gedung perkantoran rektorat dan administrasi berlantai 5 dengan konstruksi beton bertulang modern.', 'active', 1
    ],
    [
        'GD-RSM02', 'RSUD Graha Medika Bangunan Paviliun A', 'Rumah Sakit', 
        'Jl. Pandanaran No. 45, Pekunden, Semarang Tengah', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Tengah', 'Pekunden', '50134',
        -6.9855000, 110.4150000, 4, 2015, 6200.50, 
        'Paviliun rawat inap dan poliklinik kelas 1 dengan standar keselamatan medis aktif.', 'active', 1
    ],
    [
        'GD-PER03', 'Perpustakaan Daerah & Arsip Kota', 'Fasilitas Umum', 
        'Jl. Pemuda No. 120, Sekayu, Semarang Tengah', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Tengah', 'Sekayu', '50132',
        -6.9782000, 110.4132000, 3, 2012, 3100.00, 
        'Pusat literasi publik dan arsip digital kota Semarang dengan ruang baca luas dan auditorium.', 'active', 1
    ],
    [
        'GD-SMK04', 'SMK Negeri 1 Rekayasa & Teknologi', 'Sekolah', 
        'Jl. Pahlawan No. 88, Pleburan, Semarang Selatan', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Selatan', 'Pleburan', '50241',
        -6.9945000, 110.4228000, 3, 2010, 5800.00, 
        'Gedung sekolah kejuruan bertingkat 3 yang mencakup 24 ruang kelas teori dan 6 laboratorium kejuruan.', 'active', 1
    ],
    [
        'GD-BAP05', 'Kantor Perencanaan Bappeda Wilayah I', 'Pemerintahan', 
        'Jl. Madukoro Raya No. 10, Krobokan, Semarang Barat', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Barat', 'Krobokan', '50141',
        -6.9691000, 110.3985000, 3, 2019, 2800.00, 
        'Kantor pelayanan dinas perencanaan pembangunan berstandar ramah disabilitas dan green building.', 'active', 1
    ],
    [
        'GD-GOR06', 'Gelanggang Olahraga & Pusat Komunitas Satria', 'Fasilitas Umum', 
        'Jl. Ki Mangunsarkoro No. 25, Karangkidul, Semarang Tengah', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Tengah', 'Karangkidul', '50241',
        -6.9890000, 110.4280000, 2, 2016, 7500.00, 
        'Arena olahraga indoor multifungsi dengan kapasitas 2.000 penonton dan sarana latihan atletik.', 'active', 1
    ],
    [
        'GD-MNR07', 'Menara Niaga Simpang Lima', 'Komersial', 
        'Jl. Ahmad Yani No. 15, Karangkidul, Semarang Tengah', 
        'Jawa Tengah', 'Kota Semarang', 'Semarang Tengah', 'Karangkidul', '50241',
        -6.9902000, 110.4255000, 8, 2021, 12000.00, 
        'Pusat pertokoan, perbankan dan co-working space berlantai 8 di kawasan segitiga emas komersial.', 'active', 1
    ],
    [
        'GD-ASR08', 'Asrama Mahasiswa Putri Asri', 'Hunian', 
        'Jl. Menoreh Tengah No. 4, Sampangan, Gajahmungkur', 
        'Jawa Tengah', 'Kota Semarang', 'Gajahmungkur', 'Sampangan', '50232',
        -7.0095000, 110.3950000, 4, 2017, 3400.00, 
        'Asrama hunian mahasiswa kapasitas 150 kamar dilengkapi dapur bersama dan sistem proteksi kebakaran.', 'active', 1
    ]
];

$stmtBld = $pdo->prepare("INSERT INTO buildings (code, name, building_type, address, province, city, district, village, postal_code, latitude, longitude, floors, construction_year, area_m2, description, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
foreach ($buildingsData as $b) {
    $stmtBld->execute($b);
}
echo "Buildings berhasil di-seed (" . count($buildingsData) . " buildings)." . PHP_EOL;

// 5. SEED CHECKLIST ITEMS (Pastikan lengkap)
$checklistData = [
    // 1: Struktur
    [1, 'Kondisi Kolom Beton', 'Periksa adanya retak struktural, beton keropos, atau tulangan baja terekspos.', 1, 1, 1],
    [1, 'Kondisi Balok Struktur', 'Periksa lendutan berlebih, keretakan geser, atau deformasi balok utama.', 1, 1, 2],
    [1, 'Struktur Pondasi & Sloof', 'Periksa penurunan tanah fondasi tidak merata atau keretakan sloof pengikat.', 1, 1, 3],
    
    // 2: Dinding
    [2, 'Retak pada Dinding Pasangan', 'Periksa retak diagonal, retak rambut, atau pemisahan plesteran dinding.', 1, 1, 1],
    [2, 'Kelembapan & Rembesan Dinding', 'Periksa pengelupasan cat, pertumbuhan lumut, atau rembesan pipa dinding.', 1, 1, 2],
    [2, 'Kondisi Kusen & Partisi', 'Periksa sambungan dinding dengan kusen dan stabilitas partisi gipsum.', 0, 1, 3],

    // 3: Atap
    [3, 'Kondisi Penutup & Rangka Atap', 'Periksa genteng pecah/bergeser, karat rangka baja ringan atau lapuk kayu.', 1, 1, 1],
    [3, 'Talang & Saluran Pembuangan Air Hujan', 'Periksa sumbatan sampah, kemiringan talang, dan kebocoran sambungan talang.', 1, 1, 2],
    [3, 'Indikasi Kebocoran Atap', 'Periksa rembesan air pada dak beton atau plafon di bawah bidang atap.', 1, 1, 3],

    // 4: Lantai
    [4, 'Kerataan & Retak Permukaan Lantai', 'Periksa keramik meledak (popping), retak nat, atau penurunan plat lantai.', 1, 1, 1],
    [4, 'Lapisan Kedap Air / Waterproofing', 'Periksa efektivitas waterproofing terutama di area basah seperti toilet.', 1, 1, 2],

    // 5: Plafon
    [5, 'Kondisi Rangka & Panel Plafon', 'Periksa lendutan rangka hollow/kayu, kelembapan gipsum, dan sambungan drop ceiling.', 1, 1, 1],
    [5, 'Noda Air & Risiko Keruntuhan Plafon', 'Periksa tanda kebocoran air aktif yang membebani panel plafon.', 1, 1, 2],

    // 6: Instalasi Listrik
    [6, 'Panel Distribusi Listrik (MDP/SDP)', 'Periksa kebersihan panel, penandaan MCB, grounding, dan kabel semrawut.', 1, 1, 1],
    [6, 'Kondisi Visual Stop Kontak & Saklar', 'Periksa tanda hangus/meleleh, penutup pecah, atau fiksasi longgar.', 1, 1, 2],
    [6, 'Instalasi Jalur Kabel & Tray', 'Periksa kabel tanpa konduit pelindung atau potensi gigitan hewan pengerat.', 0, 1, 3],

    // 7: Pintu dan Jendela
    [7, 'Fungsi Buka-Tutup & Engsel', 'Periksa kelancaran ayunan pintu, engsel macet, dan door closer otomatis.', 0, 1, 1],
    [7, 'Kaca & Pengunci (Kunci/Handle)', 'Periksa retak pada panel kaca serta kelayakan fungsi silinder kunci.', 1, 1, 2],

    // 8: Sanitasi & Plumbing
    [8, 'Saluran Air Bersih & Tekanan', 'Periksa kebocoran kran, sambungan pipa PVC/PPR, dan tekanan pompa transfer.', 1, 1, 1],
    [8, 'Saluran Pembuangan & Floor Drain', 'Periksa aliran air kotor, bau balik dari drainase, dan perangkap uap pipa.', 1, 1, 2],

    // 9: Keselamatan & Proteksi Kebakaran
    [9, 'Jalur Evakuasi & Rambu Darurat', 'Periksa kejelasan rambu EXIT menyala dan ketiadaan rintangan di koridor darurat.', 1, 1, 1],
    [9, 'Tabung APAR & Titik Hydrant', 'Periksa jarum tekanan APAR di zona hijau, segel utuh, dan akses valve hydrant.', 1, 1, 2],
    [9, 'Detektor Asap & Alarm Kebakaran', 'Periksa kebersihan smoke detector dan kesiapan indikator control panel alarm.', 0, 1, 3]
];

// Kosongkan checklist bawaan agar rapi
$pdo->exec("DELETE FROM checklist_items");
$stmtItem = $pdo->prepare("INSERT INTO checklist_items (category_id, item_name, description, requires_note, requires_photo, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
foreach ($checklistData as $item) {
    $stmtItem->execute($item);
}
echo "Checklist Items berhasil di-seed (" . count($checklistData) . " items)." . PHP_EOL;

// 6. SEED INSPECTIONS (Multi-status: Approved, Pending Review, In Progress, Revision, Scheduled)
$inspectionsData = [
    // 1: Approved - Rektorat
    [
        'INS-20261015-A101', 1, 2, 1, 'Inspeksi Triwulan Rutin Semester II', 'Rutin',
        '2026-10-15', '2026-10-15 09:00:00', '2026-10-15 14:30:00', '2026-10-16 10:00:00',
        'approved', 'Secara umum kondisi struktur bangunan Gedung Rektorat terpelihara sangat baik. Ditemukan beberapa catatan minor pada plafon lantai 3 dan kabel panel listrik.',
        'Laporan lengkap dan terdokumentasi dengan baik. Rekomendasi perbaikan plafon disetujui untuk dianggarkan pada pemeliharaan rutin bulan depan.'
    ],
    // 2: Approved - RSUD Graha Medika
    [
        'INS-20261018-B202', 2, 3, 1, 'Pemeriksaan Keselamatan & Proteksi Ruang Rawat', 'Audit Keselamatan',
        '2026-10-18', '2026-10-18 08:30:00', '2026-10-18 15:45:00', '2026-10-19 11:20:00',
        'approved', 'Fasilitas keselamatan ruangan rawat inap berfungsi normal. APAR bertekanan baik. Perlu perhatian pada rembesan dinding toilet ruang rawat 204.',
        'Disetujui. Tindak lanjut perbaikan plumbing pada toilet lantai 2 agar diprioritaskan oleh tim teknis medis.'
    ],
    // 3: Pending Review - SMK Negeri 1
    [
        'INS-20261025-C303', 4, 4, 1, 'Inspeksi Berkala Fasilitas Workshop & Lab', 'Berkala',
        '2026-10-25', '2026-10-25 10:00:00', '2026-10-25 16:00:00', NULL,
        'pending_review', 'Ditemukan retak struktural pada balok sayap timur laboratorium mesin, serta beberapa kabel instalasi panel daya utama tanpa pengaman pipa konduit.',
        NULL
    ],
    // 4: Revision - GOR Komunitas Satria
    [
        'INS-20261027-D404', 6, 5, 1, 'Pemeriksaan Kelayakan Struktur Atap & Fasilitas Umum', 'Kelaikan Fungsi',
        '2026-10-27', '2026-10-27 09:15:00', '2026-10-27 13:00:00', NULL,
        'revision', 'Rangka atap tribun barat menunjukkan korosi ringan akibat kebocoran talang air. Pintu darurat sisi selatan sedikit seret saat dibuka.',
        'Mohon lampirkan foto close-up detail karat pada sambungan baut rangka atap dan hasil uji bukaan pintu evakuasi.'
    ],
    // 5: In Progress - Menara Niaga Simpang Lima
    [
        'INS-20261029-E505', 7, 2, 1, 'Inspeksi Kesiapan Sistem Proteksi Gedung Tinggi', 'Audit Kelaikan',
        '2026-10-29', '2026-10-29 10:30:00', NULL, NULL,
        'in_progress', 'Sedang melakukan pengecekan sensor detektor asap dan hidran kebakaran pada lantai 5 hingga 8.',
        NULL
    ],
    // 6: Scheduled - Bappeda Wilayah I
    [
        'INS-20261102-F606', 5, 3, 1, 'Inspeksi Semesteran Gedung Pemerintahan', 'Rutin',
        '2026-11-02', NULL, NULL, NULL,
        'scheduled', 'Jadwal inspeksi komprehensif struktur, MEP, dan aksesibilitas ramah disabilitas.',
        NULL
    ],
    // 7: Scheduled - Asrama Mahasiswa Putri Asri
    [
        'INS-20261105-G707', 8, 4, 1, 'Pemeriksaan Rutin Pra-Semester Genap', 'Rutin',
        '2026-11-05', NULL, NULL, NULL,
        'scheduled', 'Pemeriksaan utilitas kamar, saluran air bersih, dan kesiapan jalur evakuasi kebakaran.',
        NULL
    ]
];

$stmtInsp = $pdo->prepare("INSERT INTO inspections (inspection_number, building_id, assigned_user_id, created_by, title, inspection_type, scheduled_date, started_at, submitted_at, approved_at, status, general_notes, review_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
foreach ($inspectionsData as $insp) {
    $stmtInsp->execute($insp);
}
echo "Inspections berhasil di-seed (" . count($inspectionsData) . " inspections)." . PHP_EOL;

// 7. SEED INSPECTION DETAILS (Checklist snapshot for inspections 1, 2, 3, 4, 5)
$allChecklistItems = $pdo->query("SELECT id FROM checklist_items ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

// Status presets for realistic demo
$detailsPresets = [
    1 => ['good' => [1,2,3,4,6,7,8,9,10,13,14,16,17,18,19,20,21], 'attention' => [5,11,15], 'damaged' => [12]], // Plafon noda air
    2 => ['good' => [1,2,3,4,6,7,8,9,10,11,12,13,14,16,17,19,20,21], 'attention' => [5,18], 'damaged' => [15]], // Stop kontak
    3 => ['good' => [3,4,6,7,8,9,10,11,12,16,17,18,19,20], 'attention' => [5,15], 'damaged' => [1,2,13,14]], // Retak kolom/balok & kabel MDP
    4 => ['good' => [1,2,3,4,9,10,11,12,14,15,18,20], 'attention' => [7,8,16], 'damaged' => [17,19]], // Pintu seret & talang
    5 => ['good' => [1,2,3,4,5,6,7,8,9,10], 'attention' => [11,12], 'damaged' => [], 'not_checked' => [13,14,15,16,17,18,19,20,21]]
];

$stmtDetailIns = $pdo->prepare("INSERT INTO inspection_details (inspection_id, checklist_item_id, condition_status, notes, created_at) VALUES (?, ?, ?, ?, NOW())");

foreach ([1, 2, 3, 4, 5] as $inspId) {
    $preset = $detailsPresets[$inspId] ?? [];
    foreach ($allChecklistItems as $cId) {
        $status = 'good';
        $note = 'Kondisi baik dan berfungsi normal.';

        if (!empty($preset['damaged']) && in_array($cId, $preset['damaged'])) {
            $status = 'damaged';
            $note = 'Terlihat kerusakan yang memerlukan perbaikan / penanganan teknis.';
        } elseif (!empty($preset['attention']) && in_array($cId, $preset['attention'])) {
            $status = 'attention';
            $note = 'Perlu perhatian dan monitoring rutin.';
        } elseif (!empty($preset['not_checked']) && in_array($cId, $preset['not_checked'])) {
            $status = 'not_checked';
            $note = '';
        }

        $stmtDetailIns->execute([$inspId, $cId, $status, $note]);
    }
}
echo "Inspection Details snapshot berhasil di-seed." . PHP_EOL;

// 8. SEED FINDINGS (Realistic findings across inspections)
$findingsData = [
    // Inspeksi 1 - Rektorat
    [
        1, 'Noda Air dan Plafon Melendut di Koridor Sayap Barat Lantai 3',
        'Gedung Rektorat Lantai 3 Koridor Barat depan Ruang Rapat 302',
        'Terdapat bekas rembesan air seluas kurang lebih 1.5 x 1 meter pada panel plafon gipsum, rangka plafon tampak sedikit melendut akibat beban air bocor dari talang dak atas saat hujan deras.',
        'medium',
        'Segera periksa dan tambal kebocoran sambungan talang dak atap beton, lalu ganti 2 lembar panel gipsum yang melendut.',
        'completed', 'Kebocoran talang dak telah ditambal dengan pelapis bitumen sika, dan plafon gipsum telah diganti baru serta dicat ulang.', '2026-10-22 14:00:00'
    ],
    // Inspeksi 1 - Rektorat (Finding 2)
    [
        1, 'Penutup Stop Kontak Pecah di Ruang Arsip Lantai 1',
        'Lantai 1, Ruang Tata Usaha & Kearsipan Sudut Selatan',
        'Penutup mika stop kontak 2 lubang pecah sehingga bagian terminal kabel bermuatan fasa terbuka, membahayakan keselamatan staf arsip.',
        'high',
        'Segera ganti stop kontak dengan tipe bertutup pengaman (child-protection) dan pastikan kabel grounding terpasang rapat.',
        'completed', 'Stop kontak telah diganti baru oleh teknisi listrik kampus pada 18 Oktober 2026.', '2026-10-18 16:30:00'
    ],
    // Inspeksi 2 - RSUD Graha Medika
    [
        2, 'Rembesan Air dan Cat Terkelupas pada Dinding Toilet Pasien Kamar 204',
        'Paviliun A Lantai 2, Kamar Rawat Inap No. 204 Toilet Dalam',
        'Dinding luar toilet kamar 204 mengalami rembesan air konstan dari pipa instalasi air kotor di balik dinding, menyebabkan cat berjamur dan terkelupas sepanjang 1.2 meter.',
        'medium',
        'Lakukan pembongkaran setempat keramik toilet untuk mencari titik kebocoran pipa buang, sambung kembali, lalu aplikasikan cat anti-jamur.',
        'in_progress', NULL, NULL
    ],
    // Inspeksi 3 - SMK Negeri 1 (Pending Review)
    [
        3, 'Retak Diagonal Struktural pada Balok Beton Sayap Timur Lab Mesin',
        'Workshop Lab Mesin Lantai 1, As B-4 Balok Utama Penopang Lantai 2',
        'Ditemukan retakan diagonal dengan lebar celah sekitar 1.8 mm pada balok beton penyangga lantai 2 di dekat tumpuan kolom. Retakan mengindikasikan tegangan geser berlebih akibat beban getaran mesin bubut di atasnya.',
        'critical',
        'Pasang perancah (shoring) penopang sementara di bawah balok. Lakukan uji NDT (Non-Destructive Testing) ultrasonic dan injeksi resin epoxy struktural oleh kontraktor spesialis.',
        'open', NULL, NULL
    ],
    // Inspeksi 3 - SMK Negeri 1 (Finding 2)
    [
        3, 'Kabel Suplai Daya 3-Fasa Tanpa Pipa Konduit di Jalur Pejalan Kaki',
        'Area Selasar Terbuka Penghubung Gedung Teori dan Bengkel Mesin',
        'Kabel NYY 4x16mm² melintang di lantai tanpa pipa pelindung galvanis/konduit PVC tebal, berisiko tinggi terinjak, terlindas forklift ringan, atau tersengat arus bocor.',
        'high',
        'Tanam kabel di bawah lantai menggunakan pipa duct atau naikkan ke tray kabel gantung setinggi minimal 2.8 meter.',
        'open', NULL, NULL
    ],
    // Inspeksi 4 - GOR Satria (Revision)
    [
        4, 'Korosi Sambungan Plat Buhul Rangka Baja Kuda-kuda Atap',
        'Rangka Baja Atap Zona Barat di atas Tribun Penonton Sektor 3',
        'Plat buhul penyambung profil pipa baja atap mengalami pengkaratan permukaan dengan serpihan karat mengelupas sedalam 0.5 mm karena tetesan kondensasi atap seng.',
        'high',
        'Bersihkan karat menggunakan sikat kawat mekanis, aplikasikan primer zink-chromate anti-karat 2 lapis dan top-coat polyurethane.',
        'open', NULL, NULL
    ]
];

$stmtFnd = $pdo->prepare("INSERT INTO inspection_findings (inspection_id, title, location_description, description, priority, recommendation, follow_up_status, resolution_notes, resolved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
foreach ($findingsData as $f) {
    $stmtFnd->execute($f);
}
echo "Inspection Findings berhasil di-seed (" . count($findingsData) . " findings)." . PHP_EOL;

// 9. SEED FOLLOW UPS
$followUpsData = [
    // finding 1
    [1, 1, 'in_progress', 'Tim pemeliharaan kampus telah melakukan pengecekan atap dak dan membeli bahan pelapis bitumen anti bocor.', '2026-10-20', NULL],
    [1, 1, 'completed', 'Pekerjaan perbaikan talang dak selesai 100%, penggantian plafon gipsum telah rapi dan dicat.', '2026-10-22', '2026-10-22 14:00:00'],
    // finding 2
    [2, 1, 'completed', 'Penggantian stop kontak baru Schneider Electric dengan pengaman anak selesai dipasang.', '2026-10-18', '2026-10-18 16:30:00'],
    // finding 3
    [3, 1, 'in_progress', 'Surat tugas pemanggilan teknisi plumbing sudah diterbitkan oleh pihak RSUD. Pembongkaran keramik dijadwalkan besok lusa.', '2026-11-04', NULL],
    // finding 4
    [4, 1, 'open', 'Temuan baru diteruskan ke dinas PU untuk pengadaan uji teknis kelayakan struktur beton.', '2026-11-10', NULL]
];

$stmtFu = $pdo->prepare("INSERT INTO follow_ups (finding_id, created_by, status, notes, target_date, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
foreach ($followUpsData as $fu) {
    $stmtFu->execute($fu);
}
echo "Follow-ups berhasil di-seed (" . count($followUpsData) . " follow-ups)." . PHP_EOL;

// 10. SEED REVIEWS
$reviewsData = [
    [1, 1, 'approved', 'Laporan inspeksi sangat memuaskan, data temuan relevan dan bukti foto jelas.'],
    [2, 1, 'approved', 'Hasil pemeriksaan diverifikasi sesuai standar sanitasi rumah sakit.'],
    [4, 1, 'revision', 'Harap lengkapi dokumentasi foto pengukuran tebal penampang baja yang terkorosi.']
];

$stmtRev = $pdo->prepare("INSERT INTO inspection_reviews (inspection_id, reviewer_id, decision, notes, created_at) VALUES (?, ?, ?, ?, NOW())");
foreach ($reviewsData as $r) {
    $stmtRev->execute($r);
}
echo "Inspection Reviews berhasil di-seed (" . count($reviewsData) . " reviews)." . PHP_EOL;

// 11. SEED ACTIVITY LOGS
$logsData = [
    [1, 'LOGIN', 'users', 1, 'Admin melakukan login ke sistem.', '127.0.0.1'],
    [1, 'CREATE', 'inspections', 1, 'Admin menjadwalkan inspeksi INS-20261015-A101 untuk Gedung Rektorat.', '127.0.0.1'],
    [2, 'UPDATE', 'inspections', 1, 'Petugas Dwi Gifa mengisi checklist inspeksi INS-20261015-A101.', '127.0.0.1'],
    [2, 'SUBMIT', 'inspections', 1, 'Petugas Dwi Gifa mengirimkan inspeksi INS-20261015-A101 untuk review.', '127.0.0.1'],
    [1, 'APPROVE', 'inspections', 1, 'Admin menyetujui laporan inspeksi INS-20261015-A101.', '127.0.0.1'],
    [3, 'SUBMIT', 'inspections', 2, 'Petugas Layra mengirimkan inspeksi INS-20261018-B202.', '127.0.0.1'],
    [1, 'APPROVE', 'inspections', 2, 'Admin menyetujui laporan inspeksi INS-20261018-B202.', '127.0.0.1'],
    [4, 'CREATE', 'inspection_findings', 4, 'Petugas Agung melaporkan temuan kritis retak struktural balok beton.', '127.0.0.1']
];

$stmtLog = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
foreach ($logsData as $lg) {
    $stmtLog->execute($lg);
}
echo "Activity Logs berhasil di-seed (" . count($logsData) . " logs)." . PHP_EOL;

// 12. GENERATE SAMPLE IMAGES FOR FINDINGS & BUILDINGS
$uploadDir = __DIR__ . '/public/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Function to generate SVG image as placeholder
function generateSampleSvg($text, $color, $filename) {
    global $uploadDir;
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <rect width="100%" height="100%" fill="{$color}"/>
  <rect x="20" y="20" width="760" height="560" fill="none" stroke="#ffffff" stroke-width="4" stroke-opacity="0.3"/>
  <circle cx="400" cy="240" r="80" fill="#ffffff" fill-opacity="0.15"/>
  <path d="M370 240 L430 240 M400 210 L400 270" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
  <text x="400" y="380" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#ffffff" text-anchor="middle">{$text}</text>
  <text x="400" y="420" font-family="Arial, sans-serif" font-size="16" fill="#ffffff" fill-opacity="0.8" text-anchor="middle">Dokumentasi Inspeksi Bangunan</text>
</svg>
SVG;
    file_put_contents($uploadDir . $filename, $svg);
}

// Create sample images for findings
generateSampleSvg('KERUSAKAN PLAFON LEMBAB', '#d97706', 'sample_plafon_lembab.svg');
generateSampleSvg('STOP KONTAK PECAH', '#dc2626', 'sample_stopkontak.svg');
generateSampleSvg('REMBESAN DINDING TOILET', '#0284c7', 'sample_dinding_rembes.svg');
generateSampleSvg('RETAK STRUKTURAL BALOK', '#b91c1c', 'sample_retak_balok.svg');
generateSampleSvg('KABEL JALUR PEJALAN KAKI', '#ea580c', 'sample_kabel_daya.svg');
generateSampleSvg('KOROSI SAMBUNGAN RANGKA BAJA', '#b45309', 'sample_korosi_baja.svg');

// Link finding photos to table
$photosData = [
    [1, 'sample_plafon_lembab.svg', 'foto_plafon_koridor3.svg', 'Foto rembesan air pada gipsum koridor lantai 3', 2],
    [2, 'sample_stopkontak.svg', 'foto_stopkontak_arsip.svg', 'Tutup stop kontak pecah terbuka di ruang arsip', 2],
    [3, 'sample_dinding_rembes.svg', 'foto_dinding_kamar204.svg', 'Cat mengelupas berjamur dinding kamar 204', 3],
    [4, 'sample_retak_balok.svg', 'foto_retak_balok_mesin.svg', 'Retak diagonal 1.8mm pada tumpuan balok lab mesin', 4],
    [5, 'sample_kabel_daya.svg', 'foto_kabel_tanpa_konduit.svg', 'Kabel NYY melintang tanpa pelindung di selasar bengkel', 4],
    [6, 'sample_korosi_baja.svg', 'foto_korosi_baja_tribun.svg', 'Plat buhul kuda-kuda rangka atap berkarat', 5]
];

$stmtPhoto = $pdo->prepare("INSERT INTO finding_photos (finding_id, file_path, original_name, caption, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
foreach ($photosData as $ph) {
    $stmtPhoto->execute($ph);
}
echo "Finding Photos berhasil di-seed (" . count($photosData) . " photos)." . PHP_EOL;

echo "=== SEEDING BERHASIL LENGKAP & SEMPURNA! ===" . PHP_EOL;
