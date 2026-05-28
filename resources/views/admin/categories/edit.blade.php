@extends('layouts.admin')
@section('title', 'Edit Category')
@section('page-title', 'Edit Category')
@section('page-description', 'Update metadata, icon, dan urutan category global Lumina Library.')
@section('page-actions')<a href="{{ route('admin.categories.index') }}" class="btn-outline cursor-pointer"><x-admin.icon name="arrow_back" class="h-4 w-4" />Back</a>@endsection
@section('content')
@include('admin.categories._form', ['category' => $category])
@endsection
