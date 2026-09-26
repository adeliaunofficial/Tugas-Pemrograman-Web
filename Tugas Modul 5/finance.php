<?php

declare(strict_types=1);

require_once './Transaction.php';

session_start();

$errors = [];
$successMessage = '';

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}

if (!isset($_SESSION['transaction_id'])) {
    $_SESSION['transaction_id'] = 0;
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $postToken)) {
        die('Kesalahan Keamanan: Token CSRF tidak cocok.');
    }

    $typeInput = $_POST['type'] ?? '';
    $amountInput = trim($_POST['amount'] ?? '');

    $transactionType = match ($typeInput) {
        'deposit' => 'deposit',
        'withdraw' => 'withdraw',
        default => null
    };

    if ($transactionType === null) {
        $errors[] = 'Jenis transaksi tidak valid.';
    }

    if (
        $amountInput === '' ||
        !preg_match('/^\d+(?:\.\d{1,2})?$/', $amountInput)
    ) {
        $errors[] = 'Jumlah transaksi harus berupa angka desimal positif.';
    } else {

        $amount = (float) $amountInput;

        if ($amount <= 0) {
            $errors[] = 'Jumlah transaksi harus lebih besar dari 0.';
        }
    }

    if (empty($errors)) {

        $_SESSION['transaction_id']++;

        $transaction = new Transaction(
            $_SESSION['transaction_id'],
            $transactionType,
            $amount
        );

        if ($transaction->process()) {

            $successMessage =
                ucfirst($transaction->getType()) .
                ' sebesar Rp ' .
                number_format(
                    $transaction->getAmount(),
                    2,
                    ',',
                    '.'
                ) .
                ' berhasil diproses.';

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));

        } else {

            $errors[] =
                'Penarikan ditolak karena saldo tidak mencukupi.';
        }
    }
}

