@extends('layouts.user')
@section('title', 'Detail AHSP')
@section('content')
@include('user.hsp._detail', ['backUrl' => route('hsp.index', ['region'=>$regionId])])
@endsection
