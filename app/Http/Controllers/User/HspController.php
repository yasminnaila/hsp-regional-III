<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Hsp;
use App\Models\Period;
use App\Models\Region;
use App\Services\AhspCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HspController extends Controller
{
    public function index(Request $request): View
    {
        $periods = Period::query()->orderByDesc('year')->get();
        $regions = Region::query()->where('is_active', true)->orderBy('sort_order')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();

        $periodId = $request->integer('period') ?: optional($periods->firstWhere('is_active', true))->id ?: optional($periods->first())->id;
        $regionId = $request->integer('region') ?: optional($regions->first())->id;
        $categoryId = $request->integer('category') ?: null;
        $search = trim((string) $request->input('q', ''));

        $hsp = Hsp::query()
            ->with([
                'category',
                'period',
                'prices' => fn ($query) => $query->where('region_id', $regionId),
            ])
            ->where('is_active', true)
            ->when($periodId, fn ($query) => $query->where('period_id', $periodId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($regionId, fn ($query) => $query->whereHas('prices', fn ($priceQuery) => $priceQuery->where('region_id', $regionId)))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('work_code', 'like', "%{$search}%")
                        ->orWhere('binkon_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('prices', fn ($priceQuery) => $priceQuery->where('regional_code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('sort_key')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $statsRow = Hsp::query()
            ->from('hsp')
            ->where('hsp.is_active', true)
            ->where('hsp.period_id', $periodId)
            ->when($categoryId, fn ($query) => $query->where('hsp.category_id', $categoryId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('hsp.work_code', 'like', "%{$search}%")
                        ->orWhere('hsp.binkon_code', 'like', "%{$search}%")
                        ->orWhere('hsp.description', 'like', "%{$search}%")
                        ->orWhere('hsp_prices.regional_code', 'like', "%{$search}%");
                });
            })
            ->join('hsp_prices', 'hsp_prices.hsp_id', '=', 'hsp.id')
            ->where('hsp_prices.region_id', $regionId)
            ->selectRaw('
                COUNT(DISTINCT hsp.id) AS total,
                ROUND(AVG(hsp_prices.price), 0) AS avg_price,
                MAX(hsp_prices.price) AS max_price,
                ROUND(AVG(hsp.tkdn_percent), 2) AS avg_tkdn
            ')
            ->first();

        return view('user.hsp.index', compact(
            'hsp', 'periods', 'regions', 'categories', 'periodId', 'regionId', 'categoryId', 'search', 'statsRow'
        ));
    }

    public function show(Request $request, Hsp $hsp, AhspCalculationService $calculator): View
    {
        abort_unless($hsp->is_active, 404);

        $regions = Region::query()->where('is_active', true)->orderBy('sort_order')->get();
        $regionId = $request->integer('region') ?: optional($regions->first())->id;
        $analysis = $calculator->calculate($hsp, (int) $regionId);

        return view('user.hsp.show', compact('hsp', 'regions', 'regionId', 'analysis'));
    }
}
