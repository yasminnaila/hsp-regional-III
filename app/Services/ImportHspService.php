<?php

namespace App\Services;

use App\Models\AhspComponent;
use App\Models\BasicItem;
use App\Models\Category;
use App\Models\Hsp;
use App\Models\Period;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportHspService
{
    public function import(string $filePath, int $year): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([
            'HSP',
            'AHS',
            'Upah Bahan Alat',
        ]);

        $spreadsheet = $reader->load($filePath);

        $hspSheet = $spreadsheet->getSheetByName('HSP');
        $ahsSheet = $spreadsheet->getSheetByName('AHS');
        $basicItemSheet = $spreadsheet->getSheetByName('Upah Bahan Alat');

        if (!$hspSheet || !$ahsSheet || !$basicItemSheet) {
            throw new \RuntimeException('Sheet HSP, AHS, atau Upah Bahan Alat tidak ditemukan.');
        }

        $period = Period::query()->updateOrCreate(
            ['year' => $year],
            ['name' => 'AHSP Tahun ' . $year, 'is_active' => true]
        );

        $requiredRegionCodes = ['JATENG_DIY', 'JATIM', 'BALI', 'NTB_NTT'];

        $regions = Region::query()
            ->whereIn('code', $requiredRegionCodes)
            ->get()
            ->keyBy('code');

        foreach ($requiredRegionCodes as $code) {
            if (!$regions->has($code)) {
                throw new \RuntimeException("Master wilayah {$code} belum tersedia. Jalankan seeder terlebih dahulu.");
            }
        }

        $result = DB::transaction(function () use ($hspSheet, $ahsSheet, $basicItemSheet, $period, $regions): array {
            $hspResult = $this->importHspSheet($hspSheet, $period, $regions);
            $ahsResult = $this->importAhsSheet($ahsSheet, $period, $regions);
            $referenceResult = $this->importBasicItemReferenceSheet($basicItemSheet, $period, $regions);

            return array_merge($hspResult, $ahsResult, $referenceResult);
        });

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $result;
    }

    private function importHspSheet(Worksheet $sheet, Period $period, $regions): array
    {
        $regionMap = [
            'JATENG_DIY' => ['regional_code' => 'E', 'material' => 'F', 'service' => 'G', 'direct_price' => 'H', 'final_price' => 'AA'],
            'JATIM' => ['regional_code' => 'I', 'material' => 'J', 'service' => 'K', 'direct_price' => 'L', 'final_price' => 'AE'],
            'BALI' => ['regional_code' => 'M', 'material' => 'N', 'service' => 'O', 'direct_price' => 'P', 'final_price' => 'AI'],
            'NTB_NTT' => ['regional_code' => 'Q', 'material' => 'R', 'service' => 'S', 'direct_price' => 'T', 'final_price' => 'AM'],
        ];

        $currentCategoryId = null;
        $importedHsp = 0;
        $importedPrices = 0;

        for ($row = 5; $row <= $sheet->getHighestDataRow(); $row++) {
            $workCode = $this->text($this->cellValue($sheet, "B{$row}"));
            $description = $this->text($this->cellValue($sheet, "C{$row}"));
            $unit = $this->text($this->cellValue($sheet, "D{$row}"));

            if ($workCode === '') {
                continue;
            }

            if (preg_match('/^[IVXLCDM]+$/', $workCode) && $unit === '') {
                $category = Category::query()->where('code', $workCode)->first();
                $currentCategoryId = $category?->id;
                continue;
            }

            $firstRegionalCode = $this->text($this->cellValue($sheet, "E{$row}"));

            if ($description === '' || !str_starts_with($firstRegionalCode, 'TR3.')) {
                continue;
            }

            if (!$currentCategoryId) {
                $categoryCode = explode('.', $workCode)[0];
                $currentCategoryId = Category::query()->where('code', $categoryCode)->value('id');
            }

            $hsp = Hsp::query()->updateOrCreate(
                ['period_id' => $period->id, 'work_code' => $workCode],
                ['category_id' => $currentCategoryId, 'description' => $description, 'unit' => $unit !== '' ? $unit : null, 'is_active' => true]
            );

            $importedHsp++;

            $rawOverhead = $this->number($this->cellValue($sheet, "V{$row}"));
            $overheadPercent = $rawOverhead > 0 && $rawOverhead <= 1 ? $rawOverhead * 100 : $rawOverhead;

            foreach ($regionMap as $regionCode => $columns) {
                $regionalCode = $this->text($this->cellValue($sheet, $columns['regional_code'] . $row));
                if ($regionalCode === '') {
                    continue;
                }

                $material = $this->number($this->cellValue($sheet, $columns['material'] . $row));
                $service = $this->number($this->cellValue($sheet, $columns['service'] . $row));
                $directPrice = $this->number($this->cellValue($sheet, $columns['direct_price'] . $row));
                $finalPrice = $this->number($this->cellValue($sheet, $columns['final_price'] . $row));

                if ($finalPrice <= 0 && $directPrice > 0) {
                    $finalPrice = $directPrice;
                }

                $region = $regions->get($regionCode);

                $hsp->prices()->updateOrCreate(
                    ['region_id' => $region->id],
                    ['regional_code' => $regionalCode, 'material' => $material, 'service' => $service, 'price' => $finalPrice]
                );

                if ($overheadPercent > 0) {
                    $hsp->parameters()->updateOrCreate(
                        ['region_id' => $region->id],
                        ['overhead_profit_percent' => $overheadPercent]
                    );
                }

                $importedPrices++;
            }
        }

        return ['hsp' => $importedHsp, 'hsp_prices' => $importedPrices];
    }

    private function importAhsSheet(Worksheet $sheet, Period $period, $regions): array
    {
        $priceColumns = [
            'JATENG_DIY' => ['material' => 'G', 'service' => 'H'],
            'JATIM' => ['material' => 'L', 'service' => 'M'],
            'BALI' => ['material' => 'Q', 'service' => 'R'],
            'NTB_NTT' => ['material' => 'V', 'service' => 'W'],
        ];

        $hspMap = Hsp::query()->where('period_id', $period->id)->get()->keyBy('work_code');

        AhspComponent::query()->whereIn('hsp_id', $hspMap->pluck('id'))->delete();

        $currentHsp = null;
        $currentType = null;
        $sortOrder = 0;

        $componentCount = 0;
        $basicItemCount = 0;
        $basicItemPriceCount = 0;
        $missingHsp = 0;
        $itemCache = [];

        for ($row = 5; $row <= $sheet->getHighestDataRow(); $row++) {
            $columnA = $this->text($this->cellValue($sheet, "A{$row}"));
            $columnB = $this->text($this->cellValue($sheet, "B{$row}"));
            $columnC = $this->text($this->cellValue($sheet, "C{$row}"));
            $description = $this->text($this->cellValue($sheet, "D{$row}"));
            $unit = $this->text($this->cellValue($sheet, "E{$row}"));
            $coefficient = $this->number($this->cellValue($sheet, "F{$row}"));
            $jatengRegionalCode = $this->text($this->cellValue($sheet, "H{$row}"));

            if ($columnA !== '' && $columnC !== '' && str_starts_with($jatengRegionalCode, 'TR3.')) {
                $currentHsp = $hspMap->get($columnC);
                $currentType = null;
                $sortOrder = 0;

                if (!$currentHsp) {
                    $missingHsp++;
                    continue;
                }

                if ($columnB !== '' && $currentHsp->binkon_code !== $columnB) {
                    $currentHsp->update(['binkon_code' => $columnB]);
                }

                continue;
            }

            if (!$currentHsp) {
                continue;
            }

            $upperDescription = mb_strtoupper($description);

            if ($columnC === 'A' && str_contains($upperDescription, 'TENAGA KERJA')) {
                $currentType = 'labor';
                continue;
            }
            if ($columnC === 'B' && str_contains($upperDescription, 'BAHAN')) {
                $currentType = 'material';
                continue;
            }
            if ($columnC === 'C' && str_contains($upperDescription, 'PERALATAN')) {
                $currentType = 'equipment';
                continue;
            }

            if (!$currentType || $description === '' || $unit === '' || $coefficient <= 0 || str_starts_with($upperDescription, 'JUMLAH')) {
                continue;
            }

            $itemCode = $this->makeBasicItemCode($currentType, $description, $unit);

            if (isset($itemCache[$itemCode])) {
                $basicItem = $itemCache[$itemCode];
            } else {
                $basicItem = BasicItem::query()->updateOrCreate(
                    ['code' => $itemCode],
                    ['source_no' => $columnC !== '' ? $columnC : null, 'item_type' => $currentType, 'description' => $description, 'unit' => $unit, 'is_active' => true]
                );

                $itemCache[$itemCode] = $basicItem;
                $basicItemCount++;
            }

            $sortOrder++;

            AhspComponent::query()->create([
                'hsp_id' => $currentHsp->id,
                'basic_item_id' => $basicItem->id,
                'coefficient' => $coefficient,
                'sort_order' => $sortOrder,
                'notes' => null,
            ]);

            $componentCount++;

            foreach ($priceColumns as $regionCode => $columns) {
                $priceColumn = $currentType === 'material' ? $columns['material'] : $columns['service'];
                $price = $this->number($this->cellValue($sheet, $priceColumn . $row));
                $region = $regions->get($regionCode);

                $basicItem->prices()->updateOrCreate(
                    ['period_id' => $period->id, 'region_id' => $region->id],
                    ['price' => $price]
                );

                $basicItemPriceCount++;
            }
        }

        return ['components' => $componentCount, 'basic_items' => $basicItemCount, 'basic_item_prices' => $basicItemPriceCount, 'missing_hsp' => $missingHsp];
    }

    private function importBasicItemReferenceSheet(Worksheet $sheet, Period $period, $regions): array
    {
        $regionColumns = [
            'JATENG_DIY' => ['price' => 'F', 'reference_price_1' => 'L', 'reference_price_2' => 'Q', 'reference_link_1' => 'V', 'reference_link_2' => 'AA'],
            'JATIM' => ['price' => 'G', 'reference_price_1' => 'M', 'reference_price_2' => 'R', 'reference_link_1' => 'W', 'reference_link_2' => 'AB'],
            'BALI' => ['price' => 'H', 'reference_price_1' => 'N', 'reference_price_2' => 'S', 'reference_link_1' => 'X', 'reference_link_2' => 'AC'],
            'NTB_NTT' => ['price' => 'I', 'reference_price_1' => 'O', 'reference_price_2' => 'T', 'reference_link_1' => 'Y', 'reference_link_2' => 'AD'],
        ];

        $items = BasicItem::query()->get();

        $itemsByCode = $items->keyBy('code');
        $itemsByTypedKey = [];
        $itemsByLooseKey = [];

        foreach ($items as $item) {
            $typedKey = $this->makeItemMatchKey($item->item_type, $item->description, (string) $item->unit);
            $looseKey = $this->makeLooseItemMatchKey($item->description, (string) $item->unit);
            $itemsByTypedKey[$typedKey] = $item;
            $itemsByLooseKey[$looseKey][] = $item;
        }

        $currentType = null;

        $matchedItems = 0;
        $createdItems = 0;
        $updatedPrices = 0;
        $skippedItems = 0;

        for ($row = 6; $row <= $sheet->getHighestDataRow(); $row++) {
            $sourceNo = $this->text($this->cellValue($sheet, "C{$row}"));
            $description = $this->text($this->cellValue($sheet, "D{$row}"));
            $unit = $this->text($this->cellValue($sheet, "E{$row}"));

            $upperDescription = mb_strtoupper($description);

            if ($sourceNo === 'A' && $upperDescription === 'UPAH') {
                $currentType = 'labor';
                continue;
            }
            if ($sourceNo === 'B' && $upperDescription === 'MATERIAL') {
                $currentType = 'material';
                continue;
            }
            if (str_contains($upperDescription, 'SEWA PERALATAN')) {
                $currentType = 'equipment';
                continue;
            }
            if (str_contains($upperDescription, 'DKD')) {
                $currentType = 'dkd';
                continue;
            }
            if (str_contains($upperDescription, 'LAIN - LAIN')) {
                $currentType = 'material';
                continue;
            }

            if ($sourceNo === '' || !is_numeric($sourceNo) || $description === '' || $unit === '' || $currentType === null) {
                continue;
            }

            $itemCode = $this->makeBasicItemCode($currentType, $description, $unit);
            $typedKey = $this->makeItemMatchKey($currentType, $description, $unit);
            $looseKey = $this->makeLooseItemMatchKey($description, $unit);

            $basicItem = $itemsByCode->get($itemCode) ?? ($itemsByTypedKey[$typedKey] ?? null);

            if (!$basicItem && isset($itemsByLooseKey[$looseKey])) {
                $looseCandidates = $itemsByLooseKey[$looseKey];
                $basicItem = collect($looseCandidates)->firstWhere('item_type', $currentType)
                    ?? (count($looseCandidates) === 1 ? $looseCandidates[0] : null);
            }

            if ($basicItem) {
                $matchedItems++;
            } else {
                $basicItem = BasicItem::query()->create([
                    'code' => $itemCode,
                    'source_no' => $sourceNo,
                    'item_type' => $currentType,
                    'description' => $description,
                    'unit' => $unit,
                    'is_active' => true,
                ]);

                $createdItems++;

                $itemsByCode->put($itemCode, $basicItem);
                $itemsByTypedKey[$typedKey] = $basicItem;
                $itemsByLooseKey[$looseKey][] = $basicItem;
            }

            if (!$basicItem) {
                $skippedItems++;
                continue;
            }

            $basicItem->update([
                'source_no' => $sourceNo,
                'description' => $description,
                'unit' => $unit,
                'is_active' => true,
            ]);

            foreach ($regionColumns as $regionCode => $columns) {
                $region = $regions->get($regionCode);
                if (!$region) {
                    continue;
                }

                $actualPrice = $this->number($this->cellValue($sheet, $columns['price'] . $row));
                $referencePrice1 = $this->nullableNumber($this->cellValue($sheet, $columns['reference_price_1'] . $row));
                $referencePrice2 = $this->nullableNumber($this->cellValue($sheet, $columns['reference_price_2'] . $row));
                $referenceLink1 = $this->nullableText($this->cellValue($sheet, $columns['reference_link_1'] . $row));
                $referenceLink2 = $this->nullableText($this->cellValue($sheet, $columns['reference_link_2'] . $row));

                $basicItem->prices()->updateOrCreate(
                    ['period_id' => $period->id, 'region_id' => $region->id],
                    [
                        'price' => $actualPrice,
                        'reference_price_1' => $referencePrice1,
                        'reference_link_1' => $referenceLink1,
                        'reference_price_2' => $referencePrice2,
                        'reference_link_2' => $referenceLink2,
                    ]
                );

                $updatedPrices++;
            }
        }

        return ['reference_items_matched' => $matchedItems, 'reference_items_created' => $createdItems, 'reference_prices' => $updatedPrices, 'reference_items_skipped' => $skippedItems];
    }

    private function makeBasicItemCode(string $type, string $description, string $unit): string
    {
        $prefix = match ($type) {'labor' => 'LAB', 'material' => 'MAT', 'equipment' => 'EQP', 'dkd' => 'DKD', default => 'ITM'};
        $normalized = $this->normalizeText($description) . '|' . $this->normalizeText($unit);
        return $prefix . '-' . strtoupper(substr(sha1($normalized), 0, 16));
    }

    private function makeItemMatchKey(string $type, string $description, string $unit): string
    {
        return $type . '|' . $this->makeLooseItemMatchKey($description, $unit);
    }

    private function makeLooseItemMatchKey(string $description, string $unit): string
    {
        $normalizedDescription = $this->normalizeText($description);
        $normalizedUnit = $this->normalizeText($unit);

        if ($normalizedUnit !== '' && str_ends_with($normalizedDescription, $normalizedUnit)) {
            $normalizedDescription = substr($normalizedDescription, 0, -strlen($normalizedUnit));
        }

        return $normalizedDescription . '|' . $normalizedUnit;
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['³', '²', '”', '“', '’', '‘'], ['3', '2', '', '', '', ''], $value);
        $value = preg_replace('/[^\pL\pN]+/u', '', $value);
        return $value ?? '';
    }

    private function nullableNumber(mixed $value): ?float
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $this->number($value);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = $this->text($value);
        return $text !== '' ? $text : null;
    }

    private function cellValue(Worksheet $sheet, string $coordinate): mixed
    {
        $cell = $sheet->getCell($coordinate);
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            $cachedValue = $cell->getOldCalculatedValue();
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }

        return $value;
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function number(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', (string) ($value ?? ''));

        if ($clean === '' || $clean === null) {
            return 0;
        }

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');

            if ($lastComma > $lastDot) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            $parts = explode(',', $clean);
            $lastPart = end($parts);

            if (count($parts) > 2 || strlen($lastPart) === 3) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        } elseif (str_contains($clean, '.')) {
            $parts = explode('.', $clean);
            $lastPart = end($parts);

            if (count($parts) > 2 || strlen($lastPart) === 3) {
                $clean = str_replace('.', '', $clean);
            }
        }

        return is_numeric($clean) ? (float) $clean : 0;
    }
}
