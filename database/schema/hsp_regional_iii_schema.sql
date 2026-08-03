-- Sistem Informasi Harga Satuan Pekerjaan (HSP) Konstruksi Umum Regional III
-- Target: MySQL 8+
-- Mapping workbook AHSP 2026 Regional III.xlsx yang diverifikasi:
-- Sheet HSP: NO, URAIAN PEKERJAAN, SATUAN, lalu KODE/MATERIAL/JASA/HARGA untuk 4 wilayah.
-- Sheet AHS: KODE BINKON, kode pekerjaan umum, detail TENAGA KERJA/BAHAN/PERALATAN, koefisien, dan harga per wilayah.
-- Sheet Upah Bahan Alat: item UPAH/BAHAN/ALAT dan HS JATENG DIY/JATIM/BALI/NTB+NTT.
-- Sheet Kode: 16 group pekerjaan dan kode group wilayah 1-4.

CREATE DATABASE IF NOT EXISTS hsp_regional_iii
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hsp_regional_iii;

-- ============================================================
-- 1. USERS / AUTENTIKASI
-- ============================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. PERIODE / TAHUN DATA
-- ============================================================
CREATE TABLE periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_period_year (year)
) ENGINE=InnoDB;

-- ============================================================
-- 3. WILAYAH / REGIONAL HARGA
-- ============================================================
CREATE TABLE regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 4. KATEGORI PEKERJAAN
-- ============================================================
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 5. MASTER HSP
-- Satu kode pekerjaan disimpan sekali per periode.
-- Harga tiap wilayah berada pada tabel hsp_prices.
-- ============================================================
CREATE TABLE hsp (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    work_code VARCHAR(100) NOT NULL COMMENT 'Kode umum dari kolom NO, contoh I.1',
    binkon_code VARCHAR(100) NULL COMMENT 'Kode BINKON dari sheet AHS, contoh 1.1.1.2',
    description TEXT NOT NULL,
    unit VARCHAR(50) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hsp_period FOREIGN KEY (period_id) REFERENCES periods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_hsp_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uq_hsp_period_work_code (period_id, work_code),
    KEY idx_hsp_binkon_code (binkon_code),
    KEY idx_hsp_category (category_id),
    FULLTEXT KEY ft_hsp_search (work_code, binkon_code, description)
) ENGINE=InnoDB;

-- ============================================================
-- 6. HARGA HSP PER WILAYAH
-- material + jasa mengikuti struktur tabel HSP sumber.
-- harga_satuan merupakan nilai final/snapshot yang ditampilkan.
-- ============================================================
CREATE TABLE hsp_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hsp_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    regional_code VARCHAR(100) NOT NULL COMMENT 'Contoh TR3.1.I.1; dapat berulang pada periode berbeda',
    material DECIMAL(18,2) NOT NULL DEFAULT 0,
    service DECIMAL(18,2) NOT NULL DEFAULT 0,
    price DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hsp_prices_hsp FOREIGN KEY (hsp_id) REFERENCES hsp(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_hsp_prices_region FOREIGN KEY (region_id) REFERENCES regions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_hsp_price_region (hsp_id, region_id),
    KEY idx_hsp_regional_code (regional_code),
    KEY idx_hsp_prices_region (region_id),
    KEY idx_hsp_prices_price (price)
) ENGINE=InnoDB;

-- ============================================================
-- 7. MASTER ITEM UPAH / BAHAN / ALAT
-- Item disimpan sekali; harga dipisahkan per periode dan wilayah.
-- ============================================================
CREATE TABLE basic_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NULL COMMENT 'Kode item/upah/bahan/alat dari sumber bila tersedia',
    source_no VARCHAR(50) NULL COMMENT 'Nomor sumber pada sheet Upah Bahan Alat; tidak selalu unik',
    item_type ENUM('labor','material','equipment') NOT NULL,
    description VARCHAR(500) NOT NULL,
    unit VARCHAR(50) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_basic_items_code (code),
    KEY idx_basic_items_type (item_type),
    KEY idx_basic_items_lookup (item_type, unit),
    FULLTEXT KEY ft_basic_items_search (description)
) ENGINE=InnoDB;

-- ============================================================
-- 8. HARGA DASAR ITEM PER PERIODE DAN WILAYAH
-- ============================================================
CREATE TABLE basic_item_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    basic_item_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    price DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_basic_prices_item FOREIGN KEY (basic_item_id) REFERENCES basic_items(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_basic_prices_period FOREIGN KEY (period_id) REFERENCES periods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_basic_prices_region FOREIGN KEY (region_id) REFERENCES regions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_basic_price (basic_item_id, period_id, region_id),
    KEY idx_basic_prices_period_region (period_id, region_id)
) ENGINE=InnoDB;