$balance = (float) $_SESSION['balance'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Sistem Manajemen Keuangan Sederhana">

    <title>Sistem Manajemen Keuangan</title>

    <style>
         * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f5f7f8;
        color: #24323d;
    }

    .container {
        width: min(900px, 92%);
        margin: 40px auto;
    }

    .card {
        background-color: #ffffff;
        border-radius: 14px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 16px rgba(0, 38, 71, 0.10);
        border: 1px solid #e3e8ec;
    }

    .header {
        background-color: #002647;
        color: #ffffff;
        padding: 28px;
        border-radius: 14px;
        margin-bottom: 25px;
        border-left: 7px solid #8AC01E;
    }

    .header h1 {
        margin: 0 0 8px;
        font-size: 1.8rem;
    }

    .header p {
        margin: 0;
        color: #dce7ee;
    }

    .balance {
        font-size: 2.3rem;
        font-weight: bold;
        color: #002647;
        margin: 10px 0 0;
    }

    .balance::before {
        content: "SALDO AKTIF";
        display: block;
        font-size: 0.75rem;
        letter-spacing: 1.5px;
        color: #8AC01E;
        margin-bottom: 5px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 7px;
        color: #002647;
    }

    select,
    input,
    button {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        font-size: 1rem;
    }

    select,
    input {
        border: 1px solid #cbd5dc;
        background-color: #ffffff;
    }

    select:focus,
    input:focus {
        outline: none;
        border-color: #2086C8;
        box-shadow: 0 0 0 3px rgba(32, 134, 200, 0.12);
    }

    .form-group {
        margin-bottom: 18px;
    }

    button {
        border: none;
        background-color: #002647;
        color: #ffffff;
        cursor: pointer;
        font-weight: bold;
        transition: 0.25s ease;
    }

    button:hover {
        background-color: #8AC01E;
        color: #002647;
    }

    .alert {
        padding: 13px 15px;
        border-radius: 8px;
        margin-bottom: 18px;
    }

    .alert-error {
        background-color: #fde7e9;
        color: #842029;
        border-left: 4px solid #dc3545;
    }

    .alert-success {
        background-color: #edf7df;
        color: #315300;
        border-left: 4px solid #8AC01E;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        overflow: hidden;
        border-radius: 8px;
    }

    th,
    td {
        padding: 13px;
        text-align: left;
        border-bottom: 1px solid #e3e8ec;
    }

    th {
        background-color: #002647;
        color: #ffffff;
    }

    tr:hover td {
        background-color: #f3f8fa;
    }

    .deposit {
        color: #3f6f00;
        font-weight: bold;
    }

    .withdraw {
        color: #2086c8;
        font-weight: bold;
    }

    h2 {
        color: #002647;
    }

    .transaction-choice {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.transaction-choice input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.choice-card {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 14px;
    border: 2px solid #d7e0e6;
    border-radius: 10px;

    background-color: #ffffff;
    cursor: pointer;

    transition:
        border-color 0.25s ease,
        background-color 0.25s ease,
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.choice-card:hover {
    border-color: #2086C8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 38, 71, 0.08);
}

.transaction-choice input:checked + .choice-card {
    border-color: #8AC01E;
    background-color: #f2f8e8;
    box-shadow: 0 0 0 3px rgba(138, 192, 30, 0.12);
}

.choice-icon {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background-color: #002647;
    color: #ffffff;

    font-size: 1.4rem;
    font-weight: bold;
}

.transaction-choice input:checked + .choice-card .choice-icon {
    background-color: #8AC01E;
    color: #002647;
}

.choice-content {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.choice-content strong {
    color: #002647;
    font-size: 0.95rem;
}

.choice-content small {
    color: #6d7d88;
    font-size: 0.78rem;
}

    @media (max-width: 600px) {
        .container {
            width: 94%;
            margin: 20px auto;
        }

        .transaction-choice{
            gird-tamplate-columns: 1fr;
        }

        .card,
        .header {
            padding: 18px;
        }

        .header h1 {
            font-size: 1.4rem;
        }

        .balance {
            font-size: 1.8rem;
        }

        table {
            font-size: 0.85rem;
        }

        th,
        td {
            padding: 9px;
        }
    }
    </style>
</head>


<body>

    <main class="container">

        <section class="header">

            <h1>
                Sistem Manajemen Keuangan
            </h1>

            <p>
                Kelola transaksi deposit dan penarikan
                melalui sistem berbasis PHP.
            </p>

        </section>

        <section class="card">

            <h2>
                Saldo Saat Ini
            </h2>

            <p class="balance">
                Rp
                <?= htmlspecialchars(
                    number_format(
                        $balance,
                        2,
                        ',',
                        '.'
                    )
                ) ?>
            </p>

        </section>

        <section class="card">

            <h2>
                Tambah Transaksi
            </h2>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-error">

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>

                <div class="alert alert-success">

                    <?= htmlspecialchars($successMessage) ?>

                </div>

            <?php endif; ?>


            <form action="./finance.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >


                <div class="form-group">

                    <label>
                        Jenis Transaksi
                    </label>

                    <div class="transaction-choice">
                        <input
                        type="radio"
                        id="deposit"
                        name="type"
                        value="deposit"
                        require
                        >

                        <label for="deposit" class="choice-card">
                            <span class="choice-icon">+</span>

                            <span class="choice-content">
                                <strong>Deposit</strong>
                                <small>Menambah Saldo</small>
                            </span>
                        </label>

                        <input
                        type="radio"
                        id="withdraw"
                        name="type"
                        value="withdraw"
                        >

                        <label for="widthdraw" class="choice-card">
                            <span class="choice-icon">-</span>

                            <span class="choice-content">
                                <strong>Penarikan</storng>
                                <small>Mengurngi Saldo</small>
                            </span>
                        
                        </label>    
                    </div>
                </div>

                <div class="form-group">

                    <label for="amount">
                        Jumlah Transaksi
                    </label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="Contoh: 50000.00"
                    >

                </div>


                <button type="submit">
                    Proses Transaksi
                </button>

            </form>

        </section>


        <!-- Riwayat -->
        <section class="card">

            <h2>
                Riwayat Transaksi
            </h2>


            <?php if (!empty($_SESSION['transactions'])): ?>

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($_SESSION['transactions'] as $transaction): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $transaction['id']
                                    ) ?>
                                </td>

                                <td class="<?= htmlspecialchars(
                                    $transaction['type']
                                ) ?>">

                                    <?= htmlspecialchars(
                                        ucfirst($transaction['type'])
                                    ) ?>

                                </td>

                                <td>
                                    Rp
                                    <?= htmlspecialchars(
                                        number_format(
                                            (float) $transaction['amount'],
                                            2,
                                            ',',
                                            '.'
                                        )
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p>
                    Belum ada transaksi.
                </p>

            <?php endif; ?>

        </section>

    </main>

</body>

</html>