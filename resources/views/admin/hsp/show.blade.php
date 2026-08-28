@extends('layouts.admin')
@section('title', 'Detail AHSP')
@section('page-title', 'Detail Analisa Harga Satuan Pekerjaan')
@section('breadcrumb')
    <a href="{{ route('admin.hsp.index') }}">Data HSP</a>
    <span>/</span>
    <span>{{ $hsp->work_code }}</span>
@endsection
@section('content')
@if (session('error'))
    <div class="alert danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert" style="background: #ecfdf5; color: #065f46; border-color: #a7f3d0;">{{ session('success') }}</div>
@endif
@include('user.hsp._detail', [
    'backUrl' => route('admin.hsp.index'),
    'showExport' => true,
    'exportUrl' => route('admin.export.per-analisa', ['hsp_id' => $hsp->id, 'region' => $regionId]),
    'basicItems' => $basicItems,
    'componentsByType' => $componentsByType,
])
@endsection
