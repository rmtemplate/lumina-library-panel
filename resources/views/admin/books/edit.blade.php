@extends('layouts.admin')
@section('title','Edit Book Title')
@section('page-title','Edit Book Title')
@section('page-description','Perbarui metadata judul buku tanpa mengubah copy fisik.')
@section('content')<form method="POST" action="{{ route('admin.books.update', $book) }}" enctype="multipart/form-data">@method('PUT')@include('admin.books._form')</form>@endsection
