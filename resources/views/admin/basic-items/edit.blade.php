@extends('layouts.admin')

@section('title', 'Edit Upah, Bahan & Alat')
@section('page-title', 'Edit Upah, Bahan & Alat')
@section('breadcrumb')
    <a href="{{ route('admin.basic-items.index') }}">Upah, Bahan & Alat</a>
    <span>/</span>
    <span>Edit</span>
@endsection
@section('header-actions')
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
                            @php
                                $rp1Raw = old('prices.' . $region->id . '.reference_price_1', $rp1);
                                $rp1Display = $rp1Raw !== null && $rp1Raw !== '' ? number_format((float) str_replace(',', '.', (string) $rp1Raw), 3, ',', '.') : '';
                            @endphp
                            <input
                                type="text"
                                inputmode="decimal"
                                class="price-reference-input ref1"
                                data-region="{{ $region->id }}"
                                name="prices[{{ $region->id }}][reference_price_1]"
                                value="{{ $rp1Display }}"
                                placeholder="0,000"
                            >
                        </td>

                        <td>
                            @php
                                 $ref1 = trim(
                                     (string) ($price?->reference_link_1 ?? '')
                                 );
                                 $ref1Url = trim(
                                     (string) ($price?->reference_url_1 ?? '')
                                 );
                                 $ref1Low = strtolower($ref1);
                                 $ref1Href = $ref1Url !== '' ? $ref1Url : null;

                                 if ($ref1Href === null && $ref1 !== '') {
                                    if (
                                        str_starts_with($ref1Low, 'http://')
                                        || str_starts_with($ref1Low, 'https://')
                                    ) {
                                        $ref1Href = $ref1;
                                    } elseif (str_contains($ref1, '.')) {
                                        $ref1Href = 'https://' . ltrim($ref1, '/');
                                    }
                                }
                            @endphp

                            <div class="ref-link-wrap">
                                <div class="ref-link-display">
                                    @if ($ref1Href !== null)
                                        <a
                                            class="ref-link-mini"
                                            href="{{ $ref1Href }}"
                                            target="_blank"
                                            rel="noopener"
                                            title="Buka: {{ $ref1 }}"
                                        >
                                            <span class="ref-link-label">{{ $ref1 }}</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    @elseif ($ref1 !== '')
                                        <span class="ref-link-text">{{ $ref1 }}</span>
                                    @else
                                        <span class="ref-link-empty">Belum ada link</span>
                                    @endif

                                    <button
                                        type="button"
                                        class="ref-link-edit"
                                        data-action="edit"
                                        title="Ubah link"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </div>

                                <input
                                    type="text"
                                    class="link-reference-input"
                                    name="prices[{{ $region->id }}][reference_link_1]"
                                    value="{{ old(
                                        'prices.'
                                        . $region->id
                                        . '.reference_link_1',
                                        $price?->reference_link_1
                                    ) }}"
                                    placeholder="URL atau nama sumber"
                                    hidden
                                >
                            </div>
                        </td>

                        <td>
                            @php
                                $rp2Raw = old('prices.' . $region->id . '.reference_price_2', $rp2);
                                $rp2Display = $rp2Raw !== null && $rp2Raw !== '' ? number_format((float) str_replace(',', '.', (string) $rp2Raw), 3, ',', '.') : '';
                            @endphp
                            <input
                                type="text"
                                inputmode="decimal"
                                class="price-reference-input ref2"
                                data-region="{{ $region->id }}"
                                name="prices[{{ $region->id }}][reference_price_2]"
                                value="{{ $rp2Display }}"
                                placeholder="0,000"
                            >
                        </td>

                        <td>
                            @php
                                 $ref2 = trim(
                                     (string) ($price?->reference_link_2 ?? '')
                                 );
                                 $ref2Url = trim(
                                     (string) ($price?->reference_url_2 ?? '')
                                 );
                                 $ref2Low = strtolower($ref2);
                                 $ref2Href = $ref2Url !== '' ? $ref2Url : null;

                                 if ($ref2Href === null && $ref2 !== '') {
                                    if (
                                        str_starts_with($ref2Low, 'http://')
                                        || str_starts_with($ref2Low, 'https://')
                                    ) {
                                        $ref2Href = $ref2;
                                    } elseif (str_contains($ref2, '.')) {
                                        $ref2Href = 'https://' . ltrim($ref2, '/');
                                    }
                                }
                            @endphp

                            <div class="ref-link-wrap">
                                <div class="ref-link-display">
                                    @if ($ref2Href !== null)
                                        <a
                                            class="ref-link-mini"
                                            href="{{ $ref2Href }}"
                                            target="_blank"
                                            rel="noopener"
                                            title="Buka: {{ $ref2 }}"
                                        >
                                            <span class="ref-link-label">{{ $ref2 }}</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    @elseif ($ref2 !== '')
                                        <span class="ref-link-text">{{ $ref2 }}</span>
                                    @else
                                        <span class="ref-link-empty">Belum ada link</span>
                                    @endif

                                    <button
                                        type="button"
                                        class="ref-link-edit"
                                        data-action="edit"
                                        title="Ubah link"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </div>

                                <input
                                    type="text"
                                    class="link-reference-input"
                                    name="prices[{{ $region->id }}][reference_link_2]"
                                    value="{{ old(
                                        'prices.'
                                        . $region->id
                                        . '.reference_link_2',
                                        $price?->reference_link_2
                                    ) }}"
                                    placeholder="URL atau nama sumber"
                                    hidden
                                >
                            </div>
                        </td>

                        <td>
                            @php
                                $selectedRaw = old('prices.' . $region->id . '.price', $price?->price ?? $selected);
                                $selectedDisplay = $selectedRaw !== null && $selectedRaw !== '' ? number_format((float) str_replace(',', '.', (string) $selectedRaw), 3, ',', '.') : number_format(0, 3, ',', '.');
                            @endphp
                            <input
                                type="text"
                                class="selected-price"
                                name="prices[{{ $region->id }}][price]"
                                value="{{ $selectedDisplay }}"
                                readonly
                                style="background:#f3f4f6;font-weight:600;"
                            >
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:16px; border-top:1px solid #e5e7eb;">
        <a class="btn" href="{{ route('admin.basic-items.index', ['period' => $periodId, 'region' => $regionId, 'type' => $basicItem->item_type]) }}" style="background:linear-gradient(180deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%); color:#fff; border-color:#991b1b; box-shadow:0 2px 8px rgba(220,38,38,.3);">Kembali</a>
        <button type="submit" class="btn primary">Simpan Perubahan</button>
    </div>

