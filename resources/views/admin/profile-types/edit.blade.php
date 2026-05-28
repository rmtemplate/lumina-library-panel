@extends('layouts.admin')
@section('title','Edit Profile Type')
@section('page-title','Edit Profile Type')
@section('page-description','Update rules field dinamis untuk user/member Lumina Library.')
@section('page-actions')<a href="{{ route('admin.profile-types.index') }}" class="btn-outline cursor-pointer"><x-admin.icon name="arrow_back" class="h-4 w-4" />Back</a>@endsection
@section('content')@include('admin.profile-types._form', ['profileType' => $profileType])@endsection
