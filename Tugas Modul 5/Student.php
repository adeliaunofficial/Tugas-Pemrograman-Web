<?php

declare(strict_types=1);

class Student
{
    public function __construct(
        private string $nim,
        private string $name,
        private string $email
    ) {
    }

    public function getNim(): string
    {
        return $this->nim;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function saveToSession(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['registered_students'][] = [
            'nim'   => $this->nim,
            'name'  => $this->name,
            'email' => $this->email
        ];

        return true;
    }
}