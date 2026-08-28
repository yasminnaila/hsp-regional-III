@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Ringkasan Data HSP')

@section('content')

<section class="dashboard-intro">
    <div class="dashboard-intro-copy">
        <span class="dashboard-kicker">Pusat kendali data</span>
        <h2>Ringkasan AHSP Regional III</h2>
        <p>Pantau kelengkapan harga dan komposisi data pekerjaan dalam satu tampilan.</p>

        <div class="dashboard-actions">
            <a href="{{ route('admin.hsp.index') }}" class="dashboard-action primary-action">
                Lihat Data HSP
                <span aria-hidden="true">&rarr;</span>
            </a>
            <a href="{{ route('admin.basic-items.index') }}" class="dashboard-action">
                Kelola Upah, Bahan &amp; Alat
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="dashboard-filter-panel">
        <div class="dashboard-filter-heading">
            <span>Data yang ditampilkan</span>
            <strong>Perbarui ringkasan</strong>
        </div>
        <div class="dashboard-filter-fields">
            <div>
                <label for="period">Periode</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}" @selected((int) $periodId === (int) $p->id)>
                            {{ $p->year }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <input type="hidden" name="region_top" value="{{ $regionTopId }}">
        <input type="hidden" name="region_low" value="{{ $regionLowId }}">
    </form>
</section>

{{-- Ringkasan --}}
<div class="cards dash-cards">
    <div class="card-stat blue">
        <div class="stat-icon blue">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <span>Total HSP ({{ $period?->year }})</span>
        <strong>{{ number_format($totalHsp, 0, ',', '.') }}</strong>
    </div>
    <div class="card-stat violet">
        <div class="stat-icon violet">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <span>Total Komponen AHS</span>
        <strong>{{ number_format($totalComponents, 0, ',', '.') }}</strong>
    </div>
    <div class="card uba-card">
        <div class="card-head">
            <div class="h-icon amber">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3>Upah, Bahan &amp; Alat<small>Total {{ number_format($totalBasicItems, 0, ',', '.') }} item aktif ({{ $period?->year }})</small></h3>
        </div>
        <div class="uba-grid">
            @php
                $typeLabels = [
                    'labor' => ['label' => 'Upah', 'icon' => 'person', 'color' => 'labor'],
                    'material' => ['label' => 'Bahan', 'icon' => 'box', 'color' => 'material'],
                    'equipment' => ['label' => 'Peralatan', 'icon' => 'wrench', 'color' => 'equipment'],
                    'dkd' => ['label' => 'DKD', 'icon' => 'shield', 'color' => 'dkd'],
                ];
                $icons = [
                    'person' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                    'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
                    'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
                    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                ];
            @endphp
            @foreach ($typeLabels as $type => $meta)
                @php
                    $count = (int) ($basicItemTotals[$type] ?? 0);
                    $percent = $totalBasicItems > 0 ? round($count / $totalBasicItems * 100) : 0;
                @endphp
                <div class="uba-item">
                    <div class="uba-top">
                        <div class="uba-icon {{ $meta['color'] }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$meta['icon']] !!}</svg>
                        </div>
                        <span class="uba-name">{{ $meta['label'] }}</span>
                    </div>
                    <strong>{{ number_format($count, 0, ',', '.') }}</strong>
                    <div class="mini-bar bar-{{ $meta['color'] }}">
                        <span style="width: {{ $percent }}%;"></span>
                    </div>
                    <small class="uba-pct">{{ $percent }}%</small>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid-2-eq dashboard-insights">
    <div class="card">
        <div class="card-head">
            <div class="h-icon blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <h3>Rata-rata Harga HSP per Wilayah<small>{{ $period?->year }}</small></h3>
        </div>
        @php
            $highestAverage = (float) ($regionStats->max('avg_price') ?? 0);
            $lowestAverage = (float) ($regionStats->min('avg_price') ?? 0);
        @endphp

        <div class="avg-region-grid">
            @forelse ($regionStats as $rs)
                @php
                    $average = (float) ($rs->avg_price ?? 0);
                    $difference = $highestAverage - $average;
                    $position = $average === $highestAverage ? 'highest' : ($average === $lowestAverage ? 'lowest' : 'normal');
                @endphp

                <article class="avg-region-card {{ $position }}">
                    <div class="avg-region-top">
                        <span>{{ $rs->region_name }}</span>
                        @if ($position === 'highest')
                            <small>Tertinggi</small>
                        @elseif ($position === 'lowest')
                            <small>Terendah</small>
                        @endif
                    </div>
                    <strong>Rp {{ number_format($average, 0, ',', '.') }}</strong>
                    <div class="avg-region-footer">
                        <span>{{ number_format($rs->total, 0, ',', '.') }} HSP</span>
                        <span>
                            {{ $difference > 0 ? 'Selisih Rp ' . number_format($difference, 0, ',', '.') : 'Selisih Rp 0' }}
                        </span>
                    </div>
                </article>
            @empty
                <p class="muted">Belum ada data harga.</p>
            @endforelse
        </div>
    </div>

    <div class="card category-distribution">
        <div class="card-head">
            <div class="h-icon violet">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <h3>Distribusi per Kategori<small>{{ $period?->year }}</small></h3>
        </div>
        <div class="category-list">
            <table>
                <tbody>
                    @forelse ($categoryStats as $cs)
                        <tr>
                            <td>{{ $cs->cat_name }}</td>
                            <td class="num" style="width:70px;"><span class="badge-count">{{ number_format($cs->total, 0, ',', '.') }}</span></td>
                            <td style="width:45%;">
                                <div class="mini-bar">
                                    <span style="width: {{ $cs->total > 0 ? min(100, $cs->total / ($categoryStats->max('total') ?: 1) * 100) : 0 }}%;"></span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="muted">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid-2-eq" style="margin-top:20px;">

    <div class="card price-leaderboard price-leaderboard-high">
        <div class="card-head leaderboard-head">
            <div class="h-icon amber">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <h3>5 HSP dengan Harga Tertinggi<small>Nilai terbesar di {{ optional($regions->firstWhere('id', $regionTopId))->name ?? 'wilayah terpilih' }}</small></h3>
            <span class="leaderboard-tag">TERTINGGI</span>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="leaderboard-filter">
            <label for="region_top">Wilayah</label>
            <select id="region_top" name="region_top" onchange="preserveDashboardScroll(this.form)" class="dash-inline-select">
                @foreach ($regions as $r)
                    <option value="{{ $r->id }}" @selected((int) $regionTopId === (int) $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="period" value="{{ $periodId }}">
            <input type="hidden" name="region_low" value="{{ $regionLowId }}">
        </form>
        <div class="table-wrap">
            <table class="leaderboard-table">
                <thead>
                    <tr><th>No</th><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th class="num">Harga</th></tr>
                </thead>
                <tbody>
                    @forelse ($topHsp as $item)
                        @php($price = $item->prices->first())
                        @php($rank = $loop->iteration <= 3 ? ['gold', 'silver', 'bronze'][$loop->iteration - 1] : '')
                        <tr>
                            <td><span class="rank-badge {{ $rank }}">{{ $loop->iteration }}</span></td>
                            <td>{{ $price?->regional_code ?? $item->work_code }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->unit ?: '-' }}</td>
                            <td class="num amount blue">Rp {{ number_format((float) ($price?->price ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Belum ada data harga untuk wilayah ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card price-leaderboard price-leaderboard-low">
        <div class="card-head leaderboard-head">
            <div class="h-icon green">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            </div>
            <h3>5 HSP dengan Harga Terendah<small>Nilai terkecil di wilayah terpilih</small></h3>
            <span class="leaderboard-tag">TERENDAH</span>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="leaderboard-filter">
            <label for="region_low">Wilayah</label>
            <select id="region_low" name="region_low" onchange="preserveDashboardScroll(this.form)" class="dash-inline-select">
                @foreach ($regions as $r)
                    <option value="{{ $r->id }}" @selected((int) $regionLowId === (int) $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="period" value="{{ $periodId }}">
            <input type="hidden" name="region_top" value="{{ $regionTopId }}">
        </form>
        <div class="table-wrap">
            <table class="leaderboard-table">
                <thead>
                    <tr><th>No</th><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th class="num">Harga</th></tr>
                </thead>
                <tbody>
                    @forelse ($lowestHsp as $item)
                        @php($price = $item->prices->first())
                        @php($rank = $loop->iteration <= 3 ? ['gold', 'silver', 'bronze'][$loop->iteration - 1] : '')
                        <tr>
                            <td><span class="rank-badge {{ $rank }}">{{ $loop->iteration }}</span></td>
                            <td>{{ $price?->regional_code ?? $item->work_code }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->unit ?: '-' }}</td>
                            <td class="num amount green">Rp {{ number_format((float) ($price?->price ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Belum ada data harga untuk wilayah ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    function preserveDashboardScroll(form) {
        sessionStorage.setItem('dashboard-scroll-position', window.scrollY);
        form.submit();
    }

    const dashboardScrollPosition = sessionStorage.getItem('dashboard-scroll-position');

    if (dashboardScrollPosition !== null) {
        sessionStorage.removeItem('dashboard-scroll-position');
        requestAnimationFrame(() => window.scrollTo(0, Number(dashboardScrollPosition)));
    }
</script>

@endsection
