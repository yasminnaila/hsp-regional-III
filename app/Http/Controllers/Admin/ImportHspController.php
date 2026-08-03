<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AhspComponent;
use App\Models\BasicItem;
use App\Models\BasicItemPrice;
use App\Models\Category;
use App\Models\Hsp;
use App\Models\HspPrice;
use App\Models\Period;
use App\Models\Region;
use App\Services\AhspCalculationService;
use App\Services\ImportHspService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ImportHspController extends Controller
{
    public function index(): View
    {
        $lastImport = Hsp::query()->max('updated_at');

        return view('admin.import.index', [
            'currentHsp' => Hsp::query()->count(),
            'currentHspPrices' => HspPrice::query()->count(),
            'currentComponents' => AhspComponent::query()->count(),
            'currentBasicItems' => BasicItem::query()->count(),
            'currentBasicPrices' => BasicItemPrice::query()->count(),
            'lastImport' => $lastImport,
            'importHistory' => $this->getImportHistory(),
            'regions' => Region::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, ImportHspService $service): RedirectResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ]);

        try {
            $path = $request->file('file')->getRealPath();
            $year = (int) $request->input('year');

            $result = $service->import($path, $year);

            $this->logImport($request, $result);

            return redirect()
                ->route('admin.import.index')
                ->with(
                    'success',
                    'Import selesai. '
                    . "{$result['hsp']} HSP, "
                    . "{$result['hsp_prices']} harga HSP, "
                    . "{$result['components']} komponen AHS, "
                    . "{$result['basic_items']} item dasar AHS, "
                    . "{$result['basic_item_prices']} harga item AHS, "
                    . "{$result['reference_items_matched']} item referensi cocok, "
                    . "{$result['reference_items_created']} item referensi baru, dan "
                    . "{$result['reference_prices']} harga/referensi wilayah berhasil diproses. "
                    . "HSP tidak ditemukan: {$result['missing_hsp']}. "
                    . "Baris referensi dilewati: {$result['reference_items_skipped']}."
                );
        } catch (Throwable $e) {
            report($e);

            $this->logImport($request, null, $e->getMessage());

            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Import gagal: ' . $e->getMessage(),
                ]);
        }
    }

    public function exportPerAnalisa(Request $request, AhspCalculationService $calculator): Response
    {
        $hspId = $request->integer('hsp_id');
        $regionId = $request->integer('region');

        $hsp = Hsp::findOrFail($hspId);
        if (!$regionId) {
            $regionId = Region::where('is_active', true)->orderBy('sort_order')->value('id');
        }

        $analysis = $calculator->calculate($hsp, $regionId);
        $region = Region::find($regionId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analisa');

        // Title
        $sheet->setCellValue('A1', 'ANALISA HARGA SATUAN PEKERJAAN');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Header info
        $info = [
            ['Pekerjaan', $hsp->description],
            ['Kode', $hsp->work_code],
            ['Satuan', $hsp->unit ?? '-'],
            ['Wilayah', $region?->name ?? '-'],
        ];
        $row = 2;
        foreach ($info as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->setCellValue("B{$row}", $item[1]);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']],
            ]);
            $row++;
        }

        $groups = [
            'labor' => 'A. TENAGA KERJA',
            'material' => 'B. BAHAN',
            'service' => 'C. PERALATAN',
        ];

        $row++; // blank row

        $tableHeaders = ['No', 'Kode', 'Uraian', 'Satuan', 'Koefisien', 'Harga Satuan', 'Jumlah Harga'];
        $lastCol = 'G';

        foreach ($groups as $type => $label) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
            ]);
            $row++;

            $sheet->fromArray([$tableHeaders], null, "A{$row}");
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4B5563']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ]);
            $row++;

            $typeKey = $type === 'service' ? 'equipment' : $type;
            $items = $analysis['groups'][$typeKey] ?? [];

            if (empty($items)) {
                $sheet->setCellValue("A{$row}", 'Belum ada komponen.');
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
                ]);
                $row++;
            } else {
                foreach ($items as $i => $item) {
                    $sheet->fromArray([[
                        $i + 1,
                        $item['code'] ?? '-',
                        $item['description'],
                        $item['unit'],
                        $item['coefficient'],
                        $item['unit_price'],
                        $item['amount'],
                    ]], null, "A{$row}");
                    $row++;
                }
            }

            // Subtotal
            $sheet->setCellValue("F{$row}", 'Jumlah');
            $sheet->setCellValue("G{$row}", $analysis['subtotals'][$typeKey] ?? 0);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF3C7']],
                'borders' => ['top' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D1D5DB']]],
            ]);
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // Summary section
        $row++; // blank row
        $summary = [
            ['D. Jumlah Biaya Langsung (A+B+C)', $analysis['direct_cost']],
            ['E. Overhead & Profit (' . number_format($analysis['overhead_percent'], 2, ',', '.') . '%)', $analysis['overhead_amount']],
            ['F. Harga Satuan Pekerjaan', $analysis['final_price']],
        ];

        foreach ($summary as $i => $s) {
            $sheet->setCellValue("A{$row}", $s[0]);
            $sheet->setCellValue("G{$row}", $s[1]);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $i === 2 ? 'DBEAFE' : 'F9FAFB']],
                'borders' => [
                    'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D1D5DB']],
                ],
            ]);
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            if ($i === 2) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1D4ED8']],
                ]);
            }
            $row++;
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);

        // Borders for data area
        $lastDataRow = $row - 1;
        $sheet->getStyle("A2:G{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']],
            ],
        ]);

        // Number format for price columns
        $sheet->getStyle("F2:G{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E2:E{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.0000');

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        $filename = 'analisa-' . str_replace(['/', '\\', ' '], '-', $hsp->work_code) . '.xlsx';

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportMenyeluruh(): Response
    {
        set_time_limit(300);
        ini_set('memory_limit', '1G');

        $regions = Region::query()->where('is_active', true)->orderBy('sort_order')->get();
        $period = Period::query()
            ->orderByDesc('year')
            ->where('is_active', true)
            ->first() ?? Period::query()->orderByDesc('year')->first();

        $hsps = Hsp::query()
            ->with([
                'category',
                'prices',
                'components' => fn ($query) => $query->orderBy('sort_order'),
                'components.basicItem',
                'components.basicItem.prices' => fn ($query) => $period
                    ? $query->where('period_id', $period->id)
                    : $query,
            ])
            ->get();

        $categories = Category::query()->orderBy('sort_order')->get();
        $sorted = collect();
        foreach ($categories as $category) {
            $list = $hsps->where('category_id', $category->id)->sort(
                fn ($a, $b) => strlen((string) $a->work_code) <=> strlen((string) $b->work_code)
                    ?: strcmp((string) $a->work_code, (string) $b->work_code)
            );
            $sorted = $sorted->merge($list);
        }
        $uncategorized = $hsps->whereNotIn('category_id', $categories->pluck('id'))
            ->sort(fn ($a, $b) => strcmp((string) $a->work_code, (string) $b->work_code));
        $hsps = $sorted->merge($uncategorized)->values();

        $calc = [];
        foreach ($hsps as $hsp) {
            $calc[$hsp->id] = $this->calculateHspRegions($hsp, $regions);
        }

        $spreadsheet = new Spreadsheet();
        $this->buildHspSheet($spreadsheet->getActiveSheet(), $hsps, $regions, $calc);
        $this->buildAhsSheet($spreadsheet->createSheet(), $hsps, $regions, $calc);
        $this->buildUpahBahanAlatSheet($spreadsheet->createSheet(), $regions, $period);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="export-hsp-menyeluruh.xlsx"',
        ]);
    }

    private function regionLabels(): array
    {
        return [
            1 => ['kode' => 'JATENG DIY', 'material' => 'MATERIAL JATENG DIY', 'jasa' => 'JASA JATENG & DIY', 'harga' => 'HARGA JATENG DIY'],
            2 => ['kode' => 'JATIM', 'material' => 'MATERIAL JATIM', 'jasa' => 'JASA JATIM', 'harga' => 'HARGA JATIM'],
            3 => ['kode' => 'BALI', 'material' => 'MATERIAL BALI', 'jasa' => 'JASA BALI', 'harga' => 'HARGA BALI'],
            4 => ['kode' => 'NTB NTT', 'material' => 'MATERIAL NTB NTT', 'jasa' => 'JASA NTB NTT', 'harga' => 'HARGA NTB NTT'],
        ];
    }

    private function roundDown(float $value, int $digits): float
    {
        if ($digits >= 0) {
            $factor = 10 ** $digits;
            return floor($value * $factor) / $factor;
        }
        $factor = 10 ** abs($digits);
        return floor($value / $factor) * $factor;
    }

    private function calculateHspRegions(Hsp $hsp, $regions): array
    {
        $result = [];
        foreach ($regions as $region) {
            $result[$region->id] = ['material' => 0.0, 'jasa' => 0.0];
        }

        foreach ($hsp->components as $comp) {
            $item = $comp->basicItem;
            $type = $item?->item_type;
            if (!in_array($type, ['labor', 'material', 'equipment'], true)) {
                continue;
            }
            foreach ($regions as $region) {
                $price = (float) ($item->prices->firstWhere('region_id', $region->id)?->price ?? 0);
                $amount = (float) $comp->coefficient * $price;
                if ($type === 'material') {
                    $result[$region->id]['material'] += $amount;
                } else {
                    $result[$region->id]['jasa'] += $amount;
                }
            }
        }

        foreach ($result as &$values) {
            $values['harga'] = $values['material'] + $values['jasa'];
        }

        return $result;
    }

    private function regionalCode(Hsp $hsp, $region): string
    {
        return (string) ($hsp->prices->firstWhere('region_id', $region->id)?->regional_code
            ?? 'TR3.' . $region->id . '.' . $hsp->work_code);
    }

    private function buildHspSheet($sheet, $hsps, $regions, $calc): void
    {
        $sheet->setTitle('HSP');
        $labels = $this->regionLabels();

        $sheet->setCellValue('B1', 'DAFTAR HARGA SATUAN PEKERJAAN (DHSP)');
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

        // Row 3: headers
        $headers = ['NO', 'URAIAN PEKERJAAN', 'SATUAN'];
        foreach ($regions as $region) {
            $lbl = $labels[$region->id] ?? null;
            $headers[] = 'KODE ' . ($lbl['kode'] ?? strtoupper($region->name));
            $headers[] = $lbl['material'] ?? 'MATERIAL ' . strtoupper($region->name);
            $headers[] = $lbl['jasa'] ?? 'JASA ' . strtoupper($region->name);
            $headers[] = $lbl['harga'] ?? 'HARGA ' . strtoupper($region->name);
        }
        // U, W kosong (sekat), V=Overhead
        $headers[] = null;
        $headers[] = 'Overhead';
        $headers[] = null;
        // Kolom X..AM (setelah overhead)
        foreach ($regions as $region) {
            $lbl = $labels[$region->id] ?? null;
            $headers[] = 'KODE ' . ($lbl['kode'] ?? strtoupper($region->name));
            $headers[] = $lbl['material'] ?? 'MATERIAL ' . strtoupper($region->name);
            $headers[] = $lbl['jasa'] ?? 'JASA ' . strtoupper($region->name);
            $headers[] = $lbl['harga'] ?? 'HARGA ' . strtoupper($region->name);
        }
        $sheet->fromArray([$headers], null, 'B3');

        // Row 4: nomor kolom
        $row4 = [];
        for ($i = 1; $i <= 19; $i++) {
            $row4[] = $i;
        }
        $row4[] = null; // U
        $row4[] = 0.15; // V overhead
        $row4[] = null; // W
        for ($i = 4; $i <= 19; $i++) {
            $row4[] = $i;
        }
        $sheet->fromArray([$row4], null, 'B4');

        $row = 5;
        $lastDataRow = 4;
        $currentCategory = null;
        foreach ($hsps as $hsp) {
            $categoryId = $hsp->category_id;
            if ($categoryId !== $currentCategory) {
                $currentCategory = $categoryId;
                $sheet->setCellValue('B' . $row, $hsp->category?->code);
                $sheet->setCellValue('C' . $row, $hsp->category?->name);
                $sheet->getStyle('B' . $row . ':AM' . $row)->getFont()->setBold(true);
                $row++;
            }

            $data = [$hsp->work_code, $hsp->description, $hsp->unit ?? ''];
            foreach ($regions as $region) {
                $values = $calc[$hsp->id][$region->id] ?? ['material' => 0.0, 'jasa' => 0.0, 'harga' => 0.0];
                $data[] = $this->regionalCode($hsp, $region);
                $data[] = $this->roundDown((float) $values['material'], 0);
                $data[] = $this->roundDown((float) $values['jasa'], 0);
                $data[] = $this->roundDown((float) $values['harga'], 0);
            }
            $data[] = null; // U
            $data[] = 0.15; // V overhead
            $data[] = null; // W
            foreach ($regions as $region) {
                $values = $calc[$hsp->id][$region->id] ?? ['material' => 0.0, 'jasa' => 0.0, 'harga' => 0.0];
                $data[] = $this->regionalCode($hsp, $region);
                $data[] = $this->roundDown(1.15 * (float) $values['material'], -2);
                $data[] = $this->roundDown(1.15 * (float) $values['jasa'], -2);
                $data[] = $this->roundDown(1.15 * (float) $values['harga'], -2);
            }
            $sheet->fromArray([$data], null, 'B' . $row);
            $row++;
        }
        $lastDataRow = $row - 1;

        // Catatan
        $row++;
        $sheet->setCellValue('B' . $row, 'Catatan :');
        $sheet->setCellValue('B' . ($row + 1), 0.15);
        $sheet->setCellValue('C' . ($row + 1), 'Biaya Umum dan Keuntungan (10% - 15%)');
        $sheet->setCellValue('B' . ($row + 2), 0);
        $sheet->setCellValue('C' . ($row + 2), 'Pembulatan (rounddown)');
        $sheet->setCellValue('B' . ($row + 3), -2);
        $sheet->setCellValue('C' . ($row + 3), 'Pembulatan (rounddown) after Overhead');

        // Styling
        $sheet->getStyle('B3:AM3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('B4:AM4')->applyFromArray([
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle("B3:AM{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'BFBFBF']],
            ],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getStyle("F5:T{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("Y5:AM{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('V5:V' . $lastDataRow)->getNumberFormat()->setFormatCode('0.00');

        $sheet->getColumnDimension('A')->setWidth(1);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(55);
        $sheet->getColumnDimension('D')->setWidth(10);
        foreach (['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(13);
        }
        $sheet->getColumnDimension('U')->setWidth(2);
        $sheet->getColumnDimension('V')->setWidth(10);
        $sheet->getColumnDimension('W')->setWidth(2);
    }

    private function buildAhsSheet($sheet, $hsps, $regions, $calc): void
    {
        $sheet->setTitle('AHS');

        $regionNames = [
            1 => 'JAWA TENGAH dan DI YOGYAKARTA',
            2 => 'JAWA TIMUR',
            3 => 'BALI',
            4 => 'NTB dan NTT',
        ];
        $blockCols = [
            1 => ['mat' => 'G', 'jasa' => 'H', 'jml_mat' => 'I', 'jml_jasa' => 'J', 'jml_harga' => 'K', 'kode' => 'H'],
            2 => ['mat' => 'L', 'jasa' => 'M', 'jml_mat' => 'N', 'jml_jasa' => 'O', 'jml_harga' => 'P', 'kode' => 'M'],
            3 => ['mat' => 'Q', 'jasa' => 'R', 'jml_mat' => 'S', 'jml_jasa' => 'T', 'jml_harga' => 'U', 'kode' => 'R'],
            4 => ['mat' => 'V', 'jasa' => 'W', 'jml_mat' => 'X', 'jml_jasa' => 'Y', 'jml_harga' => 'Z', 'kode' => 'W'],
        ];

        // Row 2 & 3
        $sheet->setCellValue('A2', 'No');
        $sheet->setCellValue('B2', 'KODE BINKON');
        $sheet->setCellValue('C2', 'ANALISA HARGA SATUAN');
        $sheet->setCellValue('A3', 'Reg');
        $sheet->setCellValue('B3', 'Bidang Cipta Karya');
        $sheet->setCellValue('C3', 'PEKERJAAN');
        foreach ($regions as $i => $region) {
            $col = ['G', 'L', 'Q', 'V'][$i] ?? null;
            if (!$col) {
                continue;
            }
            $sheet->setCellValue($col . '2', $regionNames[$region->id] ?? strtoupper($region->name));
            $sheet->setCellValue($col . '3', $i + 1);
        }
        $sheet->getStyle('A2:Z3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $colHeaders = [
            'C' => 'No', 'D' => 'Uraian', 'E' => 'Sat', 'F' => 'Koefisien',
        ];
        foreach ($blockCols as $id => $cols) {
            if (!$regions->contains('id', $id)) {
                continue;
            }
            $colHeaders[$cols['mat']] = 'Material';
            $colHeaders[$cols['jasa']] = 'Jasa';
            $colHeaders[$cols['jml_mat']] = 'Jumlah Material';
            $colHeaders[$cols['jml_jasa']] = 'Jumlah Jasa';
            $colHeaders[$cols['jml_harga']] = 'Jumlah Harga';
        }

        $row = 4;
        $currentCategory = null;
        $seqInCategory = 0;

        foreach ($hsps as $hsp) {
            if ($hsp->category_id !== $currentCategory) {
                $currentCategory = $hsp->category_id;
                $seqInCategory = 0;
                $sheet->setCellValue('C' . $row, $hsp->category?->code);
                $sheet->setCellValue('D' . $row, $hsp->category?->name);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $row++;
            }

            $seqInCategory++;
            $blockStart = $row;

            // Header blok
            $sheet->setCellValue('A' . $row, $seqInCategory);
            $sheet->setCellValue('B' . $row, $hsp->binkon_code ?? '');
            $sheet->setCellValue('C' . $row, $hsp->work_code);
            $sheet->setCellValue('D' . $row, $hsp->description);
            foreach ($regions as $region) {
                $cols = $blockCols[$region->id] ?? null;
                if (!$cols) {
                    continue;
                }
                $values = $calc[$hsp->id][$region->id] ?? ['material' => 0.0, 'jasa' => 0.0, 'harga' => 0.0];
                $sheet->setCellValue($cols['kode'] . $row, $this->regionalCode($hsp, $region));
                $sheet->setCellValue($cols['jml_mat'] . $row, $this->roundDown((float) $values['material'], 0));
                $sheet->setCellValue($cols['jml_jasa'] . $row, $this->roundDown((float) $values['jasa'], 0));
                $sheet->setCellValue($cols['jml_harga'] . $row, $this->roundDown((float) $values['harga'], 0));
            }
            $sheet->getStyle('A' . $row . ':Z' . $row)->getFont()->setBold(true);
            $row++;

            // Header kolom
            foreach ($colHeaders as $col => $label) {
                $sheet->setCellValue($col . $row, $label);
            }
            $sheet->getStyle('C' . $row . ':Z' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']],
                'alignment' => ['horizontal' => 'center'],
            ]);
            $row++;

            // Seksi & komponen
            $sections = [
                ['code' => 'A', 'label' => 'TENAGA KERJA', 'type' => 'labor'],
                ['code' => 'B', 'label' => 'BAHAN', 'type' => 'material'],
                ['code' => 'C', 'label' => 'PERALATAN', 'type' => 'equipment'],
            ];
            $subtotal = []; // per region: mat, jasa, harga
            foreach ($regions as $region) {
                $subtotal[$region->id] = ['mat' => 0.0, 'jasa' => 0.0, 'harga' => 0.0];
            }

            foreach ($sections as $section) {
                $comps = $hsp->components->filter(
                    fn ($comp) => $comp->basicItem?->item_type === $section['type']
                )->values();
                if ($comps->isEmpty()) {
                    continue;
                }

                $sheet->setCellValue('C' . $row, $section['code']);
                $sheet->setCellValue('D' . $row, $section['label']);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $row++;

                $secTotals = [];
                foreach ($regions as $region) {
                    $secTotals[$region->id] = ['mat' => 0.0, 'jasa' => 0.0, 'harga' => 0.0];
                }

                $no = 1;
                foreach ($comps as $comp) {
                    $item = $comp->basicItem;
                    $sheet->setCellValue('C' . $row, $no);
                    $sheet->setCellValue('D' . $row, $item?->description ?? '-');
                    $sheet->setCellValue('E' . $row, $item?->unit ?? '');
                    $sheet->setCellValue('F' . $row, (float) $comp->coefficient);

                    foreach ($regions as $region) {
                        $cols = $blockCols[$region->id] ?? null;
                        if (!$cols) {
                            continue;
                        }
                        $price = (float) ($item->prices->firstWhere('region_id', $region->id)?->price ?? 0);
                        $amount = (float) $comp->coefficient * $price;
                        if ($section['type'] === 'material') {
                            $sheet->setCellValue($cols['mat'] . $row, $price);
                            $sheet->setCellValue($cols['jml_mat'] . $row, $amount);
                            $sheet->setCellValue($cols['jml_harga'] . $row, $amount);
                            $secTotals[$region->id]['mat'] += $amount;
                        } else {
                            $sheet->setCellValue($cols['jasa'] . $row, $price);
                            $sheet->setCellValue($cols['jml_jasa'] . $row, $amount);
                            $sheet->setCellValue($cols['jml_harga'] . $row, $amount);
                            $secTotals[$region->id]['jasa'] += $amount;
                        }
                        $secTotals[$region->id]['harga'] += $amount;
                        $subtotal[$region->id]['mat'] += $section['type'] === 'material' ? $amount : 0.0;
                        $subtotal[$region->id]['jasa'] += $section['type'] !== 'material' ? $amount : 0.0;
                        $subtotal[$region->id]['harga'] += $amount;
                    }
                    $row++;
                    $no++;
                }

                // Subtotal seksi
                $sheet->setCellValue('D' . $row, 'Jumlah ' . $section['label']);
                foreach ($regions as $region) {
                    $cols = $blockCols[$region->id] ?? null;
                    if (!$cols) {
                        continue;
                    }
                    $t = $secTotals[$region->id];
                    if ($section['type'] === 'material') {
                        $sheet->setCellValue($cols['jml_mat'] . $row, $t['mat']);
                        $sheet->setCellValue($cols['jml_harga'] . $row, $t['harga']);
                    } else {
                        $sheet->setCellValue($cols['jml_jasa'] . $row, $t['jasa']);
                        $sheet->setCellValue($cols['jml_harga'] . $row, $t['harga']);
                    }
                }
                $sheet->getStyle('D' . $row)->getFont()->setBold(true);
                $row++;
            }

            // Jumlah (A+B+C)
            $sheet->setCellValue('C' . $row, 'D');
            $sheet->setCellValue('D' . $row, 'Jumlah (A+B+C)');
            foreach ($regions as $region) {
                $cols = $blockCols[$region->id] ?? null;
                if (!$cols) {
                    continue;
                }
                $t = $subtotal[$region->id];
                $sheet->setCellValue($cols['jml_mat'] . $row, $t['mat']);
                $sheet->setCellValue($cols['jml_jasa'] . $row, $t['jasa']);
                $sheet->setCellValue($cols['jml_harga'] . $row, $t['harga']);
            }
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
            $row++;

            // Baris kosong antar blok
            $row++;

            $sheet->getStyle("A{$blockStart}:Z" . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'BFBFBF']],
                ],
                'alignment' => ['vertical' => 'center'],
            ]);
            $sheet->getStyle('C' . ($blockStart + 2) . ':Z' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.0000');
            $sheet->getStyle('C' . ($blockStart + 2) . ':Z' . ($row - 1))->applyFromArray([
                'alignment' => ['horizontal' => 'center'],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(45);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(11);
        foreach (['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(13);
        }
    }

    private function buildUpahBahanAlatSheet($sheet, $regions, $period): void
    {
        $sheet->setTitle('Upah Bahan Alat');

        $sectionMap = $this->materialSectionMap();

        $items = BasicItem::query()
            ->with(['prices' => fn ($query) => $period
                ? $query->where('period_id', $period->id)
                : $query])
            ->orderByRaw("FIELD(item_type, 'labor', 'material', 'equipment', 'dkd')")
            ->orderBy('description')
            ->get();

        $hsLabels = [
            1 => 'HS JATENG DIY',
            2 => 'HS JATIM',
            3 => 'HS BALI',
            4 => 'HS NTB+NTT',
        ];

        $sheet->setCellValue('C1', 'DAFTAR HARGA SATUAN UPAH - BAHAN - ALAT');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal('center');

        // Row 3
        $sheet->setCellValue('C3', 'NO');
        $sheet->setCellValue('D3', 'URAIAN');
        $sheet->setCellValue('E3', 'SATUAN');
        $sheet->setCellValue('F3', 'HARGA SATUAN');
        $sheet->setCellValue('L3', 'HARGA REFRENSI 1');
        $sheet->setCellValue('Q3', 'HARGA REFRENSI 2');

        // Row 4
        $sheet->setCellValue('D4', 'UPAH - BAHAN - ALAT');
        foreach ($regions as $region) {
            $labels = ['F', 'G', 'H', 'I'];
            $labels2 = ['L', 'M', 'N', 'O'];
            $labels3 = ['Q', 'R', 'S', 'T'];
            $i = array_search($region->id, $regions->pluck('id')->all(), true);
            $sheet->setCellValue(($labels[$i] ?? 'F') . '4', $hsLabels[$region->id] ?? 'HS ' . strtoupper($region->name));
            $sheet->setCellValue(($labels2[$i] ?? 'L') . '4', $hsLabels[$region->id] ?? 'HS ' . strtoupper($region->name));
            $sheet->setCellValue(($labels3[$i] ?? 'Q') . '4', $hsLabels[$region->id] ?? 'HS ' . strtoupper($region->name));
        }
        $sheet->setCellValue('J4', 'KETERANGAN');

        // Row 5: nomor kolom
        $row5 = ['C' => 1, 'D' => 2, 'E' => 3, 'F' => 4, 'G' => 5, 'H' => 6, 'I' => 7, 'J' => 8];
        foreach ($regions as $i => $region) {
            $row5[['L', 'M', 'N', 'O'][$i] ?? 'L'] = 4 + $i;
            $row5[['Q', 'R', 'S', 'T'][$i] ?? 'Q'] = 4 + $i;
        }
        foreach ($row5 as $col => $val) {
            $sheet->setCellValue($col . '5', $val);
        }

        $sheet->getStyle('C3:T4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('C5:T5')->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
        ]);

        $row = 6;
        $sections = [
            ['code' => 'A', 'label' => 'UPAH', 'type' => 'labor'],
            ['code' => 'B', 'label' => 'MATERIAL', 'type' => 'material'],
            ['code' => 'XIX', 'label' => 'SEWA PERALATAN', 'type' => 'equipment'],
            ['code' => 'XX', 'label' => 'DKD', 'type' => 'dkd'],
        ];

        $lastDataRow = 5;

        foreach ($sections as $section) {
            $sectionItems = $items->where('item_type', $section['type'])->values();
            if ($sectionItems->isEmpty()) {
                continue;
            }

            // Sub-seksi material
            $groups = [$sectionItems];
            $groupCodes = [$section['code']];
            $groupLabels = [$section['label']];
            if ($section['type'] === 'material' && !empty($sectionMap)) {
                $groups = [];
                $groupCodes = [];
                $groupLabels = [];
                $ordered = ['I' => 'MATERIAL TANAH DAN BATUAN', 'V' => 'MATERIAL BESI DAN BAJA', 'X' => 'MATERIAL CAT', 'XVIII' => 'MATERIAL LAIN-LAIN'];
                foreach ($ordered as $code => $label) {
                    $list = $sectionItems->filter(fn ($item) => ($sectionMap[trim(mb_strtolower((string) $item->description))] ?? 'XVIII') === $code)->values();
                    if ($list->isNotEmpty()) {
                        $groups[] = $list;
                        $groupCodes[] = $code;
                        $groupLabels[] = $label;
                    }
                }
            }

            if ($section['type'] === 'material' && count($groups) > 1) {
                $sheet->setCellValue('C' . $row, 'B');
                $sheet->setCellValue('D' . $row, 'MATERIAL');
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $row++;
            }

            foreach ($groups as $gi => $list) {
                $sheet->setCellValue('C' . $row, $groupCodes[$gi]);
                $sheet->setCellValue('D' . $row, $groupLabels[$gi]);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $row++;

                $no = 1;
                foreach ($list as $item) {
                    $data = [$no, $item->description, $item->unit ?? ''];
                    foreach ($regions as $region) {
                        $p = $item->prices->firstWhere('region_id', $region->id);
                        $data[] = (float) ($p?->price ?? 0);
                    }
                    $data[] = null; // J KETERANGAN
                    $data[] = null; // K (sekat)
                    foreach ($regions as $region) {
                        $p = $item->prices->firstWhere('region_id', $region->id);
                        $data[] = $p?->reference_price_1;
                    }
                    $data[] = null; // P (sekat)
                    foreach ($regions as $region) {
                        $p = $item->prices->firstWhere('region_id', $region->id);
                        $data[] = $p?->reference_price_2;
                    }
                    $sheet->fromArray([$data], null, 'C' . $row);
                    $row++;
                    $no++;
                }
            }
        }
        $lastDataRow = $row - 1;

        $sheet->getStyle("C6:T{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'BFBFBF']],
            ],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getStyle("F6:I{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("L6:T{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('C6:C' . $lastDataRow)->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
        ]);
        $sheet->getStyle('E6:E' . $lastDataRow)->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
        ]);

        $sheet->getColumnDimension('C')->setWidth(7);
        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getColumnDimension('E')->setWidth(8);
        foreach (['F', 'G', 'H', 'I'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(13);
        }
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(2);
        foreach (['L', 'M', 'N', 'O'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(13);
        }
        $sheet->getColumnDimension('P')->setWidth(2);
        foreach (['Q', 'R', 'S', 'T'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(13);
        }
    }

    private function materialSectionMap(): array
    {
        $path = 'C:\Users\L\OneDrive\SEMESTER 6\MAGANG\AHSP 2026 Regional III.xlsx';
        $map = [];

        try {
            if (!file_exists($path)) {
                return $map;
            }
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['Upah Bahan Alat']);
            $spreadsheet = $reader->load($path);
            $ws = $spreadsheet->getSheetByName('Upah Bahan Alat');
            if (!$ws) {
                return $map;
            }

            $current = 'XVIII';
            for ($r = 38; $r <= 1882; $r++) {
                $sec = trim((string) $ws->getCell('C' . $r)->getValue());
                $desc = trim((string) $ws->getCell('D' . $r)->getValue());
                if ($desc === '') {
                    continue;
                }
                if (preg_match('/^[IVX]+$/', $sec) && !ctype_digit($desc)) {
                    $current = $sec;
                    continue;
                }
                $map[trim(mb_strtolower($desc))] = $current;
            }
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $e) {
            report($e);
        }

        return $map;
    }

    private function styleSheet($sheet, string $headerRange, string $dataRange, string $lastCol, array $centerCols, array $numberCols): void
    {
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['bottom' => ['borderStyle' => 'thin', 'color' => ['rgb' => '1D4ED8']]],
        ]);

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D1D5DB']],
            ],
            'alignment' => ['vertical' => 'center'],
        ]);

        foreach ($centerCols as $i) {
            $col = chr(65 + $i);
            $sheet->getStyle("{$col}2:{$col}9999")->applyFromArray([
                'alignment' => ['horizontal' => 'center'],
            ]);
        }

        foreach ($numberCols as $i) {
            $col = chr(65 + $i);
            $sheet->getStyle("{$col}2:{$col}9999")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->setAutoFilter($dataRange);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(6);
        $colCount = ord($lastCol) - 65 + 1;
        for ($i = 1; $i < $colCount; $i++) {
            $width = 40;
            if (in_array($i, $numberCols, true)) {
                $width = 16;
            } elseif (in_array($i, $centerCols, true)) {
                $width = 14;
            }
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($width);
        }
    }

    private function getImportHistory(): array
    {
        $logFile = storage_path('logs/import_history.json');
        if (!file_exists($logFile)) {
            return [];
        }

        $logs = json_decode(file_get_contents($logFile), true);
        if (!is_array($logs)) {
            return [];
        }

        return array_slice($logs, 0, 20);
    }

    private function logImport(Request $request, ?array $result, ?string $error = null): void
    {
        $logFile = storage_path('logs/import_history.json');
        $logs = [];

        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true);
            if (!is_array($logs)) {
                $logs = [];
            }
        }

        $file = $request->file('file');

        $entry = [
            'file_name' => $file ? $file->getClientOriginalName() : 'unknown',
            'file_size' => $file ? $file->getSize() : 0,
            'year' => (int) $request->input('year'),
            'total_data' => $result ? array_sum($result) : 0,
            'status' => $error ? 'gagal' : 'berhasil',
            'created_at' => now()->toIso8601String(),
        ];

        array_unshift($logs, $entry);
        $logs = array_slice($logs, 0, 100);

        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
