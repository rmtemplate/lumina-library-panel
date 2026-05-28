@extends('layouts.admin')
@section('title','Create Book Title')
@section('page-title','Create Book Title')
@section('page-description','Buat master judul buku. Copy fisik ditambahkan setelah master judul tersedia.')
@section('content')<form method="POST" action="{{ route('admin.books.store') }}" enctype="multipart/form-data">@include('admin.books._form')</form>@endsection
