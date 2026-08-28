@extends('layouts.admin')

@section('title', 'Data HSP')
@section('page-title', 'Data Harga Satuan Pekerjaan')

@section('content')

{{-- Tambah Pekerjaan Baru --}}
<form
    id="add-hsp-form"
    class="card"
    style="margin-top: 20px;"
    method="POST"
    action="{{ route('admin.hsp.store') }}"
>
    @csrf
    <h3>Tambah Pekerjaan Baru</h3>

    @if ($errors->any())
        <div class="alert danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-grid">
        <div>
            <label for="add-period">
                Periode
            </label>

            <select
                id="add-period"
                name="period_id"
                required
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

        <div>
            <label for="add-category">
                Kategori
            </label>

            <select
                id="add-category"
                name="category_id"
            >
                <option value="">-</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">
                        {{ $cat->code }} - {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="add-code">
                Kode Pekerjaan
            </label>

            <input
                id="add-code"
                type="text"
                name="work_code"
                value="{{ old('work_code') }}"
                required
                placeholder="PE-001"
            >
        </div>

        <div>
            <label for="add-unit">
                Satuan
            </label>

            <input
                id="add-unit"
                type="text"
                name="unit"
                value="{{ old('unit') }}"
                placeholder="m³"
            >
        </div>

        {{-- Semua pekerjaan baru langsung aktif. --}}
        <input type="hidden" name="is_active" value="1">

        <div class="full">
            <label for="add-description">
                Uraian Pekerjaan
            </label>

            <textarea
                id="add-description"
                name="description"
                rows="2"
                required
            >{{ old('description') }}</textarea>
        </div>
    </div>

    <div style="margin-top: 15px;">
        <button
            type="submit"
            class="btn primary"
        >
            Simpan
        </button>

        <span class="muted" style="margin-left: 10px;">
            Material, Jasa, dan Harga terisi otomatis dari analisa (Lihat Analisa).
        </span>
    </div>
</form>

{{-- Filter Data HSP --}}
<form
    id="hsp-filter-form"
    method="GET"
    action="{{ route('admin.hsp.index') }}"
>
    <div class="card" style="margin-top: 20px;">
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

        <table class="data-table hsp-list-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode</th>
                    <th>Uraian Pekerjaan</th>
                    <th>Satuan</th>
                    <th class="num">Material</th>
                    <th class="num">Jasa</th>
                    <th class="num">Harga</th>
                    <th class="num">TKDN</th>
                    <th style="text-align: center;">Aksi</th>
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

                        <td class="num">
                            {{ $item->tkdn_percent !== null
                                ? number_format(
                                    (float) $item->tkdn_percent,
                                    2,
                                    ',',
                                    '.'
                                ) . '%'
                                : ''
                            }}
                        </td>

                        <td>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">

                            <a
                                href="{{ route(
                                    'admin.hsp.show',
                                    [
                                        'hsp' => $item->id,
                                        'region' => request()->integer('region')
                                            ?: $regionId,
                                    ]
                                ) }}"
                                class="btn primary small"
                                title="Lihat Analisa"
                            >
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>

                            <a
                                href="{{ route(
                                    'admin.hsp.edit',
                                    $item->id
                                ) }}"
                                class="btn small"
                                title="Edit"
                            >
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                            </a>

                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.hsp.destroy',
                                    $item->id
                                ) }}"
                                style="display: inline-flex;"
                                onsubmit="return confirm('Hapus pekerjaan {{ $item->work_code }}?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn small"
                                    title="Hapus"
                                    style="
                                        color: #b91c1c;
                                        border-color: #fecaca;
                                        background: #fef2f2;
                                    "
                                >
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    ><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty-state', [
                        'colspan' => 9,
                        'message' => 'Data HSP tidak ditemukan. Coba ubah kata kunci atau filter lainnya.',
                        'resetUrl' => route('admin.hsp.index'),
                    ])
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
