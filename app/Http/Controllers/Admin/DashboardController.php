<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicItem;
use App\Models\Category;
use App\Models\Hsp;
use App\Models\Period;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $periods = Period::query()
            ->orderByDesc('year')
            ->get();

        $periodId = $request->integer('period')
            ?: optional($periods->firstWhere('is_active', true))->id
            ?: optional($periods->first())->id;

        $period = $periods->firstWhere('id', $periodId) ?: $periods->first();

        $regions = Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $regionTopId = $request->integer('region_top')
            ?: optional($regions->first())->id;

        $regionLowId = $request->integer('region_low')
            ?: optional($regions->first())->id;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $totalHsp = Hsp::query()
            ->where('is_active', true)
            ->where('period_id', $periodId)
            ->count();

        $totalComponents = Hsp::query()
            ->where('is_active', true)
            ->where('period_id', $periodId)
            ->withCount('components')
            ->get()
            ->sum('components_count');

        $regionStats = DB::table('hsp_prices')
            ->join('hsp', 'hsp.id', '=', 'hsp_prices.hsp_id')
            ->join('regions', 'regions.id', '=', 'hsp_prices.region_id')
            ->where('hsp.is_active', true)
            ->where('hsp.period_id', $periodId)
            ->selectRaw('
                regions.id AS region_id,
                regions.name AS region_name,
                regions.sort_order AS region_sort,
                COUNT(DISTINCT hsp.id) AS total,
                ROUND(AVG(hsp_prices.price), 0) AS avg_price
            ')
            ->groupBy('regions.id', 'regions.name', 'regions.sort_order')
            ->orderBy('regions.sort_order')
            ->get();

        $categoryStats = DB::table('hsp')
            ->leftJoin('categories', 'categories.id', '=', 'hsp.category_id')
            ->where('hsp.is_active', true)
            ->where('hsp.period_id', $periodId)
            ->selectRaw("
                COALESCE(categories.name, 'Tanpa Kategori') AS cat_name,
                COUNT(*) AS total
            ")
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $topHsp = Hsp::query()
            ->where('is_active', true)
            ->where('period_id', $periodId)
            ->with(['prices' => fn ($query) => $query->where('region_id', $regionTopId)])
            ->whereHas('prices', fn ($query) => $query->where('region_id', $regionTopId))
            ->orderByDesc(
                DB::table('hsp_prices')
                    ->whereColumn('hsp_prices.hsp_id', 'hsp.id')
                    ->where('hsp_prices.region_id', $regionTopId)
                    ->select('hsp_prices.price')
            )
            ->limit(5)
            ->get();

        $lowestHsp = Hsp::query()
            ->where('is_active', true)
            ->where('period_id', $periodId)
            ->with(['prices' => fn ($query) => $query->where('region_id', $regionLowId)])
            ->whereHas('prices', fn ($query) => $query->where('region_id', $regionLowId)->where('price', '>', 0))
            ->orderBy(
                DB::table('hsp_prices')
                    ->whereColumn('hsp_prices.hsp_id', 'hsp.id')
                    ->where('hsp_prices.region_id', $regionLowId)
                    ->select('hsp_prices.price')
            )
            ->limit(5)
            ->get();

        $basicItemTotals = BasicItem::query()
            ->where('is_active', true)
            ->selectRaw('item_type, COUNT(*) AS total')
            ->groupBy('item_type')
            ->pluck('total', 'item_type');

        $totalBasicItems = $basicItemTotals->sum();

        return view('admin.dashboard.index', compact(
            'periods', 'periodId', 'period', 'regions', 'regionTopId', 'regionLowId', 'categories',
            'totalHsp', 'totalComponents', 'regionStats', 'categoryStats', 'topHsp', 'lowestHsp',
            'basicItemTotals', 'totalBasicItems'
        ));
    }
}
