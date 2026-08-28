<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicItem;
use App\Models\Period;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BasicItemController extends Controller
{
    public function index(Request $request): View
    {
        $periods = Period::query()
            ->orderByDesc('year')
            ->get();

        $regions = Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $periodId = $request->integer('period')
            ?: optional(
                $periods->firstWhere('is_active', true)
            )->id
            ?: optional($periods->first())->id;

        $regionId = $request->integer('region')
            ?: optional($regions->first())->id;

        $type = $request->string('type')
            ->trim()
            ->toString();

        $search = $request->string('q')
            ->trim()
            ->toString();

        $items = BasicItem::query()
            ->with([
                'prices' => function ($query) use (
                    $periodId,
                    $regionId
                ): void {
                    $query
                        ->where('period_id', $periodId)
                        ->where('region_id', $regionId);
                },
            ])
            ->when(
                in_array(
                    $type,
                    ['labor', 'material', 'equipment', 'dkd'],
                    true
                ),
                function ($query) use ($type): void {
                    $query->where('item_type', $type);
                }
            )
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'unit',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderByRaw(
                "FIELD(
                    item_type,
                    'labor',
                    'material',
                    'equipment',
                    'dkd'
                )"
            )
            ->orderBy('description')
            ->paginate(25)
            ->withQueryString();

        return view(
            'admin.basic-items.index',
            compact(
                'items',
                'periods',
                'regions',
                'periodId',
                'regionId',
                'type',
                'search'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => [
                'required',
                'exists:periods,id',
            ],

            'return_region_id' => [
                'nullable',
                'exists:regions,id',
            ],

            'item_type' => [
                'required',
                Rule::in([
                    'labor',
                    'material',
                    'equipment',
                    'dkd',
                ]),
            ],

            'description' => [
                'required',
                'string',
                'max:500',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $validated['description'] = trim(
            $validated['description']
        );

        $basicItem = DB::transaction(
            function () use (
                $validated,
                $request
            ): BasicItem {
                $basicItem = BasicItem::query()->create([
                    'item_type' => $validated['item_type'],

                    'description' => $validated[
                        'description'
                    ],

                    'unit' => filled(
                        $validated['unit'] ?? null
                    )
                        ? trim($validated['unit'])
                        : null,

                    'is_active' => true,
                ]);

                $regionId = $request->filled(
                    'return_region_id'
                )
                    ? (int) $request->input(
                        'return_region_id'
                    )
                    : (int) Region::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->value('id');

                $price = $validated['price'] ?? null;

                if ($price !== null && $price !== '') {
                    $basicItem->prices()->updateOrCreate(
                        [
                            'period_id' => (int) $validated[
                                'period_id'
                            ],

                            'region_id' => $regionId,
                        ],
                        [
                            'price' => (float) $price,
                        ]
                    );
                }

                return $basicItem;
            }
        );

        return redirect()
            ->route('admin.basic-items.index', [
                'period' => $validated['period_id'],
                'region' => $validated['return_region_id']
                    ?? null,
                'type' => $validated['item_type'],
            ])
            ->with(
                'success',
                'Upah, bahan, atau alat berhasil ditambahkan.'
            );
    }

    public function edit(
        Request $request,
        BasicItem $basicItem
    ): View {
        $periods = Period::query()
            ->orderByDesc('year')
            ->get();

        $regions = Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $periodId = $request->integer('period')
            ?: optional(
                $periods->firstWhere('is_active', true)
            )->id
            ?: optional($periods->first())->id;

        $regionId = $request->integer('region')
            ?: optional($regions->first())->id;

        $basicItem->load([
            'prices' => function ($query) use ($periodId): void {
                $query->where('period_id', $periodId);
            },
        ]);

        return view(
            'admin.basic-items.edit',
            compact(
                'basicItem',
                'periods',
                'regions',
                'periodId',
                'regionId'
            )
        );
    }

    public function update(
    Request $request,
    BasicItem $basicItem
): RedirectResponse {
    $validated = $request->validate([
        'period_id' => [
            'required',
            'exists:periods,id',
        ],

        'return_region_id' => [
            'nullable',
            'exists:regions,id',
        ],

        'item_type' => [
            'required',
            Rule::in([
                'labor',
                'material',
                'equipment',
                'dkd',
            ]),
        ],

        'description' => [
            'required',
            'string',
            'max:500',
        ],

        'unit' => [
            'nullable',
            'string',
            'max:50',
        ],

        'is_active' => [
            'nullable',
            'boolean',
        ],

        'prices' => [
            'required',
            'array',
        ],

        'prices.*.reference_price_1' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'prices.*.reference_link_1' => [
            'nullable',
            'string',
            'max:2000',
        ],

        'prices.*.reference_price_2' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'prices.*.reference_link_2' => [
            'nullable',
            'string',
            'max:2000',
        ],

        'prices.*.price' => [
            'nullable',
            'numeric',
            'min:0',
        ],
    ]);

    DB::transaction(function () use (
        $basicItem,
        $validated,
        $request
    ): void {
        $basicItem->update([
            'item_type' => $validated['item_type'],

            'description' => trim(
                $validated['description']
            ),

            'unit' => filled(
                $validated['unit'] ?? null
            )
                ? trim($validated['unit'])
                : null,

            'is_active' => $request->boolean(
                'is_active'
            ),
        ]);

        foreach (
            $validated['prices'] as $regionId => $priceData
        ) {
            $referencePrice1 =
                $priceData['reference_price_1'] ?? null;

            $referencePrice2 =
                $priceData['reference_price_2'] ?? null;

            $referenceLink1 = trim(
                (string) (
                    $priceData['reference_link_1'] ?? ''
                )
            );

            $referenceLink2 = trim(
                (string) (
                    $priceData['reference_link_2'] ?? ''
                )
            );

            $actualPrice = $priceData['price'] ?? null;

            $basicItem->prices()->updateOrCreate(
                [
                    'period_id' => (int) $validated[
                        'period_id'
                    ],

                    'region_id' => (int) $regionId,
                ],
                [
                    'reference_price_1' =>
                        $referencePrice1 !== null
                        && $referencePrice1 !== ''
                            ? (float) $referencePrice1
                            : null,

                    'reference_link_1' =>
                        $referenceLink1 !== ''
                            ? $referenceLink1
                            : null,

                    'reference_price_2' =>
                        $referencePrice2 !== null
                        && $referencePrice2 !== ''
                            ? (float) $referencePrice2
                            : null,

                    'reference_link_2' =>
                        $referenceLink2 !== ''
                            ? $referenceLink2
                            : null,

                    'price' => $actualPrice !== null
                        && $actualPrice !== ''
                            ? (float) $actualPrice
                            : null,
                ]
            );
        }
    });

    return redirect()
        ->route('admin.basic-items.index', [
            'period' => $validated['period_id'],
            'region' => $validated['return_region_id'] ?? null,
            'type' => $validated['item_type'],
        ])
        ->with(
            'success',
            'Harga dan referensi berhasil diperbarui.'
        );
    }
}
