# Perancangan ERD E-Library Kampus

## 1. Deskripsi Sistem

E-Library Kampus merupakan sistem basis data yang digunakan untuk
mengelola informasi mahasiswa, buku, penerbit, serta transaksi
peminjaman dan pengembalian buku.

Sistem dirancang menggunakan model basis data relasional dengan tujuan
mengurangi redundansi data, menjaga integritas referensial, dan
menghindari anomali sisip, hapus, serta pembaruan.

## 2. Entitas Utama

Entitas yang digunakan dalam sistem adalah:

1. Mahasiswa
2. Buku
3. Penerbit
4. Transaksi Peminjaman
5. Detail Peminjaman

Detail Peminjaman digunakan sebagai tabel penghubung antara transaksi
peminjaman dan buku karena satu transaksi dapat memuat lebih dari satu
buku.

## 3. Tujuan Perancangan

Perancangan basis data ini bertujuan untuk:

- Menyimpan data mahasiswa secara terstruktur.
- Menyimpan informasi buku dan penerbit.
- Mencatat transaksi peminjaman dan pengembalian.
- Menjaga hubungan antarentitas melalui Primary Key dan Foreign Key.
- Mengurangi redundansi melalui proses normalisasi hingga 3NF.

## 4. Simulasi Normalisasi

### 4.1 Unnormalized Form (UNF)

Pada bentuk awal, seluruh informasi peminjaman dicatat dalam satu
struktur besar. Satu transaksi dapat berisi lebih dari satu buku sehingga
data buku disimpan sebagai kelompok berulang dalam satu baris.

Contoh:

| ID Peminjaman | NIM | Nama Mahasiswa | Tanggal Pinjam | Daftar Buku |
|---|---|---|---|---|
| PJ001 | MHS001 | Andi | 2026-09-01 | {(B001, Pemrograman Web, P001, Informatika Press), (B002, Basis Data, P002, Data Media)} |
| PJ002 | MHS002 | Siti | 2026-09-02 | {(B003, Jaringan Komputer, P001, Informatika Press)} |

Masalah pada bentuk UNF:

- Satu kolom dapat berisi lebih dari satu nilai.
- Terdapat kelompok data buku yang berulang.
- Data penerbit dapat ditulis berulang untuk beberapa buku.
- Struktur sulit diproses secara konsisten menggunakan operasi relasional.

### 4.2 First Normal Form (1NF)

Pada tahap 1NF, setiap atribut harus memiliki nilai yang atomik dan
kelompok data berulang harus dihilangkan.

Setiap buku pada satu transaksi sekarang dicatat pada baris tersendiri.

| ID Peminjaman | NIM | Nama Mahasiswa | Tanggal Pinjam | ID Buku | Judul Buku | ID Penerbit | Nama Penerbit | Tanggal Kembali |
|---|---|---|---|---|---|---|---|---|
| PJ001 | MHS001 | Andi | 2026-09-01 | B001 | Pemrograman Web | P001 | Informatika Press | 2026-09-08 |
| PJ001 | MHS001 | Andi | 2026-09-01 | B002 | Basis Data | P002 | Data Media | 2026-09-08 |
| PJ002 | MHS002 | Siti | 2026-09-02 | B003 | Jaringan Komputer | P001 | Informatika Press | 2026-09-09 |

Kunci utama sementara dapat menggunakan kombinasi:

**ID Peminjaman + ID Buku**

Pada tahap ini data sudah atomik, tetapi masih terdapat redundansi.
Informasi mahasiswa dan transaksi diulang ketika satu transaksi memiliki
lebih dari satu buku.

Masih terdapat ketergantungan parsial, karena beberapa atribut hanya
bergantung pada sebagian dari kunci gabungan.

Contoh:

- Nama Mahasiswa bergantung pada NIM.
- Tanggal Pinjam bergantung pada ID Peminjaman.
- Judul Buku bergantung pada ID Buku.
- Nama Penerbit bergantung pada ID Penerbit.