</form>

<script>
function parseIdPrice(v) {
    if (!v) return 0;
    v = v.trim().replace(/\./g, '').replace(',', '.');
    var n = parseFloat(v);
    return isNaN(n) ? 0 : n;
}
function formatIdPrice(n) {
    return n.toLocaleString('id-ID', {minimumFractionDigits: 3, maximumFractionDigits: 3});
}
document.querySelectorAll('.price-reference-input').forEach(function(input) {
    input.addEventListener('input', function() {
        var region = this.dataset.region;
        var rp1 = parseIdPrice(document.querySelector('.ref1[data-region="' + region + '"]').value);
        var rp2 = parseIdPrice(document.querySelector('.ref2[data-region="' + region + '"]').value);
        var selected = document.querySelector('.selected-price[name="prices[' + region + '][price]"]');
        if (rp1 > 0 && rp2 > 0) {
            selected.value = formatIdPrice(Math.min(rp1, rp2));
        } else if (rp1 > 0) {
            selected.value = formatIdPrice(rp1);
        } else if (rp2 > 0) {
            selected.value = formatIdPrice(rp2);
        } else {
            selected.value = formatIdPrice(0);
        }
    });
    input.addEventListener('blur', function() {
        var n = parseIdPrice(this.value);
        this.value = n > 0 ? formatIdPrice(n) : '';
        this.dispatchEvent(new Event('input'));
    });
});
document.getElementById('basic-item-form').addEventListener('submit', function() {
    document.querySelectorAll('.price-reference-input, .selected-price').forEach(function(el) {
        var n = parseIdPrice(el.value);
        el.value = n > 0 ? n.toFixed(3) : '';
    });
});

document.querySelectorAll('.ref-link-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var wrap = this.closest('.ref-link-wrap');
        wrap.querySelector('.ref-link-display').hidden = true;
        wrap.querySelector('.link-reference-input').hidden = false;
        wrap.querySelector('.link-reference-input').focus();
    });
});
</script>

@endsection
