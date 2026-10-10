<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/GuestBook.php';

session_start();

/* =========================
   KONEKSI DATABASE
   ========================= */
$host = '127.0.0.1';
$db = 'akademik_db';
$user = 'root';
$pass = '';

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    http_response_code(500);
    exit('Sistem mengalami kegagalan teknis. Silakan coba lagi nanti.');
}

$guestBook = new GuestBook($pdo);
$errors = [];
$successMessage = '';
$nama = '';
$email = '';
$pesan = '';

/* =========================
   CSRF TOKEN
   ========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================
   PROSES FORM
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';

    if (!is_string($postToken) || !hash_equals($_SESSION['csrf_token'], $postToken)) {
        http_response_code(403);
        exit('Kesalahan keamanan: token CSRF tidak cocok. Muat ulang halaman lalu coba kembali.');
    }

    $namaInput = $_POST['nama'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $pesanInput = $_POST['pesan'] ?? '';

    $nama = is_string($namaInput) ? trim($namaInput) : '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $pesan = is_string($pesanInput) ? trim($pesanInput) : '';

    if ($nama === '') {
        $errors[] = 'Nama tidak boleh kosong.';
    } elseif (mb_strlen($nama, 'UTF-8') > 100) {
        $errors[] = 'Nama maksimal 100 karakter.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    } elseif (mb_strlen($email, 'UTF-8') > 150) {
        $errors[] = 'Email maksimal 150 karakter.';
    }

    if (mb_strlen($pesan, 'UTF-8') < 5) {
        $errors[] = 'Pesan harus memiliki minimal 5 karakter.';
    }

    if (empty($errors)) {
        try {
            if ($guestBook->addMessage($nama, $email, $pesan)) {
                $successMessage = 'Pesan berhasil dikirim. Terima kasih, ' . $nama . '!';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $nama = '';
                $email = '';
                $pesan = '';
            }
        } catch (PDOException $e) {
            error_log('GuestBook Insert Error: ' . $e->getMessage());
            $errors[] = 'Pesan tidak dapat disimpan saat ini. Silakan coba lagi.';
        }
    }
}

/* =========================
   AMBIL DATA PESAN
   ========================= */
