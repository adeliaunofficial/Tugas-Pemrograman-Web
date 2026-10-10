-- Hands On Modul 6
-- Perancangan Database KRS Akademik Ternormalisasi 3NF

CREATE DATABASE IF NOT EXISTS akademik_db;

USE akademik_db;


-- 1. Tabel Dosen

CREATE TABLE IF NOT EXISTS dosen (
    nidn VARCHAR(10) PRIMARY KEY,
    nama_dosen VARCHAR(100) NOT NULL
);


-- 2. Tabel Mahasiswa

CREATE TABLE IF NOT EXISTS mahasiswa (
    nim VARCHAR(10) PRIMARY KEY,
    nama_mhs VARCHAR(100) NOT NULL,
    alamat_mhs TEXT
);


-- 3. Tabel Mata Kuliah

CREATE TABLE IF NOT EXISTS mata_kuliah (
    kode_mk VARCHAR(10) PRIMARY KEY,
    nama_mk VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    nidn VARCHAR(10),

    FOREIGN KEY (nidn)
        REFERENCES dosen(nidn)
        ON DELETE SET NULL
);


-- 4. Tabel KRS

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