-- ============================================================
-- 9. DETAIL ANALISA HARGA SATUAN PEKERJAAN (AHSP)
-- Koefisien menghubungkan HSP dengan item dasar.
-- Harga ditarik dari basic_item_prices sesuai periode + wilayah.
-- ============================================================
CREATE TABLE ahsp_components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hsp_id BIGINT UNSIGNED NOT NULL,
    basic_item_id BIGINT UNSIGNED NOT NULL,
    coefficient DECIMAL(20,8) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ahsp_hsp FOREIGN KEY (hsp_id) REFERENCES hsp(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ahsp_basic_item FOREIGN KEY (basic_item_id) REFERENCES basic_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    KEY idx_ahsp_component (hsp_id, basic_item_id),
    KEY idx_ahsp_hsp_sort (hsp_id, sort_order)
) ENGINE=InnoDB;

-- ============================================================
-- 10. PARAMETER ANALISA PER HSP/WILAYAH
-- Menyimpan overhead/profit sebagai snapshot untuk periode harga.
-- ============================================================
CREATE TABLE ahsp_parameters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hsp_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    overhead_profit_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ahsp_params_hsp FOREIGN KEY (hsp_id) REFERENCES hsp(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ahsp_params_region FOREIGN KEY (region_id) REFERENCES regions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_ahsp_param (hsp_id, region_id)
) ENGINE=InnoDB;

-- ============================================================
-- 11. RIWAYAT IMPORT EXCEL
-- ============================================================
CREATE TABLE import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NULL,
    status ENUM('uploaded','validating','failed','completed') NOT NULL DEFAULT 'uploaded',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    success_rows INT UNSIGNED NOT NULL DEFAULT 0,
    failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
    error_summary TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_period FOREIGN KEY (period_id) REFERENCES periods(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_import_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 12. LOG AKTIVITAS ADMIN (opsional tetapi direkomendasikan)
-- ============================================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    KEY idx_activity_entity (entity_type, entity_id),
    KEY idx_activity_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL: PERIODE, WILAYAH, KATEGORI
-- ============================================================
INSERT INTO periods (year, name, is_active)
VALUES (2026, 'AHSP Tahun 2026', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO regions (code, name, sort_order) VALUES
('JATENG_DIY', 'Jateng & DIY', 1),
('JATIM', 'Jawa Timur', 2),
('BALI', 'Bali', 3),
('NTB_NTT', 'NTB & NTT', 4)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order);

INSERT INTO categories (code, name, sort_order) VALUES
('I', 'Pekerjaan Persiapan', 1),
('II', 'Pekerjaan Tanah', 2),
('III', 'Pekerjaan Pondasi', 3),
('IV', 'Pekerjaan Beton', 4),
('V', 'Pekerjaan Baja', 5),
('VI', 'Pekerjaan Dinding', 6),
('VII', 'Pekerjaan Kusen, Pintu dan Jendela', 7),
('VIII', 'Pekerjaan Plafon', 8),
('IX', 'Pekerjaan Lantai', 9),
('X', 'Pekerjaan Atap', 10),
('XI', 'Pekerjaan Elektrikal', 11),
('XII', 'Pekerjaan Mekanikal', 12),
('XIII', 'Pekerjaan Sanitasi', 13),
('XIV', 'Pekerjaan Pengecatan', 14),
('XV', 'Pekerjaan Perkerasan', 15),
('XVI', 'Pekerjaan Lain-lain', 16)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order);

-- ============================================================
-- VIEW UNTUK DAFTAR HSP YANG DITAMPILKAN KE USER
-- ============================================================
CREATE OR REPLACE VIEW v_hsp_prices AS
SELECT
    h.id AS hsp_id,
    p.year,
    c.code AS category_code,
    c.name AS category_name,
    h.work_code,
    h.binkon_code,
    h.description,
    h.unit,
    r.id AS region_id,
    r.code AS region_code,
    r.name AS region_name,
    hp.regional_code,
    hp.material,
    hp.service,
    hp.price
FROM hsp h
JOIN periods p ON p.id = h.period_id
LEFT JOIN categories c ON c.id = h.category_id
JOIN hsp_prices hp ON hp.hsp_id = h.id
JOIN regions r ON r.id = hp.region_id
WHERE h.is_active = TRUE AND r.is_active = TRUE;

-- ============================================================
-- QUERY CONTOH DETAIL AHSP UNTUK SATU HSP + WILAYAH
-- :hsp_id dan :region_id diganti binding dari aplikasi.
-- ============================================================
-- SELECT
--     bi.item_type,
--     bi.code,
--     bi.description,
--     bi.unit,
--     ac.coefficient,
--     bip.price AS unit_price,
--     (ac.coefficient * bip.price) AS amount
-- FROM ahsp_components ac
-- JOIN hsp h ON h.id = ac.hsp_id
-- JOIN basic_items bi ON bi.id = ac.basic_item_id
-- JOIN basic_item_prices bip
--      ON bip.basic_item_id = bi.id
--     AND bip.period_id = h.period_id
--     AND bip.region_id = :region_id
-- WHERE ac.hsp_id = :hsp_id
-- ORDER BY FIELD(bi.item_type, 'labor','material','equipment'), ac.sort_order;
