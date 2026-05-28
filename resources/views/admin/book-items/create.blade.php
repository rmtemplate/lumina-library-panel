@extends('layouts.admin')
@section('title','Create Physical Copy')
@section('page-title','Create Physical Copy')
@section('page-description','Tambahkan copy fisik untuk judul buku tertentu pada branch dan rak tertentu.')
@section('content')<form method="POST" action="{{ route('admin.book-items.store') }}">@include('admin.book-items._form')</form>@endsection
