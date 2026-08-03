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
            ->orderBy('work_code')
            ->paginate(20)
            ->withQueryString();

        return view('user.hsp.index', compact(
            'hsp', 'periods', 'regions', 'categories', 'periodId', 'regionId', 'categoryId', 'search'
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
