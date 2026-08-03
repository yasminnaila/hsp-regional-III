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
                'amount' => $amount,
            ];
        }

        $subtotals = [];

        foreach ($groups as $type => $items) {
            $subtotals[$type] = array_sum(
                array_column($items, 'amount')
            );
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

        $overheadAmount = $directCost * ($overheadPercent / 100);
        $calculatedPrice = $directCost + $overheadAmount;

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
            'direct_cost' => $directCost,
            'overhead_percent' => $overheadPercent,
            'overhead_amount' => $overheadAmount,
            'calculated_price' => $calculatedPrice,
            'snapshot_price' => $snapshotPrice,
            'final_price' => $finalPrice,
        ];
    }
}
