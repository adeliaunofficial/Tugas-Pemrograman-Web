-- DDL Schema Database & Dummy Data untuk Modul 7

CREATE DATABASE IF NOT EXISTS akademik_db;
USE akademik_db;

CREATE TABLE IF NOT EXISTS dosen (
    nidn VARCHAR(10) PRIMARY KEY,
    nama_dosen VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS mahasiswa (
    nim VARCHAR(10) PRIMARY KEY,
    nama_mhs VARCHAR(100) NOT NULL,
    alamat_mhs TEXT
);

CREATE TABLE IF NOT EXISTS mata_kuliah (
    kode_mk VARCHAR(10) PRIMARY KEY,
    nama_mk VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    nidn VARCHAR(10),
    FOREIGN KEY (nidn)
        REFERENCES dosen(nidn)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS krs (
    nim VARCHAR(10),
    kode_mk VARCHAR(10),
    tanggal_ambil DATE NOT NULL,
    PRIMARY KEY (nim, kode_mk),
    FOREIGN KEY (nim)
        REFERENCES mahasiswa(nim)
        ON DELETE CASCADE,
    FOREIGN KEY (kode_mk)
        REFERENCES mata_kuliah(kode_mk)
        ON DELETE CASCADE
);

-- Insert Data Dummy

INSERT IGNORE INTO dosen
VALUES
    ('D001', 'Prof. Dr. Ir. Saharuddin, M.T.'),
    ('D002', 'Dr. Eng. Armin, S.T., M.T.');

INSERT IGNORE INTO mahasiswa
VALUES
    ('D121241001', 'Rian Diantara', 'Makassar'),
    ('D121241002', 'Muhammad Akbar', 'Gowa');

INSERT IGNORE INTO mata_kuliah
VALUES
    ('INF302', 'Pemrograman Web', 3, 'D001'),
    ('INF301', 'Struktur Data & Algoritma', 4, 'D002');

INSERT IGNORE INTO krs
VALUES
    ('D121241001', 'INF302', '2026-07-17'),
    ('D121241001', 'INF301', '2026-07-17'),
    ('D121241002', 'INF302', '2026-07-17');