@extends('layouts.admin')

@section('title', 'Upah, Bahan & Alat')
@section('page-title', 'Data Upah, Bahan & Alat')

@section('content')

<div class="card basic-item-create">
    <form
        method="POST"
        action="{{ route('admin.basic-items.store') }}"
    >
        @csrf

        <input
            type="hidden"
            name="period_id"
            value="{{ $periodId }}"
        >

        <input
            type="hidden"
            name="return_region_id"
            value="{{ $regionId }}"
        >

        <h3>Tambah Upah, Bahan &amp; Alat</h3>

        <div class="form-grid">
            <div>
                <label for="new-item-type">Jenis</label>

                <select
                    id="new-item-type"
                    name="item_type"
                    required
                >
                    <option
                        value=""
                        @selected(old('item_type') === '')
                    >
                        Pilih Jenis
                    </option>

                    <option
                        value="labor"
                        @selected(old('item_type') === 'labor')
                    >
                        Upah
                    </option>

                    <option
                        value="material"
                        @selected(old('item_type') === 'material')
                    >
                        Bahan
                    </option>

                    <option
                        value="equipment"
                        @selected(old('item_type') === 'equipment')
                    >
                        Alat
                    </option>

                    <option
                        value="dkd"
                        @selected(old('item_type') === 'dkd')
                    >
                        DKD
                    </option>
                </select>
            </div>

            <div>
                <label for="new-item-description">Uraian</label>

                <input
                    id="new-item-description"
                    type="text"
                    name="description"
                    value="{{ old('description') }}"
                    placeholder="Contoh: Juru Gambar"
                    required
                >
            </div>

            <div>
                <label for="new-item-unit">Satuan</label>

                <input
                    id="new-item-unit"
                    type="text"
                    name="unit"
                    value="{{ old('unit') }}"
                    placeholder="Contoh: OH"
                >
            </div>

            <div>
                <label for="new-item-price">Harga</label>

                <input
                    id="new-item-price"
                    type="number"
                    name="price"
                    value="{{ old('price') }}"
                    min="0"
                    step="0.01"
                    placeholder="0"
                >
            </div>
        </div>

        <div style="margin-top: 15px;">
            <button type="submit" class="btn primary">
                + Tambah
            </button>
        </div>
    </form>
</div>

<form
    method="GET"
    action="{{ route('admin.basic-items.index') }}"
>
    <div class="card basic-item-filter">
        <div class="form-grid">

            <div>
                <label for="period">Periode</label>

                <select
                    id="period"
                    name="period"
                    onchange="this.form.submit()"
                >
                    @foreach ($periods as $period)
                        <option
                            value="{{ $period->id }}"
                            @selected($periodId == $period->id)
                        >
                            {{ $period->year }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="region">Wilayah</label>

                <select
                    id="region"
                    name="region"
                    onchange="this.form.submit()"
                >
                    @foreach ($regions as $region)
                        <option
                            value="{{ $region->id }}"
                            @selected($regionId == $region->id)
                        >
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="type">Jenis</label>

                <select
                    id="type"
                    name="type"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua Jenis</option>

                    <option
                        value="labor"
                        @selected($type === 'labor')
                    >
                        Upah
                    </option>

                    <option
                        value="material"
                        @selected($type === 'material')
                    >
                        Bahan
                    </option>

                    <option
                        value="equipment"
                        @selected($type === 'equipment')
                    >
                        Alat
                    </option>

                    <option
                        value="dkd"
                        @selected($type === 'dkd')
                    >
                        DKD
                    </option>
                </select>
            </div>

            <div>
                <label for="q">Pencarian</label>

                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari kode, uraian, atau satuan"
                    oninput="filterDebounce(this.form)"
                >
            </div>

        </div>

        <div style="margin-top: 15px;">
            <a
                href="{{ route('admin.basic-items.index') }}"
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

<div class="card" style="margin-top: 20px;">
    <div class="table-responsive">

        <table class="data-table basic-items-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Jenis</th>
                    <th>Kode</th>
                    <th>Uraian</th>
                    <th>Satuan</th>
                    <th class="num">Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $item)

                    @php
                        $price = $item->prices->first();

                        $typeLabel = match ($item->item_type) {
                            'labor' => 'Upah',
                            'material' => 'Bahan',
                            'equipment' => 'Alat',
                            'dkd' => 'DKD',
                            default => '-',
                        };
                    @endphp

                    <tr>
                        <td>
                            {{ $items->firstItem() + $loop->index }}
                        </td>

                        <td>
                            {{ $typeLabel }}
                        </td>

                        <td>
                            {{ $item->code ?: '-' }}
                        </td>

                        <td>
                            {{ $item->description }}
                        </td>

                        <td>
                            {{ $item->unit ?: '-' }}
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
                                    'admin.basic-items.edit',
                                    [
                                        'basic_item' => $item->id,
                                        'period' => $periodId,
                                        'region' => $regionId,
                                    ]
                                ) }}"
                                class="btn primary"
                            >
                                Edit
                            </a>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td
                            colspan="7"
                            style="text-align: center;"
                        >
                            Data Upah, Bahan, dan Alat tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>

<div style="margin-top: 20px;">
    @if ($items->hasPages())
<div class="pagination-wrap">
    <div class="page-nav">
        @if ($items->onFirstPage())
            <span class="page-btn disabled">&#8592; Sebelumnya</span>
        @else
            <a class="page-btn primary" href="{{ $items->previousPageUrl() }}">&#8592; Sebelumnya</a>
        @endif

        <span class="page-info">
            Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}
            ({{ $items->total() }} data)
        </span>

        @if ($items->hasMorePages())
            <a class="page-btn primary" href="{{ $items->nextPageUrl() }}">Berikutnya &#8594;</a>
        @else
            <span class="page-btn disabled">Berikutnya &#8594;</span>
        @endif
    </div>
</div>
@endif
</div>

@endsection
