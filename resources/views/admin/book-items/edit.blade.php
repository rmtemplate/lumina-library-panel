@extends('layouts.admin')
@section('title','Edit Physical Copy')
@section('page-title','Edit Physical Copy')
@section('page-description','Perbarui status dan lokasi copy fisik.')
@section('content')<form method="POST" action="{{ route('admin.book-items.update', $item) }}">@method('PUT')@include('admin.book-items._form')</form>@endsection
