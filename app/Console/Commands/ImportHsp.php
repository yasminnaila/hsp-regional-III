<?php

namespace App\Console\Commands;

use App\Services\ImportHspService;
use Illuminate\Console\Command;

class ImportHsp extends Command
{
    protected $signature = 'import:hsp {file : Path ke file Excel} {year? : Tahun periode}';
    protected $description = 'Import data HSP, AHS, dan Upah Bahan Alat dari file Excel';

    public function handle(ImportHspService $service): int
    {
        $filePath = $this->argument('file');
        $year = (int) ($this->argument('year') ?? date('Y'));

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Mengimport file: {$filePath}");
        $this->info("Tahun: {$year}");
        $this->newLine();

        $bar = $this->output->createProgressBar(4);
        $bar->setFormat(' %current%/%max% [%bar%] %message%');
        $bar->setMessage('Memulai import...');
        $bar->start();

        try {
            $bar->setMessage('Import HSP...');
            $bar->advance();

            $result = $service->import($filePath, $year);

            $bar->setMessage('Import selesai!');
            $bar->finish();
            $this->newLine(2);

            $this->table(
                ['Item', 'Jumlah'],
                [
                    ['HSP', $result['hsp']],
                    ['Harga HSP', $result['hsp_prices']],
                    ['Komponen AHS', $result['components']],
                    ['Item Dasar AHS', $result['basic_items']],
                    ['Harga Item AHS', $result['basic_item_prices']],
                    ['Item Referensi Cocok', $result['reference_items_matched']],
                    ['Item Referensi Baru', $result['reference_items_created']],
                    ['Harga/Referensi', $result['reference_prices']],
                    ['HSP Tidak Ditemukan', $result['missing_hsp']],
                    ['Baris Dilewati', $result['reference_items_skipped']],
                ]
            );

            $this->info('Import berhasil!');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Import gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
