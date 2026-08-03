@extends('layouts.admin')

@section('title', 'Edit Upah, Bahan & Alat')
@section('page-title', 'Edit Upah, Bahan & Alat')
@section('breadcrumb')
    <a href="{{ route('admin.basic-items.index') }}">Upah, Bahan & Alat</a>
    <span>/</span>
    <span>Edit</span>
@endsection
@section('header-actions')
    <a class="btn" href="{{ route('admin.basic-items.index', ['period' => $periodId, 'region' => $regionId, 'type' => $basicItem->item_type]) }}">Kembali</a>
    <button class="btn primary" form="basic-item-form">Simpan Perubahan</button>
@endsection

@section('content')

<form
    id="basic-item-form"
    class="card stack"
    method="POST"
    action="{{ route(
        'admin.basic-items.update',
        $basicItem
    ) }}"
>
    @csrf
    @method('PUT')

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

    <div class="form-grid">
        <label>
            Kode

            <input
                type="text"
                value="{{ $basicItem->code }}"
                readonly
            >
        </label>

        <label>
            Jenis

            <select
                name="item_type"
                required
            >
                <option
                    value="labor"
                    @selected(
                        old(
                            'item_type',
                            $basicItem->item_type
                        ) === 'labor'
                    )
                >
                    Upah
                </option>

                <option
                    value="material"
                    @selected(
                        old(
                            'item_type',
                            $basicItem->item_type
                        ) === 'material'
                    )
                >
                    Bahan
                </option>

                <option
                    value="equipment"
                    @selected(
                        old(
                            'item_type',
                            $basicItem->item_type
                        ) === 'equipment'
                    )
                >
                    Alat
                </option>

                <option
                    value="dkd"
                    @selected(
                        old(
                            'item_type',
                            $basicItem->item_type
                        ) === 'dkd'
                    )
                >
                    DKD
                </option>
            </select>
        </label>

        <label>
            Satuan

            <input
                type="text"
                name="unit"
                value="{{ old(
                    'unit',
                    $basicItem->unit
                ) }}"
            >
        </label>

        <label>
            Periode Harga

            <input
                type="text"
                value="{{ $periods
                    ->firstWhere('id', $periodId)
                    ?->year }}"
                readonly
            >
        </label>

        <label class="full">
            Uraian

            <textarea
                name="description"
                rows="3"
                required
            >{{ old(
                'description',
                $basicItem->description
            ) }}</textarea>
        </label>

        <label class="check full">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(
                    old(
                        'is_active',
                        $basicItem->is_active
                    )
                )
            >

            Data aktif
        </label>
    </div>

            <h3>
        Harga Referensi per Wilayah
    </h3>

    <div class="table-responsive">
        <table class="data-table reference-price-table">
            <thead>
                <tr>
                    <th>Wilayah</th>
                    <th>Harga Referensi 1</th>
                    <th>Link Referensi 1</th>
                    <th>Harga Referensi 2</th>
                    <th>Link Referensi 2</th>
                    <th>Harga Terpilih</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($regions as $region)
                    @php
                        $price = $basicItem->prices
                            ->firstWhere(
                                'region_id',
                                $region->id
                            );
                        $rp1 = $price?->reference_price_1;
                        $rp2 = $price?->reference_price_2;
                        $selected = $rp1 !== null && $rp2 !== null
                            ? min($rp1, $rp2)
                            : ($rp1 ?? $rp2 ?? 0);
                    @endphp

                    <tr>
                        <td>
                            {{ $region->name }}
                        </td>

                        <td>
                            <input
                                type="number"
                                class="price-reference-input ref1"
                                data-region="{{ $region->id }}"
                                name="prices[
                                    {{ $region->id }}
                                ][reference_price_1]"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'prices.'
                                    . $region->id
                                    . '.reference_price_1',
                                    $rp1
                                ) }}"
                            >
                        </td>

                        <td>
                            <input
                                type="text"
                                class="link-reference-input"
                                name="prices[
                                    {{ $region->id }}
                                ][reference_link_1]"
                                value="{{ old(
                                    'prices.'
                                    . $region->id
                                    . '.reference_link_1',
                                    $price?->reference_link_1
                                ) }}"
                                placeholder="URL atau nama sumber"
                            >
                        </td>

                        <td>
                            <input
                                type="number"
                                class="price-reference-input ref2"
                                data-region="{{ $region->id }}"
                                name="prices[
                                    {{ $region->id }}
                                ][reference_price_2]"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'prices.'
                                    . $region->id
                                    . '.reference_price_2',
                                    $rp2
                                ) }}"
                            >
                        </td>

                        <td>
                            <input
                                type="text"
                                class="link-reference-input"
                                name="prices[
                                    {{ $region->id }}
                                ][reference_link_2]"
                                value="{{ old(
                                    'prices.'
                                    . $region->id
                                    . '.reference_link_2',
                                    $price?->reference_link_2
                                ) }}"
                                placeholder="URL atau nama sumber"
                            >
                        </td>

                        <td>
                            <input
                                type="number"
                                class="selected-price"
                                name="prices[
                                    {{ $region->id }}
                                ][price]"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'prices.'
                                    . $region->id
                                    . '.price',
                                    $price?->price ?? $selected
                                ) }}"
                                readonly
                                style="background:#f3f4f6;font-weight:600;"
                            >
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</form>

<script>
document.querySelectorAll('.price-reference-input').forEach(function(input) {
    input.addEventListener('input', function() {
        var region = this.dataset.region;
        var rp1 = parseFloat(document.querySelector('.ref1[data-region="' + region + '"]').value) || 0;
        var rp2 = parseFloat(document.querySelector('.ref2[data-region="' + region + '"]').value) || 0;
        var selected = document.querySelector('.selected-price[name="prices[' + region + '][price]"]');
        if (rp1 > 0 && rp2 > 0) {
            selected.value = Math.min(rp1, rp2);
        } else if (rp1 > 0) {
            selected.value = rp1;
        } else if (rp2 > 0) {
            selected.value = rp2;
        } else {
            selected.value = 0;
        }
    });
});
</script>

@endsection
