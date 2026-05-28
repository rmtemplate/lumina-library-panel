@extends('layouts.admin')
@section('title','Roles')
@section('page-title','Roles')
@section('page-description','Kelola role dan akses RBAC untuk web admin dan future API.')
@section('page-actions')<a href="{{ route('admin.roles.create') }}" class="btn-primary"><x-admin.icon name="plus" class="h-4 w-4" />Create Role</a>@endsection
@section('content')
<div class="table-card overflow-x-auto"><table class="modern-table min-w-[820px]"><thead><tr><th>Role</th><th>Users</th><th>Permissions</th><th>Sort</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
@forelse($roles as $role)<tr><td><div class="font-bold">{{ $role->name }}</div><div class="font-mono text-xs text-[#75777e]">{{ $role->code }}</div><p class="mt-1 max-w-md truncate text-xs text-[#44474d]">{{ $role->description ?: '-' }}</p></td><td><x-admin.badge variant="primary">{{ $role->users_count }} users</x-admin.badge></td><td><x-admin.badge variant="secondary">{{ $role->permissions_count }} permissions</x-admin.badge></td><td>{{ $role->sort_order }}</td><td><x-admin.badge :status="$role->is_active ? 'active' : 'inactive'">{{ $role->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td><td class="text-right"><x-admin.action-menu><x-admin.action-link :href="route('admin.roles.edit',$role)" icon="pencil-square">Edit</x-admin.action-link><form method="POST" action="{{ route('admin.roles.destroy',$role) }}" class="js-delete-form" data-delete-label="{{ $role->name }}">@csrf @method('DELETE')<x-admin.action-button icon="trash" danger>Delete</x-admin.action-button></form></x-admin.action-menu></td></tr>@empty<tr><td colspan="6"><x-admin.empty-state title="No roles found" description="Create roles and attach permissions." icon="shield-check" /></td></tr>@endforelse
</tbody></table></div><x-admin.pagination :paginator="$roles" />
@endsection
