@extends('layouts.admin')
@section('title','Categories')
@section('page-title','Book Categories')
@section('page-description','Global category master untuk katalog, statistik, dan future recommendation engine Lumina Library. Hanya Super Admin yang dapat mengelola data ini.')
@section('page-actions')
    @if($canManageCategory)
        <a href="{{ route('admin.categories.create') }}" class="btn-primary cursor-pointer"><x-admin.icon name="plus" class="h-4 w-4" />Create Category</a>
    @endif
@endsection
@section('content')
<x-admin.filters search-placeholder="Search category name, code, or slug">
    <select name="status" class="form-select w-full sm:w-40">
        <option value="">All Status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
    </select>
</x-admin.filters>
<div class="table-card">
    <div class="table-scroll">
        <table class="modern-table min-w-[900px]">
            <thead><tr><th>Category</th><th>Slug</th><th>Books</th><th>Sort</th><th>Status</th>@if($canManageCategory)<th class="text-right">Action</th>@endif</tr></thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#c5c6ce] bg-[#f5f3f6]">
                                @if($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-full w-full object-cover">
                                @else
                                    <x-admin.icon :name="$category->icon_name ?: 'sell'" class="h-6 w-6 text-[#006c49]" />
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-[#191c1d]">{{ $category->name }}</div>
                                <div class="font-mono text-xs text-[#75777e]">{{ $category->code }}</div>
                                <p class="mt-1 max-w-xl truncate text-xs text-[#44474d]">{{ $category->description ?: '-' }}</p>
                            </div>
                        </div>
                    </td>
                    <td><span class="font-mono text-xs text-[#44474d]">{{ $category->slug }}</span></td>
                    <td><x-admin.badge variant="primary">{{ $category->books_count }} books</x-admin.badge></td>
                    <td>{{ $category->sort_order }}</td>
                    <td><x-admin.badge :status="$category->is_active ? 'active' : 'inactive'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                    @if($canManageCategory)
                        <td class="table-action-cell"><x-admin.action-menu><x-admin.action-link :href="route('admin.categories.edit',$category)" icon="pencil-square">Edit</x-admin.action-link><form method="POST" action="{{ route('admin.categories.destroy',$category) }}" class="js-delete-form" data-delete-label="{{ $category->name }}">@csrf @method('DELETE')<x-admin.action-button icon="trash" danger>Delete</x-admin.action-button></form></x-admin.action-menu></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canManageCategory ? 6 : 5 }}"><x-admin.empty-state title="No categories found" description="Lumina Library belum memiliki category yang sesuai filter." icon="tag" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-admin.pagination :paginator="$categories" />
</div>
@endsection
