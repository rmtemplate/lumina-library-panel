@extends('layouts.admin')
@section('title','Create Profile Type')
@section('page-title','Create Profile Type')
@section('page-description','Master profile pendidikan/pekerjaan untuk user, member, recommendation, dan statistik Lumina Library.')
@section('page-actions')<a href="{{ route('admin.profile-types.index') }}" class="btn-outline cursor-pointer"><x-admin.icon name="arrow_back" class="h-4 w-4" />Back</a>@endsection
@section('content')@include('admin.profile-types._form')@endsection
