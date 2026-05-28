@extends('layouts.admin')
@section('title','Profile Types')
@section('page-title','Profile Types')
@section('page-description','Master segmentasi user/member untuk form registrasi, statistik, dan recommendation preparation.')
@section('page-actions')<a href="{{ route('admin.profile-types.create') }}" class="btn-primary cursor-pointer"><x-admin.icon name="plus" class="h-4 w-4" />Create Profile Type</a>@endsection
@section('content')
<x-admin.filters search-placeholder="Search profile type">
    <select name="status" class="form-select w-full sm:w-40"><option value="">All Status</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
</x-admin.filters>
<div class="table-card"><div class="table-scroll"><table class="modern-table min-w-[940px]"><thead><tr><th>Profile Type</th><th>Dynamic Fields</th><th>Required Fields</th><th>Users</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
@forelse($profileTypes as $type)
<tr><td><div class="font-bold">{{ $type->label }}</div><div class="font-mono text-xs text-[#75777e]">{{ $type->code }}</div><p class="mt-1 max-w-lg truncate text-xs text-[#44474d]">{{ $type->description ?: '-' }}</p></td><td><div class="flex flex-wrap gap-1">@foreach(['institution_label','major_label','level_label'] as $field)<x-admin.badge variant="neutral">{{ $type->{$field} }}</x-admin.badge>@endforeach</div></td><td><div class="flex flex-wrap gap-1">@if($type->requires_institution)<x-admin.badge variant="warning">Institution</x-admin.badge>@endif @if($type->requires_major)<x-admin.badge variant="warning">Major</x-admin.badge>@endif @if($type->requires_level)<x-admin.badge variant="warning">Level</x-admin.badge>@endif @if(! $type->requires_institution && ! $type->requires_major && ! $type->requires_level)<span class="text-xs text-[#75777e]">Optional</span>@endif</div></td><td><x-admin.badge variant="primary">{{ $type->users_count }} users</x-admin.badge></td><td><x-admin.badge :status="$type->is_active ? 'active' : 'inactive'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td><td class="table-action-cell"><x-admin.action-menu><x-admin.action-link :href="route('admin.profile-types.edit',$type)" icon="pencil-square">Edit</x-admin.action-link><form method="POST" action="{{ route('admin.profile-types.destroy',$type) }}" class="js-delete-form" data-delete-label="{{ $type->label }}">@csrf @method('DELETE')<x-admin.action-button icon="trash" danger>Delete</x-admin.action-button></form></x-admin.action-menu></td></tr>
@empty<tr><td colspan="6"><x-admin.empty-state title="No profile types found" description="Tambahkan profile type untuk segmentasi member Lumina Library." icon="badge" /></td></tr>@endforelse
</tbody></table></div><x-admin.pagination :paginator="$profileTypes" /></div>
@endsection
