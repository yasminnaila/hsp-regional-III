<?php

namespace App\Services;

use App\Models\Hsp;

class AhspCalculationService
{
    public function calculate(Hsp $hsp, int $regionId): array
    {
        $hsp->loadMissing(['period', 'category']);

        $components = $hsp->components()
            ->with(['basicItem.prices' => function ($query) use ($hsp, $regionId): void {
                $query->where('period_id', $hsp->period_id)
                    ->where('region_id', $regionId);
            }])
            ->orderBy('sort_order')
            ->get();

        $groups = [
            'labor' => [],
            'material' => [],
            'equipment' => [],
        ];

        foreach ($components as $component) {
            $item = $component->basicItem;

            if (!$item || !array_key_exists($item->item_type, $groups)) {
                continue;
            }

            $unitPrice = (float) optional($item->prices->first())->price;
            $amount = (float) $component->coefficient * $unitPrice;

            $groups[$item->item_type][] = [
                'code' => $item->code,
                'description' => $item->description,
                'unit' => $item->unit,
                'coefficient' => (float) $component->coefficient,
                'unit_price' => $unitPrice,
                'amount' => round($amount, 2),
                'amount_cents' => (int) round($amount * 100),
            ];
        }

        $subtotals = [];
        $subtotalCents = [];

        foreach ($groups as $type => $items) {
            $subtotals[$type] = array_sum(array_column($items, 'amount'));
            $subtotalCents[$type] = array_sum(array_column($items, 'amount_cents'));
        }

        $directCost = array_sum($subtotals);

        $parameter = $hsp->parameters()
            ->where('region_id', $regionId)
            ->first();

        $snapshotPrice = $hsp->prices()
            ->where('region_id', $regionId)
            ->first();

        if ($parameter) {
            $overheadPercent = (float) $parameter->overhead_profit_percent;
        } else {
            $overheadPercent = 0;
        }

        /*
         * Overhead & profit diterapkan SEKALI pada total biaya langsung,
         * bukan per bagian: semua komponen (tenaga kerja + bahan +
         * peralatan) dijumlahkan dahulu tanpa overhead, baru hasil
         * totalnya dikali overhead & profit.
         *
         * Mengikuti spreadsheet master, aritmetika jumlah dihitung per sen
         * (integer) agar tidak ada galat float; subtotal tiap bagian
         * dibulatkan ke bawah (rounddown) ke integer (senilai kolom
         * Material/Jasa pada workbook). Biaya langsung = jumlah subtotal
         * ketiga bagian, lalu dikali overhead & profit dan dibulatkan ke
         * bawah ke ratusan terdekat (setara ROUNDDOWN Excel).
         */
        $overheadFactor = 100 + (int) round($overheadPercent);

        $subtotalsWithOverhead = [];
        $overheadAmounts = [];
        $subtotalsRounded = [];

        foreach ($subtotals as $type => $subtotal) {
            $rounded = (int) intdiv($subtotalCents[$type], 100);
            $subtotalsRounded[$type] = $rounded;
            $subtotalsWithOverhead[$type] = (int) (intdiv($rounded * $overheadFactor, 10000) * 100);
            $overheadAmounts[$type] = $subtotal * ($overheadPercent / 100);
        }

        $overheadAmount = $directCost * ($overheadPercent / 100);
        $directCostRounded = array_sum($subtotalsRounded);
        $calculatedPrice = (int) (intdiv($directCostRounded * $overheadFactor, 10000) * 100);

        /*
         * Harga final memakai hasil perhitungan terbaru agar perubahan
         * harga upah/bahan/alat langsung memengaruhi seluruh HSP.
         * Snapshot hanya menjadi cadangan saat komponen belum tersedia.
         */
        $finalPrice = $directCost > 0
            ? $calculatedPrice
            : ($snapshotPrice ? (float) $snapshotPrice->price : 0);

        return [
            'groups' => $groups,
            'subtotals' => $subtotals,
            'subtotals_rounded' => $subtotalsRounded,
            'subtotals_with_overhead' => $subtotalsWithOverhead,
            'direct_cost' => $directCost,
            'direct_cost_rounded' => $directCostRounded,
            'overhead_percent' => $overheadPercent,
            'overhead_amount' => $overheadAmount,
            'overhead_amounts' => $overheadAmounts,
            'calculated_price' => $calculatedPrice,
            'snapshot_price' => $snapshotPrice,
            'final_price' => $finalPrice,
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
}
