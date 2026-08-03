@extends('layouts.admin')

@section('title', 'Data AHS')
@section('page-title', 'Data Analisa Harga Satuan')

@section('content')

{{-- Filter Data AHS --}}
<form
    method="GET"
    action="{{ route('admin.ahs.index') }}"
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
                            @selected($periodId == $period->id)
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
                            @selected($regionId == $region->id)
                        >
                            {{ $region->name }}
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
                href="{{ route('admin.ahs.index') }}"
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

{{-- Tabel Data AHS --}}
<div
    class="card"
    style="margin-top: 20px;"
>
    <div class="table-responsive">

        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode HSP</th>
                    <th>Kode BINKON</th>
                    <th>Uraian Pekerjaan</th>
                    <th>Satuan</th>
                    <th>Jumlah Komponen</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($ahs as $item)
                    <tr>
                        <td>
                            {{ $ahs->firstItem() + $loop->index }}
                        </td>

                        <td>
                            {{ $item->work_code }}
                        </td>

                        <td>
                            {{ $item->binkon_code ?: '-' }}
                        </td>

                        <td>
                            {{ $item->description }}
                        </td>

                        <td>
                            {{ $item->unit ?: '-' }}
                        </td>

                        <td>
                            {{ $item->components_count }}
                        </td>

                        <td>
                            <a
                                href="{{ route(
                                    'admin.hsp.show',
                                    [
                                        'hsp' => $item->id,
                                        'region' => $regionId,
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
                            colspan="7"
                            style="text-align: center;"
                        >
                            Data AHS tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>

{{-- Pagination --}}
<div style="margin-top: 20px;">
    {{ $ahs->links() }}
</div>

@endsection
