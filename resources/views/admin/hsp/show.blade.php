@extends('layouts.admin')
@section('title', 'Detail AHSP')
@section('page-title', 'Detail Analisa Harga Satuan Pekerjaan')
@section('breadcrumb')
    <a href="{{ route('admin.hsp.index') }}">Data HSP</a>
    <span>/</span>
    <span>{{ $hsp->work_code }}</span>
@endsection
@section('content')
@include('user.hsp._detail', [
    'backUrl' => route('admin.hsp.index'),
    'showExport' => true,
    'exportUrl' => route('admin.export.per-analisa', ['hsp_id' => $hsp->id, 'region' => $regionId]),
])
@endsection