try {
    $messages = $guestBook->getMessages();
} catch (PDOException $e) {
    error_log('GuestBook Select Error: ' . $e->getMessage());
    $messages = [];
    $errors[] = 'Riwayat pesan tidak dapat dimuat saat ini.';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buku Tamu Digital Perpustakaan Fakultas Teknik Universitas Sulawesi Barat">
    <meta name="theme-color" content="#6f1728">
    <title>Buku Tamu Perpustakaan Digital | Unsulbar</title>

    <link rel="preconnect" href="https://unsulbar.ac.id">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --maroon: #70192b;
            --maroon-dark: #4b1020;
            --maroon-mid: #8b2b3d;
            --cream: #fbf3e8;
            --cream-deep: #f2e4d3;
            --paper: #fffaf3;
            --ink: #3e2023;
            --muted: #816b67;
            --line: #ead8c8;
            --green: #526b43;
            --danger: #a52a36;
            --shadow: 0 12px 34px rgba(88, 37, 36, .08);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: radial-gradient(circle at 8% 0%, #fffaf2 0, var(--cream) 45%, #f6e9dc 100%);
            color: var(--ink);
            font-family: 'DM Sans', Arial, sans-serif;
            line-height: 1.5;
        }

        .site-shell { width: min(1480px, calc(100% - 44px)); margin: 22px auto 36px; }
        .site-header {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            gap: 28px;
            min-height: 144px;
            padding: 22px 30px;
            border: 1px solid rgba(130, 77, 58, .15);
            border-radius: 18px;
            background: linear-gradient(110deg, rgba(255,250,243,.98), rgba(248,231,212,.95));
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .site-header::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: 390px;
            height: 150px;
            right: -30px;
            bottom: -74px;
            border-radius: 50% 50% 0 0;
            background: rgba(185, 123, 91, .13);
            transform: rotate(-8deg);
        }
        .brand-block {
            flex: 0 0 168px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 90px;
            padding-right: 24px;
            border-right: 1px solid #cda99a;
        }
        .brand-logo { display: block; width: 104px; height: 104px; object-fit: contain; }
        .brand-fallback {
            display: none;
            width: 84px;
            height: 84px;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--maroon);
            border-radius: 50%;
            color: var(--maroon);
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 26px;
            font-weight: 800;
        }
        .header-copy { max-width: 850px; }
        .eyebrow { margin: 0 0 5px; color: var(--maroon-mid); font-size: 11px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        .header-copy h1 {
            margin: 0;
            color: var(--maroon-dark);
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(25px, 3vw, 43px);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -.025em;
        }
        .header-copy .faculty { margin: 9px 0 0; color: var(--maroon); font-family: 'Playfair Display', Georgia, serif; font-size: clamp(14px, 1.4vw, 20px); font-weight: 700; line-height: 1.45; }
        .header-note { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .header-books { margin-left: auto; align-self: flex-end; display: flex; gap: 5px; align-items: flex-end; min-width: 130px; opacity: .55; }
        .book-spine { display: block; width: 29px; border: 1px solid #b7776e; border-radius: 4px 4px 1px 1px; background: #b7776e; box-shadow: inset 4px 0 rgba(255,255,255,.2); }
        .book-spine:nth-child(1) { height: 58px; background: #d0a18c; }
        .book-spine:nth-child(2) { height: 76px; background: #8c3443; }
        .book-spine:nth-child(3) { height: 66px; background: #c78d7e; }
        .book-spine:nth-child(4) { height: 88px; background: #ead2b9; }

        .main-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; margin-top: 22px; align-items: stretch; }
        .panel { min-width: 0; border: 1px solid rgba(130, 77, 58, .13); border-radius: 16px; background: rgba(255,250,243,.8); box-shadow: var(--shadow); }

        .form-panel {
            position: relative;
            overflow: hidden;
            padding: clamp(22px, 3vw, 38px);
            color: #fff8f0;
            background: linear-gradient(145deg, var(--maroon) 0%, #661525 62%, var(--maroon-dark) 100%);
            border: 5px solid #fffaf3;
        }
        .form-panel::before, .form-panel::after { content: ''; position: absolute; pointer-events: none; border-radius: 50%; }
        .form-panel::before { width: 330px; height: 330px; right: -220px; top: -225px; border: 42px solid rgba(255,255,255,.035); }
        .form-panel::after { width: 170px; height: 170px; left: -110px; bottom: -115px; border: 24px solid rgba(255,255,255,.035); }
        .panel-heading { position: relative; margin-bottom: 24px; }
        .panel-heading h2 { margin: 0; color: inherit; font-family: 'Playfair Display', Georgia, serif; font-size: clamp(22px, 2vw, 28px); font-weight: 700; }
        .panel-heading p { margin: 7px 0 0; color: rgba(255,248,240,.78); font-size: 13px; }
        .form-group { position: relative; margin-top: 19px; }
        .form-label { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; color: #fff8f0; font-weight: 700; }
        .field-icon { display: inline-flex; width: 29px; height: 29px; align-items: center; justify-content: center; flex: 0 0 29px; border: 1px solid rgba(255,255,255,.35); border-radius: 9px; background: rgba(255,255,255,.11); font-size: 15px; }
        .form-control {
            display: block;
            width: 100%;
            min-height: 52px;
            padding: 13px 16px;
            border: 1px solid #e6d1bf;
            border-radius: 11px;
            outline: none;
            color: var(--ink);
            background: var(--paper);
            font: inherit;
            transition: border-color .2s, box-shadow .2s;
        }
        textarea.form-control { min-height: 128px; resize: vertical; }
        .form-control::placeholder { color: #a18b83; }
        .form-control:focus { border-color: #d7a991; box-shadow: 0 0 0 4px rgba(255,239,220,.18); }
        .btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            min-height: 58px;
            margin-top: 22px;
            padding: 14px 20px;
            border: 2px solid #fff2e6;
            border-radius: 12px;
            color: #fffaf4;
            background: linear-gradient(100deg, #8a293c, #6b1728);
            box-shadow: 0 6px 16px rgba(31,0,8,.22), inset 0 1px rgba(255,255,255,.13);
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s, background .2s, box-shadow ,2s;
        }
        .btn-submit:hover { transform: translateY(-2px); background: #8a293c; box-shadow: 0 9px 20px rgba(31,0,8,.25); }
        .btn-submit:focus-visible { outline: 3px solid #f2d6bd; outline-offset: 3px; }
        .form-hint { position: relative; margin: 13px 0 0; color: rgba(255,248,240,.7); font-size: 12px; text-align: center; }

        .alerts { margin-bottom: 16px; }
        .alert { padding: 13px 15px; border-radius: 10px; font-size: 14px; }
        .alert ul { margin: 0; padding-left: 20px; }
        .alert-error { border: 1px solid #e8b8b5; color: #81212c; background: #fff0ed; }
        .alert-success { border: 1px solid #bed0b2; color: #35562d; background: #f0f7e9; }

        .suggestions-panel { padding: 16px; }
        .search-wrap { position: relative; margin-bottom: 16px; }
        .search-wrap .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--maroon); font-size: 22px; pointer-events: none; }
        .search-input { width: 100%; min-height: 49px; padding: 12px 16px 12px 47px; border: 1px solid #dfcdbd; border-radius: 10px; outline: none; color: var(--ink); background: #fffaf4; font: inherit; }
        .search-input:focus { border-color: #9e5863; box-shadow: 0 0 0 3px rgba(112,25,43,.09); }
        .book-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .book-card {
            display: flex;
            min-width: 0;
            min-height: 145px;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 13px 9px 12px;
            border: 1px solid #e8d8c9;
            border-radius: 12px;
            color: var(--maroon-dark);
            background: linear-gradient(150deg, #fffaf3, #f8ecdd);
            text-align: center;
            box-shadow: 0 3px 8px rgba(87,41,34,.035);
            transition: transform .2s, border-color .2s, box-shadow .2s;
        }
        .book-card:hover { transform: translateY(-3px); border-color: #c9948b; box-shadow: 0 8px 16px rgba(87,41,34,.08); }
        .book-art { display: flex; align-items: center; justify-content: center; height: 82px; width: 100%; }
        .book-art svg { width: 88px; height: 78px; max-width: 100%; }
        .book-card strong { margin-top: 7px; font-family: 'Playfair Display', Georgia, serif; font-size: 12px; line-height: 1.35; }
        .suggestion-caption { margin: 14px 3px 1px; color: var(--muted); font-size: 11px; text-align: center; }
        .no-books { grid-column: 1 / -1; padding: 20px 10px; color: var(--muted); text-align: center; }

        .messages-panel { margin-top: 22px; padding: clamp(18px, 2.2vw, 28px); }
        .messages-heading { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .heading-icon { display: inline-flex; width: 43px; height: 43px; flex: 0 0 43px; align-items: center; justify-content: center; border-radius: 12px; color: #fff8f0; background: var(--maroon); font-size: 21px; }
        .messages-heading h2 { margin: 0; color: var(--maroon-dark); font-family: 'Playfair Display', Georgia, serif; font-size: clamp(20px, 2.3vw, 29px); line-height: 1.2; }
        .message-count { margin-left: auto; padding: 6px 11px; border: 1px solid #e6d2c1; border-radius: 999px; color: var(--maroon); background: #fff7ed; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .table-wrap { overflow-x: auto; border: 1px solid #e5d3c3; border-radius: 10px; background: #fffaf3; }
        .messages-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .messages-table th { padding: 13px 16px; color: #fff8f0; background: linear-gradient(90deg, var(--maroon), #85283a); font-size: 13px; font-weight: 700; text-align: left; }
        .messages-table td { padding: 13px 16px; border-top: 1px solid #eee0d4; color: #4a2a2a; font-size: 13px; vertical-align: top; }
        .messages-table tbody tr:nth-child(even) { background: #fbf0e4; }
        .messages-table tbody tr:hover { background: #f6e6d8; }
        .messages-table .name-cell { color: var(--maroon); font-weight: 800; }
        .date-cell { white-space: nowrap; color: var(--muted) !important; }
        .empty-state { padding: 24px; color: var(--muted); text-align: center; }
        .site-footer { padding: 20px 10px 0; color: var(--muted); font-size: 12px; text-align: center; }
        .site-footer strong { color: var(--maroon); }

        @media (max-width: 980px) {
            .site-header { gap: 20px; padding: 22px; }
            .brand-block { flex-basis: 130px; padding-right: 18px; }
            .brand-logo { width: 86px; height: 86px; }
            .header-books { min-width: 85px; }
            .book-spine { width: 21px; }
            .main-grid { grid-template-columns: minmax(0, 1fr); }
            .form-panel { min-height: auto; }
        }
        @media (max-width: 620px) {
            .site-shell { width: min(100% - 22px, 600px); margin: 11px auto 24px; }
            .site-header { align-items: flex-start; gap: 14px; padding: 17px 15px; border-radius: 14px; }
            .brand-block { flex: 0 0 75px; min-height: 70px; padding-right: 12px; }
            .brand-logo { width: 67px; height: 67px; }
            .brand-fallback { width: 60px; height: 60px; font-size: 18px; }
            .header-copy h1 { font-size: clamp(21px, 6vw, 31px); }
            .header-copy .faculty { font-size: 13px; }
            .header-note { font-size: 11px; }
            .header-books { display: none; }
            .main-grid { gap: 14px; margin-top: 14px; }
            .form-panel { padding: 21px 17px; border-width: 3px; }
            .suggestions-panel { padding: 12px; }
            .book-grid { gap: 8px; }
            .book-card { min-height: 119px; padding: 9px 5px; }
            .book-art { height: 64px; }
            .book-art svg { height: 62px; }
            .book-card strong { font-size: 10px; }
            .messages-panel { margin-top: 14px; padding: 15px 12px; }
            .messages-heading { gap: 9px; }
            .heading-icon { width: 36px; height: 36px; flex-basis: 36px; }
            .messages-heading h2 { font-size: 20px; }
            .message-count { padding: 5px 8px; font-size: 10px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <div class="brand-block">
            <img class="brand-logo"
                 src="https://unsulbar.ac.id/images/logo-unsulbar.png"
                 alt="Lambang resmi Universitas Sulawesi Barat"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="brand-fallback" aria-hidden="true">U</span>
        </div>
        <div class="header-copy">
            <p class="eyebrow">Ruang aspirasi sivitas akademika</p>
            <h1>BUKU TAMU PERPUSTAKAAN DIGITAL</h1>
            <p class="faculty">FAKULTAS TEKNIK</p>
            <p class="faculty">UNIVERSITAS SULAWESI BARAT</p>
            <p class="header-note">Sampaikan pesan, kesan, dan saran untuk layanan perpustakaan.</p>
        </div>
        <div class="header-books" aria-hidden="true">
            <span class="book-spine"></span><span class="book-spine"></span><span class="book-spine"></span><span class="book-spine"></span>
        </div>
    </header>

    <main>
        <div class="main-grid">
            <section class="panel form-panel" aria-labelledby="form-title">
                <div class="panel-heading">
                    <p class="eyebrow" style="color:#f0c8b8">Kami ingin mendengar darimu</p>
                    <h2 id="form-title">Tulis Pesan</h2>
                    <p>Bagikan pengalaman atau saranmu untuk perpustakaan kampus.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alerts alert-error" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= e((string) $error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="alerts alert-success" role="status"><?= e($successMessage) ?></div>
                <?php endif; ?>

                <form action="./guestbook.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label class="form-label" for="nama"><span class="field-icon" aria-hidden="true">♙</span> Nama</label>
                        <input class="form-control" type="text" id="nama" name="nama" maxlength="100" value="<?= e($nama) ?>" placeholder="Masukkan nama Anda" autocomplete="name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email"><span class="field-icon" aria-hidden="true">✉</span> Email</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($email) ?>" placeholder="Masukkan email Anda" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pesan"><span class="field-icon" aria-hidden="true">✎</span> Pesan</label>
                        <textarea class="form-control" id="pesan" name="pesan" rows="4" minlength="5" placeholder="Tulis pesan, kesan, atau saran Anda di sini..." required><?= e($pesan) ?></textarea>
                    </div>

                    <button class="btn-submit" type="submit"><span aria-hidden="true">➤</span> Kirim Pesan</button>
                    <p class="form-hint">Nama dan email hanya digunakan untuk keperluan buku tamu.</p>
                </form>
            </section>

            <section class="panel suggestions-panel" aria-labelledby="suggestions-title">
                <div class="search-wrap">
                    <label for="bookSearch" class="search-icon" aria-hidden="true">⌕</label>
                    <input class="search-input" id="bookSearch" type="search" placeholder="Cari judul buku..." autocomplete="off" aria-label="Cari judul buku saran">
                </div>
                <div class="panel-heading" style="margin: 0 3px 12px">
                    <h2 id="suggestions-title" style="font-size:22px;color:var(--maroon-dark)">Saran Buku Bacaan</h2>
                    <p style="color:var(--muted)">Temukan inspirasi bacaan untuk menemani proses belajarmu.</p>
                </div>

                <div class="book-grid" id="bookGrid">
                    <article class="book-card" data-title="pemrograman web html css php">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M13 18 Q35 9 53 24 V73 Q34 58 13 67Z" fill="#f6dfc4" stroke="#7b2435" stroke-width="3"/><path d="M53 24 Q74 9 97 18 V67 Q75 58 53 73Z" fill="#fff1de" stroke="#7b2435" stroke-width="3"/><path d="M53 24V73 M22 31l22-2 M22 41l22-2 M22 51l22-2 M64 29l22 2 M64 39l22 2 M64 49l22 2" stroke="#c89e83" stroke-width="2" stroke-linecap="round"/></svg></div>
                        <strong>Pemrograman Web</strong>
                    </article>
                    <article class="book-card" data-title="basis data database sql mysql">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M15 28L72 13L98 26L41 42Z" fill="#9a4350"/><path d="M15 28V55L41 70V42Z" fill="#6f1728"/><path d="M41 42L98 26V52L41 70Z" fill="#b96f69"/><path d="M15 44L41 58L98 40" stroke="#f5d9c3" stroke-width="2"/></svg></div>
                        <strong>Basis Data & SQL</strong>
                    </article>
                    <article class="book-card" data-title="algoritma struktur data pemecahan masalah">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M29 13L81 5L92 12V70L39 79L29 72Z" fill="#7a1c2e" stroke="#54101f" stroke-width="2"/><path d="M38 20L81 13 M38 29L81 22 M38 38L68 33" stroke="#f1d5bd" stroke-width="3" stroke-linecap="round"/><path d="M55 54l5-8 5 8-5 8Z" fill="#ddb58e"/></svg></div>
                        <strong>Algoritma & Struktur Data</strong>
                    </article>
                    <article class="book-card" data-title="ui ux desain antarmuka pengalaman pengguna">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M24 13L73 7L88 19V72L39 78L24 66Z" fill="#e7c6a8" stroke="#bd9379" stroke-width="2"/><rect x="37" y="23" width="37" height="25" rx="3" fill="#fff2df"/><circle cx="48" cy="32" r="5" fill="#c58f79"/><path d="M40 44l10-7 8 6 7-5 9 10H40Z" fill="#ad6d67"/><path d="M38 57h34 M38 63h26" stroke="#bd9379" stroke-width="2" stroke-linecap="round"/></svg></div>
                        <strong>UI/UX Design</strong>
                    </article>
                    <article class="book-card" data-title="kecerdasan buatan artificial intelligence machine learning">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M24 12L74 5L89 15V72L39 79L24 69Z" fill="#7a1c2e" stroke="#54101f" stroke-width="2"/><path d="M44 28h24v24H44z" stroke="#f4d6bc" stroke-width="2"/><path d="M56 22v6 M56 52v6 M38 40h6 M68 40h7 M45 31l-5-5 M66 49l5 5" stroke="#f4d6bc" stroke-width="2"/><circle cx="56" cy="40" r="6" fill="#ddb58e"/></svg></div>
                        <strong>Kecerdasan Buatan</strong>
                    </article>
                    <article class="book-card" data-title="jaringan komputer internet komunikasi data">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M13 20L48 12L48 72L13 78Z" fill="#c9957e"/><path d="M51 13L79 19L79 73L51 70Z" fill="#f1dfc9" stroke="#c5a18a" stroke-width="2"/><path d="M82 17L99 24V70L82 73Z" fill="#8d3343"/><path d="M20 30l21-5 M20 39l21-5 M20 48l21-5" stroke="#faead6" stroke-width="2"/></svg></div>
                        <strong>Jaringan Komputer</strong>
                    </article>
                    <article class="book-card" data-title="keamanan siber cybersecurity keamanan informasi">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M28 12L78 5L91 15V72L41 79L28 69Z" fill="#8a2b3b" stroke="#54101f" stroke-width="2"/><path d="M60 24l13 5v12c0 10-8 15-13 18-6-3-13-8-13-18V29Z" fill="#f0d3b3"/><path d="M54 40l4 4 8-9" stroke="#7b2333" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        <strong>Keamanan Siber</strong>
                    </article>
                    <article class="book-card" data-title="rekayasa perangkat lunak software engineering">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M13 19L47 10V70L13 77Z" fill="#f5dfc7" stroke="#bd9379" stroke-width="2"/><path d="M50 12L79 18V73L50 70Z" fill="#8a2b3b"/><path d="M82 18L99 23V69L82 73Z" fill="#c9957e"/><path d="M21 31l18-5 M21 40l18-5 M21 49l18-5" stroke="#9e7061" stroke-width="2"/></svg></div>
                        <strong>Rekayasa Perangkat Lunak</strong>
                    </article>
                    <article class="book-card" data-title="data science analisis data visualisasi statistik">
                        <div class="book-art" aria-hidden="true"><svg viewBox="0 0 110 85" fill="none"><path d="M23 13L75 6L89 16V71L37 79L23 69Z" fill="#e7c6a8" stroke="#bd9379" stroke-width="2"/><path d="M38 58V42H47V58 M52 58V31H61V58 M66 58V23H75V58" fill="#8a2b3b"/><path d="M34 63h47" stroke="#b88c75" stroke-width="2"/></svg></div>
                        <strong>Data Science</strong>
                    </article>
                </div>
                <p class="suggestion-caption">Kartu saran ini adalah inspirasi kategori bacaan, bukan katalog stok perpustakaan.</p>
            </section>
        </div>

        <section class="panel messages-panel" aria-labelledby="messages-title">
            <div class="messages-heading">
                <span class="heading-icon" aria-hidden="true">✉</span>
                <h2 id="messages-title">Daftar Pesan, Kesan, dan Saran</h2>
                <span class="message-count"><?= count($messages) ?> pesan</span>
            </div>

            <?php if (!empty($messages)): ?>
                <div class="table-wrap">
                    <table class="messages-table">
                        <thead>
                            <tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Pesan</th><th scope="col">Tanggal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td class="name-cell"><?= e((string) $message['nama']) ?></td>
                                    <td><?= e((string) $message['email']) ?></td>
                                    <td><?= nl2br(e((string) $message['pesan'])) ?></td>
                                    <td class="date-cell"><?= e((string) $message['tanggal_kirim']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">Belum ada pesan. Jadilah orang pertama yang berbagi saran untuk perpustakaan.</div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">Dikelola untuk <strong>Fakultas Teknik · Universitas Sulawesi Barat</strong></footer>
</div>

<script>
    const bookSearch = document.getElementById('bookSearch');
    const bookCards = Array.from(document.querySelectorAll('.book-card'));
    const bookGrid = document.getElementById('bookGrid');
    const noBooks = document.createElement('div');
    noBooks.className = 'no-books';
    noBooks.textContent = 'Tidak ada saran buku yang cocok dengan pencarian.';
    noBooks.hidden = true;
    bookGrid.appendChild(noBooks);

    bookSearch.addEventListener('input', () => {
        const query = bookSearch.value.trim().toLocaleLowerCase('id');
        let visible = 0;
        bookCards.forEach((card) => {
            const title = (card.dataset.title + ' ' + card.textContent).toLocaleLowerCase('id');
            const matches = title.includes(query);
            card.hidden = !matches;
            if (matches) visible += 1;
        });
        noBooks.hidden = visible !== 0;
    });
</script>
</body>
</html>