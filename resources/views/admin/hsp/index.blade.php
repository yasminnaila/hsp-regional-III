@extends('layouts.admin')

@section('title', 'Data HSP')
@section('page-title', 'Data Harga Satuan Pekerjaan')

@section('content')

{{-- Filter Data HSP --}}
<form
    id="hsp-filter-form"
    method="GET"
    action="{{ route('admin.hsp.index') }}"
>
    <div class="card">
        <div class="form-grid">

            {{-- Periode --}}
            <div>
                <label for="period">
                    Periode
                </label>

                <select
                    id="period"
                    name="period"
                    onchange="this.form.submit()"
                >
                    @foreach ($periods as $period)
                        <option
                            value="{{ $period->id }}"
                            @selected(
                                (int) $periodId === (int) $period->id
                            )
                        >
                            {{ $period->year }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Wilayah --}}
            <div>
                <label for="region">
                    Wilayah
                </label>

                <select
                    id="region"
                    name="region"
                    onchange="this.form.submit()"
                >
                    @foreach ($regions as $region)
                        <option
                            value="{{ $region->id }}"
                            @selected(
                                (int) $regionId === (int) $region->id
                            )
                        >
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Kategori --}}
            <div>
                <label for="category">
                    Kategori
                </label>

                <select
                    id="category"
                    name="category"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option
                            value="{{ $cat->id }}"
                            @selected(
                                (int) $categoryId === (int) $cat->id
                            )
                        >
                            {{ $cat->code }} - {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian --}}
            <div>
                <label for="q">
                    Pencarian
                </label>

                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari kode atau uraian pekerjaan"
                    oninput="filterDebounce(this.form)"
                >
            </div>

        </div>

        <div style="margin-top: 15px;">
            <a
                href="{{ route('admin.hsp.index') }}"
                class="btn"
            >
                Reset
            </a>
        </div>
    </div>
</form>

<script>
    let filterDebounceTimer;
    function filterDebounce(form) {
        clearTimeout(filterDebounceTimer);
        filterDebounceTimer = setTimeout(() => form.submit(), 500);
    }
</script>

{{-- Daftar HSP --}}
<div
    class="card"
    style="margin-top: 20px;"
>
    <div class="table-responsive">

        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode</th>
                    <th>Uraian Pekerjaan</th>
                    <th>Satuan</th>
                    <th class="num">Material</th>
                    <th class="num">Jasa</th>
                    <th class="num">Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($hsp as $item)
                    @php($price = $item->prices->first())
                    <tr>
                        <td>
                            {{ $hsp->firstItem() + $loop->index }}
                        </td>

                        <td>
                            {{ $price?->regional_code ?? $item->work_code }}
                        </td>

                        <td>
                            {{ $item->description }}
                        </td>

                        <td>
                            {{ $item->unit ?: '-' }}
                        </td>

                        <td class="num">
                            Rp {{ number_format(
                                (float) ($price?->material ?? 0),
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>

                        <td class="num">
                            Rp {{ number_format(
                                (float) ($price?->service ?? 0),
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>

                        <td class="num">
                            Rp {{ number_format(
                                (float) ($price?->price ?? 0),
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>

                        <td>
                            <a
                                href="{{ route(
                                    'admin.hsp.show',
                                    [
                                        'hsp' => $item->id,
                                        'region' => request()->integer('region')
                                            ?: $regionId,
                                    ]
                                ) }}"
                                class="btn primary"
                            >
                                Lihat Analisa
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="8"
                            style="text-align: center;"
                        >
                            Data HSP tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>

@if ($hsp->hasPages())
<div class="pagination-wrap">
    <div class="page-nav">
        @if ($hsp->onFirstPage())
            <span class="page-btn disabled">&#8592; Sebelumnya</span>
        @else
            <a class="page-btn primary" href="{{ $hsp->previousPageUrl() }}">&#8592; Sebelumnya</a>
        @endif

        <span class="page-info">
            Halaman {{ $hsp->currentPage() }} dari {{ $hsp->lastPage() }}
            ({{ $hsp->total() }} data)
        </span>

        @if ($hsp->hasMorePages())
            <a class="page-btn primary" href="{{ $hsp->nextPageUrl() }}">Berikutnya &#8594;</a>
        @else
            <span class="page-btn disabled">Berikutnya &#8594;</span>
        @endif
    </div>
</div>
@endif

@endsection
