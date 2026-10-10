<?php

declare(strict_types=1);

class GuestBook
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function addMessage(
        string $nama,
        string $email,
        string $pesan
    ): bool {
        $sql = "
            INSERT INTO buku_tamu (nama, email, pesan)
            VALUES (:nama, :email, :pesan)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':pesan' => $pesan
        ]);
    }

    public function getMessages(): array
    {
        $sql = "
            SELECT id, nama, email, pesan, tanggal_kirim
            FROM buku_tamu
            ORDER BY tanggal_kirim DESC, id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}