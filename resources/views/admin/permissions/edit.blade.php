@extends('layouts.admin')
@section('title', isset($permission) ? 'Edit Permission' : 'Create Permission')
@section('page-title', isset($permission) ? 'Edit Permission' : 'Create Permission')
@section('page-actions')<a href="{{ route('admin.permissions.index') }}" class="btn-outline">Back</a>@endsection
@section('content')
<form method="POST" action="{{ isset($permission) ? route('admin.permissions.update',$permission) : route('admin.permissions.store') }}" class="lumina-card p-6">@csrf @isset($permission) @method('PUT') @endisset
    <div class="grid gap-5 md:grid-cols-2"><div><label class="form-label">Code</label><input name="code" value="{{ old('code',$permission->code ?? '') }}" class="form-input" required></div><div><label class="form-label">Name</label><input name="name" value="{{ old('name',$permission->name ?? '') }}" class="form-input" required></div><div><label class="form-label">Module</label><input name="module" value="{{ old('module',$permission->module ?? '') }}" class="form-input" required></div><div class="flex items-end"><label class="inline-flex items-center gap-2 text-sm font-semibold text-[#44474d]"><input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active',$permission->is_active ?? true))> Active</label></div><div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" class="form-textarea">{{ old('description',$permission->description ?? '') }}</textarea></div></div>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.permissions.index') }}" class="btn-outline">Cancel</a><button class="btn-primary" data-submit-label="Saving">Save Permission</button></div>
</form>
@endsection
