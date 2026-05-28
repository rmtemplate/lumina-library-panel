@extends('layouts.admin')
@section('title','Permissions')
@section('page-title','Permissions')
@section('page-description','Kelola permission granular untuk authorization.')
@section('page-actions')<a href="{{ route('admin.permissions.create') }}" class="btn-primary"><x-admin.icon name="plus" class="h-4 w-4" />Create Permission</a>@endsection
@section('content')
<x-admin.filters search-placeholder="Search permission code or name"><select name="module" class="form-select w-full md:w-48"><option value="">All modules</option>@foreach($modules as $module)<option value="{{ $module }}" @selected(request('module')===$module)>{{ ucfirst($module) }}</option>@endforeach</select></x-admin.filters>
<div class="table-card overflow-x-auto"><table class="modern-table min-w-[820px]"><thead><tr><th>Permission</th><th>Module</th><th>Roles</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
@forelse($permissions as $permission)<tr><td><div class="font-bold">{{ $permission->name }}</div><div class="font-mono text-xs text-[#75777e]">{{ $permission->code }}</div><p class="mt-1 max-w-md truncate text-xs text-[#44474d]">{{ $permission->description ?: '-' }}</p></td><td><x-admin.badge variant="info">{{ $permission->module }}</x-admin.badge></td><td><x-admin.badge variant="primary">{{ $permission->roles_count }} roles</x-admin.badge></td><td><x-admin.badge :status="$permission->is_active ? 'active' : 'inactive'">{{ $permission->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td><td class="text-right"><x-admin.action-menu><x-admin.action-link :href="route('admin.permissions.edit',$permission)" icon="pencil-square">Edit</x-admin.action-link><form method="POST" action="{{ route('admin.permissions.destroy',$permission) }}" class="js-delete-form" data-delete-label="{{ $permission->name }}">@csrf @method('DELETE')<x-admin.action-button icon="trash" danger>Delete</x-admin.action-button></form></x-admin.action-menu></td></tr>@empty<tr><td colspan="5"><x-admin.empty-state title="No permissions found" description="Create permissions grouped by module." icon="key" /></td></tr>@endforelse
</tbody></table></div><x-admin.pagination :paginator="$permissions" />
@endsection
