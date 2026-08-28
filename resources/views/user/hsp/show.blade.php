@extends('layouts.user')
@section('title', 'Detail AHSP')
@section('content')
<div class="public-breadcrumb">
    <a href="{{ route('hsp.index') }}">Beranda</a>
    <span>/</span>
    <span>{{ $hsp->work_code }}</span>
</div>
@include('user.hsp._detail', ['backUrl' => route('hsp.index', ['region'=>$regionId])])
@endsection
