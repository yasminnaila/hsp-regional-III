<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hsp;
use App\Models\Period;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AhsController extends Controller
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

        $search = trim(
            (string) $request->input('q', '')
        );

        $ahs = Hsp::query()
            ->with([
                'period',
                'category',
            ])
            ->withCount('components')
            ->when(
                $periodId,
                function ($query) use ($periodId): void {
                    $query->where('period_id', $periodId);
                }
            )
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
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
                                );
                        }
                    );
                }
            )
            ->where('is_active', true)
            ->orderBy('work_code')
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.ahs.index',
            compact(
                'ahs',
                'periods',
                'regions',
                'periodId',
                'regionId',
                'search'
            )
        );
    }
}