Karena masih terdapat ketergantungan parsial, struktur ini belum
memenuhi 2NF.

### 4.3 Second Normal Form (2NF)

Pada tahap 2NF, struktur 1NF dipisahkan berdasarkan ketergantungan
fungsional agar tidak ada atribut yang hanya bergantung pada sebagian
kunci utama.

Dari tabel 1NF, ditemukan beberapa ketergantungan parsial:

- Nama Mahasiswa bergantung pada NIM.
- Nama Buku bergantung pada ID Buku.
- Nama Penerbit bergantung pada ID Penerbit.
- Tanggal Pinjam bergantung pada ID Peminjaman.

Oleh karena itu, data dipisahkan menjadi beberapa tabel.

#### Tabel Mahasiswa

| NIM | Nama Mahasiswa |
|---|---|
| MHS001 | Andi |
| MHS002 | Siti |

Primary Key: **NIM**

#### Tabel Buku

| ID Buku | Judul Buku | ID Penerbit |
|---|---|---|
| B001 | Pemrograman Web | P001 |
| B002 | Basis Data | P002 |
| B003 | Jaringan Komputer | P001 |

Primary Key: **ID Buku**

#### Tabel Peminjaman

| ID Peminjaman | NIM | Tanggal Pinjam | Tanggal Kembali |
|---|---|---|---|
| PJ001 | MHS001 | 2026-09-01 | 2026-09-08 |
| PJ002 | MHS002 | 2026-09-02 | 2026-09-09 |

Primary Key: **ID Peminjaman**

#### Tabel Detail Peminjaman

| ID Peminjaman | ID Buku |
|---|---|
| PJ001 | B001 |
| PJ001 | B002 |
| PJ002 | B003 |

Primary Key: **ID Peminjaman + ID Buku**

Pada tahap ini, atribut yang sebelumnya bergantung hanya pada sebagian
kunci komposit telah dipindahkan ke tabel yang sesuai.

Namun, masih terdapat ketergantungan transitif pada data penerbit.
Nama Penerbit masih bergantung pada ID Penerbit yang berada pada tabel
Buku. Oleh karena itu, struktur ini masih perlu dinormalisasi ke 3NF.

### 4.4 Third Normal Form (3NF)

Pada tahap 3NF, ketergantungan transitif dihilangkan. Pada tahap 2NF,
informasi penerbit masih berada pada tabel Buku sehingga nama penerbit
bergantung pada ID Penerbit, bukan langsung pada ID Buku.

Untuk menghilangkan ketergantungan tersebut, informasi penerbit
dipisahkan menjadi tabel tersendiri.

#### Tabel Mahasiswa

| NIM | Nama Mahasiswa |
|---|---|
| MHS001 | Andi |
| MHS002 | Siti |

Primary Key: **NIM**

#### Tabel Penerbit

| ID Penerbit | Nama Penerbit |
|---|---|
| P001 | Informatika Press |
| P002 | Data Media |

Primary Key: **ID Penerbit**

#### Tabel Buku

| ID Buku | Judul Buku | ID Penerbit |
|---|---|---|
| B001 | Pemrograman Web | P001 |
| B002 | Basis Data | P002 |
| B003 | Jaringan Komputer | P001 |

Primary Key: **ID Buku**

Foreign Key: **ID Penerbit** - Tabel Penerbit

#### Tabel Peminjaman

| ID Peminjaman | NIM | Tanggal Pinjam | Tanggal Kembali |
|---|---|---|---|
| PJ001 | MHS001 | 2026-09-01 | 2026-09-08 |
| PJ002 | MHS002 | 2026-09-02 | 2026-09-09 |

Primary Key: **ID Peminjaman**

Foreign Key: **NIM** - Tabel Mahasiswa

#### Tabel Detail Peminjaman

| ID Peminjaman | ID Buku |
|---|---|
| PJ001 | B001 |
| PJ001 | B002 |
| PJ002 | B003 |

Primary Key: **ID Peminjaman + ID Buku**

