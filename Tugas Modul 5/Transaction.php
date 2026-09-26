<?php

declare(strict_types=1);

class Transaction
{
    public function __construct(
        private int $id,
        private string $type,
        private float $amount
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function process(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['balance'])) {
            $_SESSION['balance'] = 0.0;
        }

        if (!isset($_SESSION['transactions'])) {
            $_SESSION['transactions'] = [];
        }

        if ($this->type === 'deposit') {
            $_SESSION['balance'] += $this->amount;
        } elseif ($this->type === 'withdraw') {
            if ($_SESSION['balance'] < $this->amount) {
                return false;
            }

            $_SESSION['balance'] -= $this->amount;
        } else {
            return false;
        }

        $_SESSION['transactions'][] = [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount
        ];

        return true;
    }
}