@extends('layouts.admin')
@section('title','Banners')
@section('page-title','Banners')
@section('page-description','Kelola banner promosi dan informasi pada aplikasi.')
@section('page-actions')
    @if(auth()->user()?->isSuperAdmin())
        <a href="{{ route('admin.banners.create') }}" class="btn-primary"><x-admin.icon name="plus" class="h-4 w-4" />Create Banner</a>
    @else
        <span class="inline-flex items-center gap-2 rounded-lg bg-[#f5f3f6] px-4 py-2.5 text-sm font-semibold text-[#44474d] ring-1 ring-[#c5c6ce]"><x-admin.icon name="shield-check" class="h-4 w-4" />Create banner hanya untuk Super Admin</span>
    @endif
@endsection
@section('content')
<x-admin.filters search-placeholder="Search banner title" />
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($banners as $banner)
    <div class="lumina-card overflow-hidden"><div class="flex h-40 items-center justify-center bg-[#f3f4f5]">@if($banner->image_url)<img src="{{ $banner->image_url }}" class="h-full w-full object-cover" alt="{{ $banner->title }}">@else<x-admin.icon name="photo" class="h-10 w-10 text-[#75777e]" />@endif</div><div class="p-5"><div class="flex items-start justify-between gap-3"><div><div class="font-bold">{{ $banner->title }}</div><p class="mt-1 line-clamp-2 text-sm text-[#44474d]">{{ $banner->subtitle ?: '-' }}</p></div><x-admin.badge :status="$banner->is_active ? 'active' : 'inactive'">{{ $banner->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></div><div class="mt-4 flex items-center justify-between text-xs text-[#75777e]"><span>Sort {{ $banner->sort_order }}</span><span>{{ optional($banner->start_date)->format('d M Y') ?: '-' }} - {{ optional($banner->end_date)->format('d M Y') ?: '-' }}</span></div><div class="mt-5 flex justify-end gap-2"><a href="{{ route('admin.banners.edit',$banner) }}" class="btn-primary btn-sm">Edit</a><form method="POST" action="{{ route('admin.banners.destroy',$banner) }}" class="js-delete-form" data-delete-label="{{ $banner->title }}">@csrf @method('DELETE')<button class="btn-danger btn-sm">Delete</button></form></div></div></div>
@empty
    <div class="md:col-span-2 xl:col-span-3"><x-admin.empty-state title="No banners found" description="Create banners to highlight information in the app." icon="photo" /></div>
@endforelse
</div><x-admin.pagination :paginator="$banners" />
@endsection
