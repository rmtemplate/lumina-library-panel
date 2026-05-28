@extends('layouts.admin')
@section('title','Branches')
@section('page-title','Library Branches')
@section('page-description','Kelola cabang perpustakaan, alamat, jadwal, dan kontak.')
@section('page-actions')<a href="{{ route('admin.branches.create') }}" class="btn-primary"><x-admin.icon name="plus" class="h-4 w-4" />Create Branch</a>@endsection
@section('content')
<x-admin.filters search-placeholder="Search branch code or name" />
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($branches as $branch)
    <div class="lumina-card p-5"><div class="flex items-start justify-between gap-4"><div><div class="font-bold">{{ $branch->name }}</div><div class="mt-1 font-mono text-xs text-[#75777e]">{{ $branch->code }}</div></div><x-admin.badge :status="$branch->is_active ? 'active' : 'inactive'">{{ $branch->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></div><p class="mt-4 line-clamp-2 text-sm leading-6 text-[#44474d]">{{ $branch->address ?: 'No address.' }}</p><div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-[#f3f4f5] p-3"><div class="text-lg font-bold">{{ $branch->book_items_count }}</div><div class="text-xs text-[#44474d]">Book copies</div></div><div class="rounded-2xl bg-[#f3f4f5] p-3"><div class="text-lg font-bold">{{ $branch->racks_count }}</div><div class="text-xs text-[#44474d]">Racks</div></div></div><div class="mt-5 flex justify-end gap-2"><a href="{{ route('admin.branches.show',$branch) }}" class="btn-outline btn-sm">Detail</a><a href="{{ route('admin.branches.edit',$branch) }}" class="btn-primary btn-sm">Edit</a><form method="POST" action="{{ route('admin.branches.destroy',$branch) }}" class="js-delete-form" data-delete-label="{{ $branch->name }}">@csrf @method('DELETE')<button class="btn-danger btn-sm">Delete</button></form></div></div>
@empty
    <div class="md:col-span-2 xl:col-span-3"><x-admin.empty-state title="No branches found" description="Create a library branch to start managing stock and racks." icon="building-office" /></div>
@endforelse
</div><x-admin.pagination :paginator="$branches" />
@endsection