Foreign Key:

- **ID Peminjaman** - Tabel Peminjaman
- **ID Buku** - Tabel Buku

Dengan pemisahan tersebut, nama penerbit tidak lagi disimpan berulang
pada setiap data buku. Setiap informasi disimpan pada tabel yang sesuai
dan dihubungkan menggunakan Foreign Key.

Hasil akhir telah memenuhi 3NF karena tidak terdapat ketergantungan
transitif antar atribut non-kunci.

## 5. Rancangan Tabel Akhir

Setelah proses normalisasi hingga 3NF, diperoleh lima tabel utama yang
saling berhubungan melalui Primary Key dan Foreign Key.

### 5.1 Tabel `mahasiswa`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| nim | VARCHAR(10) | Primary Key |
| nama_mhs | VARCHAR(100) | Nama mahasiswa |
| alamat_mhs | TEXT | Alamat mahasiswa |

### 5.2 Tabel `penerbit`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_penerbit | VARCHAR(10) | Primary Key |
| nama_penerbit | VARCHAR(100) | Nama penerbit |

### 5.3 Tabel `buku`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_buku | VARCHAR(10) | Primary Key |
| judul_buku | VARCHAR(150) | Judul buku |
| id_penerbit | VARCHAR(10) | Foreign Key → penerbit.id_penerbit |
| tahun_terbit | YEAR | Tahun terbit buku |
| stok | INT | Jumlah buku yang tersedia |

### 5.4 Tabel `peminjaman`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_peminjaman | VARCHAR(10) | Primary Key |
| nim | VARCHAR(10) | Foreign Key → mahasiswa.nim |
| tanggal_pinjam | DATE | Tanggal peminjaman |
| tanggal_kembali | DATE | Tanggal pengembalian |
| status_peminjaman | VARCHAR(20) | Status pengembalian |

### 5.5 Tabel `detail_peminjaman`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_peminjaman | VARCHAR(10) | Primary Key, Foreign Key → peminjaman.id_peminjaman |
| id_buku | VARCHAR(10) | Primary Key, Foreign Key → buku.id_buku |
| jumlah | INT | Jumlah buku yang dipinjam |

Primary Key pada tabel `detail_peminjaman` merupakan gabungan
`id_peminjaman` dan `id_buku`. Dengan demikian, satu buku tidak dapat
dicatat dua kali dalam detail untuk transaksi peminjaman yang sama.

## 6. Relasi Antarentitas

Relasi antar tabel dirancang sebagai berikut:

- Satu mahasiswa dapat memiliki banyak transaksi peminjaman.
- Satu transaksi peminjaman dapat memiliki banyak detail peminjaman.
- Satu buku dapat muncul pada banyak detail peminjaman.
- Satu penerbit dapat menerbitkan banyak buku.

Relasi tersebut dapat digambarkan sebagai:

```text
MAHASISWA
   │
   │ 1 : N
   ▼
PEMINJAMAN
   │
   │ 1 : N
   ▼
DETAIL_PEMINJAMAN
   ▲                │
   │                │ N : 1
   │                ▼
   │               BUKU
   │                │
   │                │ N : 1
   │                ▼
   └────────────── PENERBIT

   
### Satu catatan penting

Aku sengaja memakai **5 tabel**, bukan cuma 4 entitas yang disebut di soal, karena `Detail_Peminjaman` diperlukan untuk menangani **satu transaksi yang dapat memuat beberapa buku** tanpa membuat kelompok data berulang. Ini konsisten dengan prinsip 1NF–3NF yang dijelaskan di Modul 6. :contentReference[oaicite:1]{index=1}

Jadi file kita sekarang sudah punya urutan:

```text
1. Deskripsi Sistem
2. Entitas Utama
3. Tujuan Perancangan
4. Normalisasi
   ── UNF
   ── 1NF
   ── 2NF
   ── 3NF
5. Rancangan Tabel Akhir
6. Relasi Antarentitas
7. Diagram ERD Mermaid
8. Kesimpulan