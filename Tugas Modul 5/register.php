<?php

declare(strict_types=1);

require_once './Student.php';

session_start();

$errors = [];
$successMessage = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $postToken)) {
        die('Kesalahan Keamanan: Token CSRF tidak cocok.');
    }

    $nim = trim($_POST['nim'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!preg_match('/^[0-9]{10}$/', $nim)) {
        $errors[] = 'NIM harus berupa angka sepanjang tepat 10 digit.';
    }

    if (empty($name)) {
        $errors[] = 'Nama lengkap mahasiswa tidak boleh kosong.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }

    if (empty($errors)) {

        $student = new Student(
            $nim,
            $name,
            $email
        );

        if ($student->saveToSession()) {

            $successMessage =
                'Pendaftaran mahasiswa ' .
                htmlspecialchars($student->getName()) .
                ' berhasil disimpan!';

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Pendaftaran Mahasiswa Baru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
</head>

<body class="bg-light py-5">

    <main class="container" style="max-width: 600px;">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0">
                    Formulir Pendaftaran Mahasiswa Baru
                </h1>
            </div>

            <div class="card-body">

                <!-- Pesan Error -->
                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">

                        <ul class="mb-0">

                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?= htmlspecialchars($error) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>


                <!-- Pesan Sukses -->
                <?php if (!empty($successMessage)): ?>

                    <div class="alert alert-success">
                        <?= htmlspecialchars($successMessage) ?>
                    </div>

                <?php endif; ?>


                <!-- Form -->
                <form action="./register.php" method="POST">

                    <!-- CSRF Token -->
                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">


                    <!-- NIM -->
                    <div class="mb-3">

                        <label for="nim"
                               class="form-label">
                            Nomor Induk Mahasiswa (NIM)
                        </label>

                        <input type="text"
                               class="form-control"
                               id="nim"
                               name="nim"
                               required
                               placeholder="Masukkan NIM">


                    </div>


                    <!-- Nama -->
                    <div class="mb-3">

                        <label for="name"
                               class="form-label">
                            Nama Lengkap
                        </label>

                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               required
                               placeholder="Masukkan nama lengkap">


                    </div>


                    <!-- Email -->
                    <div class="mb-3">

                        <label for="email"
                               class="form-label">
                            Alamat Email
                        </label>

                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               required
                               placeholder="nama@mahasiswa.ac.id">


                    </div>


                    <!-- Tombol -->
                    <button type="submit"
                            class="btn btn-primary w-100">
                        Daftar Sekarang
                    </button>

                </form>

            </div>

        </div>


        <!-- Daftar Mahasiswa -->
        <?php if (!empty($_SESSION['registered_students'])): ?>

            <div class="card mt-4 shadow-sm">

                <div class="card-header bg-secondary text-white">

                    <h2 class="h5 mb-0">
                        Daftar Mahasiswa Terdaftar
                    </h2>

                </div>

                <div class="card-body">

                    <ul class="list-group">

                        <?php foreach ($_SESSION['registered_students'] as $student): ?>

                            <li class="list-group-item">

                                <strong>
                                    <?= htmlspecialchars($student['nim']) ?>
                                </strong>

                                -
                                <?= htmlspecialchars($student['name']) ?>

                                <em>
                                    (<?= htmlspecialchars($student['email']) ?>)
                                </em>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            </div>

        <?php endif; ?>

    </main>

</body>

</html>