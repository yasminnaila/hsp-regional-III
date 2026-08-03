<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

            ->orderBy('work_code')
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

        $analysis = $calculator->calculate(
            $hsp,
            (int) $regionId
        );

        return view(
            'admin.hsp.show',
            compact(
                'hsp',
                'regions',
                'regionId',
                'analysis'
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
