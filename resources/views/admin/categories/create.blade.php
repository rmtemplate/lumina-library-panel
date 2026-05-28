@extends('layouts.admin')
@section('title', 'Create Category')
@section('page-title', 'Create Category')
@section('page-description', 'Category hanya dikelola oleh Super Admin dan digunakan oleh seluruh branch Lumina Library.')
@section('page-actions')<a href="{{ route('admin.categories.index') }}" class="btn-outline cursor-pointer"><x-admin.icon name="arrow_back" class="h-4 w-4" />Back</a>@endsection
@section('content')
@include('admin.categories._form')
@endsection
