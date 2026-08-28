<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AhspComponent;
use App\Models\BasicItem;
use App\Models\Category;
use App\Models\Hsp;
use App\Models\Period;
use App\Models\Region;
use App\Services\AhspCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HspController extends Controller
{
    /**
     * Menampilkan daftar HSP.
     *
     * Daftar HSP sekaligus menjadi pintu masuk menuju
     * detail analisa AHS melalui tombol "Lihat Analisa".
     */
    public function index(Request $request): View
    {
        $periods = Period::query()
            ->orderByDesc('year')
            ->get();

        $regions = Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $categories = Category::query()
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

        $categoryId = $request->integer('category');

        $search = trim(
            (string) $request->input('q', '')
        );

        $hsp = Hsp::query()
            ->with([
                'period',
                'category',

                // Mengambil harga untuk wilayah yang sedang dipilih.
                'prices' => function ($query) use ($regionId): void {
                    $query->where('region_id', $regionId);
                },
            ])

            // Menghitung jumlah komponen AHS setiap pekerjaan.
            ->withCount('components')

            // Filter periode.
            ->when(
                $periodId,
                function ($query) use ($periodId): void {
                    $query->where('period_id', $periodId);
                }
            )

            // Filter kategori.
            ->when(
                $categoryId,
                function ($query) use ($categoryId): void {
                    $query->where('category_id', $categoryId);
                }
            )

            // Filter pencarian.
            ->when(
                $search !== '',
                function ($query) use (
                    $search,
                    $regionId
                ): void {
                    $query->where(
                        function ($subQuery) use (
                            $search,
                            $regionId
                        ): void {
                            $subQuery
                                ->where(
                                    'work_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'binkon_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'prices',
                                    function ($priceQuery) use (
                                        $search,
                                        $regionId
                                    ): void {
                                        $priceQuery
                                            ->where(
                                                'region_id',
                                                $regionId
                                            )
                                            ->where(
                                                'regional_code',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )

            // Hanya menampilkan HSP aktif.
            ->where('is_active', true)

            ->orderBy('sort_key')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.hsp.index',
            compact(
                'hsp',
                'periods',
                'regions',
                'categories',
                'periodId',
                'regionId',
                'categoryId',
                'search'
            )
        );
    }

    /**
     * Menampilkan halaman tambah HSP.
     */
    public function create(): View
    {
        return view('admin.hsp.form', [
            'hsp' => new Hsp(),

            'periods' => Period::query()
                ->orderByDesc('year')
                ->get(),

            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),

            'regions' => Region::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    /**
     * Menyimpan HSP baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $hsp = DB::transaction(
            function () use ($validated): Hsp {
                $hsp = Hsp::query()->create(
                    $validated['hsp']
                );

                $this->syncPrices(
                    $hsp,
                    $validated['prices'] ?? []
                );

                return $hsp;
            }
        );

        return redirect()
            ->route('admin.hsp.show', $hsp)
            ->with(
                'success',
                'Data HSP berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail HSP sekaligus analisa AHS.
     *
     * Halaman ini menampilkan:
     * - Tenaga kerja
     * - Bahan
     * - Peralatan
     * - Biaya langsung
     * - Overhead
     * - Harga satuan pekerjaan
     */
    public function show(
        Request $request,
        Hsp $hsp,
        AhspCalculationService $calculator
    ): View {
        $regions = Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $regionId = $request->integer('region')
            ?: optional($regions->first())->id;

        if (
            !$hsp->prices()
                ->where('region_id', $regionId)
                ->exists()
            && $hsp->components()->exists()
        ) {
            $hsp->parameters()->firstOrCreate(
                [
                    'region_id' => $regionId,
                ],
                [
                    'overhead_profit_percent' => 15,
                ]
            );
        }

        $analysis = $calculator->calculate(
            $hsp,
            (int) $regionId
        );

        $this->syncComputedPrices(
            $hsp,
            (int) $regionId,
            $analysis
        );

        $basicItems = BasicItem::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->groupBy('item_type');

        $componentsByType = $hsp->components()
            ->with('basicItem')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('basicItem.item_type');

        return view(
            'admin.hsp.show',
            compact(
                'hsp',
                'regions',
                'regionId',
                'analysis',
                'basicItems',
                'componentsByType'
            )
        );
    }

    /**
     * Menampilkan halaman edit HSP.
     */
    public function edit(Hsp $hsp): View
    {
        $hsp->load('prices');

        return view('admin.hsp.form', [
            'hsp' => $hsp,

            'periods' => Period::query()
                ->orderByDesc('year')
                ->get(),

            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),

            'regions' => Region::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    /**
     * Memperbarui data HSP.
     */
    public function update(
        Request $request,
        Hsp $hsp
    ): RedirectResponse {
        $validated = $this->validatePayload(
            $request,
            $hsp
        );

        DB::transaction(
            function () use (
                $hsp,
                $validated
            ): void {
                $hsp->update(
                    $validated['hsp']
                );

                $this->syncPrices(
                    $hsp,
                    $validated['prices'] ?? []
                );
            }
        );

        return redirect()
            ->route('admin.hsp.show', $hsp)
            ->with(
                'success',
                'Data HSP berhasil diperbarui.'
            );
    }

    /**
     * Menghapus data HSP.
     */
    public function destroy(Hsp $hsp): RedirectResponse
    {
        $hsp->delete();

        return redirect()
            ->route('admin.hsp.index')
            ->with(
                'success',
                'Data HSP berhasil dihapus.'
            );
    }

    /**
     * Memvalidasi data tambah dan edit HSP.
     */
    private function validatePayload(
        Request $request,
        ?Hsp $hsp = null
    ): array {
        $request->validate([
            'period_id' => [
                'required',
                'exists:periods,id',
            ],

            'category_id' => [
                'nullable',
                'exists:categories,id',
            ],

            'work_code' => [
                'required',
                'string',
                'max:100',

                Rule::unique('hsp', 'work_code')
                    ->where(
                        fn ($query) => $query->where(
                            'period_id',
                            $request->integer('period_id')
                        )
                    )
                    ->ignore($hsp?->id),
            ],

            'binkon_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'required',
                'string',
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
                'nullable',
                'array',
            ],

            'prices.*.regional_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'prices.*.material' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'prices.*.equipment' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'prices.*.service' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'prices.*.price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        return [
            'hsp' => [
                'period_id' => $request->integer(
                    'period_id'
                ),

                'category_id' => $request->filled(
                    'category_id'
                )
                    ? $request->integer('category_id')
                    : null,

                'work_code' => $request
                    ->string('work_code')
                    ->trim()
                    ->toString(),

                'binkon_code' => $request->filled(
                    'binkon_code'
                )
                    ? $request
                        ->string('binkon_code')
                        ->trim()
                        ->toString()
                    : null,

                'description' => $request
                    ->string('description')
                    ->trim()
                    ->toString(),

                'unit' => $request->filled('unit')
                    ? $request
                        ->string('unit')
                        ->trim()
                        ->toString()
                    : null,

                'is_active' => $request->boolean(
                    'is_active'
                ),
            ],

            'prices' => $request->input(
                'prices',
                []
            ),
        ];
    }

    /**
     * Menambahkan komponen (tenaga kerja/bahan/peralatan)
     * ke analisa AHS dari dropdown data upah, bahan, dan alat.
     */
    public function storeComponent(
        Request $request,
        Hsp $hsp
    ): RedirectResponse {
        $validated = $request->validate([
            'basic_item_id' => [
                'required',
                'integer',
                'exists:basic_items,id',
            ],

            'coefficient' => [
                'required',
                'numeric',
                'min:0.0001',
            ],
        ]);

        if (
            $hsp->components()
                ->where(
                    'basic_item_id',
                    $validated['basic_item_id']
                )
                ->exists()
        ) {
            return back()->with(
                'error',
                'Komponen tersebut sudah ada pada analisa ini.'
            );
        }

        $hsp->components()->create([
            'basic_item_id' => $validated['basic_item_id'],
            'coefficient' => $validated['coefficient'],
            'sort_order' => (
                $hsp->components()->max('sort_order') + 1
            ) ?: 1,
        ]);

        $this->persistComputedPrice(
            $hsp,
            $this->resolveRegionId($request)
        );

        return back()->with(
            'success',
            'Komponen berhasil ditambahkan.'
        );
    }

    /**
     * Menghapus komponen dari analisa AHS.
     */
    public function destroyComponent(
        Request $request,
        Hsp $hsp,
        AhspComponent $component
    ): RedirectResponse {
        if ($component->hsp_id !== $hsp->id) {
            abort(404);
        }

        $component->delete();

        $this->persistComputedPrice(
            $hsp,
            $this->resolveRegionId($request)
        );

        return back()->with(
            'success',
            'Komponen berhasil dihapus.'
        );
    }

    /**
     * Menentukan wilayah aktif dari query string, dengan
     * wilayah pertama sebagai nilai bawaan.
     */
    private function resolveRegionId(Request $request): int
    {
        $regionId = $request->integer('region');

        if ($regionId > 0) {
            return $regionId;
        }

        return (int) Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');
    }

    /**
     * Menghitung ulang analisa AHS lalu menyimpan harga per
     * wilayah. Dipanggil ketika admin menambah/menghapus komponen
     * sehingga Material, Jasa, dan Harga langsung terbarui.
     */
    private function persistComputedPrice(
        Hsp $hsp,
        int $regionId
    ): void {
        $hsp->parameters()->firstOrCreate(
            [
                'region_id' => $regionId,
            ],
            [
                'overhead_profit_percent' => 15,
            ]
        );

        $analysis = (new AhspCalculationService())->calculate(
            $hsp,
            $regionId
        );

        $rounded = $analysis['subtotals_rounded'];

        if (array_sum($rounded) <= 0) {
            $hsp->prices()
                ->where('region_id', $regionId)
                ->delete();

            return;
        }

        $existing = $hsp->prices()
            ->where('region_id', $regionId)
            ->first();

        $hsp->prices()->updateOrCreate(
            [
                'region_id' => $regionId,
            ],
            [
                'regional_code' => $existing?->regional_code
                    ?: $hsp->work_code,
                'material' => $rounded['material'] ?? 0,
                'equipment' => $rounded['equipment'] ?? 0,
                'service' => ($rounded['labor'] ?? 0)
                    + ($rounded['equipment'] ?? 0),
                'price' => $analysis['final_price'] ?? 0,
            ]
        );
    }

    /**
     * Mengisi harga per wilayah secara otomatis dari hasil hitung
     * analisa AHS untuk HSP baru yang belum punya harga sama sekali.
     *
     * Harga yang sudah ada (misalnya dari impor Excel) tidak ditimpa.
     */
    private function syncComputedPrices(
        Hsp $hsp,
        int $regionId,
        array $analysis
    ): void {
        if (
            $hsp->prices()
                ->where('region_id', $regionId)
                ->exists()
        ) {
            return;
        }

        $rounded = $analysis['subtotals_rounded'];

        if (array_sum($rounded) <= 0) {
            return;
        }

        $hsp->prices()->create([
            'region_id' => $regionId,
            'regional_code' => $hsp->work_code,
            'material' => $rounded['material'] ?? 0,
            'equipment' => $rounded['equipment'] ?? 0,
            'service' => ($rounded['labor'] ?? 0)
                + ($rounded['equipment'] ?? 0),
            'price' => $analysis['final_price'] ?? 0,
        ]);
    }

    /**
     * Menyimpan atau memperbarui harga HSP per wilayah.
     */
    private function syncPrices(
        Hsp $hsp,
        array $prices
    ): void {
        foreach ($prices as $regionId => $priceData) {
            if (empty($priceData['regional_code'])) {
                continue;
            }

            $hsp->prices()->updateOrCreate(
                [
                    'region_id' => (int) $regionId,
                ],
                [
                    'regional_code' => $priceData[
                        'regional_code'
                    ],

                    'material' => $priceData[
                        'material'
                    ] ?? 0,

                    'equipment' => $priceData[
                        'equipment'
                    ] ?? 0,

                    'service' => $priceData[
                        'service'
                    ] ?? 0,

                    'price' => $priceData[
                        'price'
                    ] ?? 0,
                ]
            );
        }
    }
}
