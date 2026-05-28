@php($isEdit = isset($profileType) && $profileType?->exists)
<form method="POST" action="{{ $isEdit ? route('admin.profile-types.update', $profileType) : route('admin.profile-types.store') }}" class="lumina-card p-5 sm:p-6">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">Label</label><input name="label" value="{{ old('label', $profileType->label ?? '') }}" class="form-input" placeholder="Mahasiswa/i" required>@error('label')<p class="form-error">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Code</label><input name="code" value="{{ old('code', $profileType->code ?? '') }}" class="form-input" placeholder="mahasiswa" required>@error('code')<p class="form-error">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Institution Label</label><input name="institution_label" value="{{ old('institution_label', $profileType->institution_label ?? 'Instansi') }}" class="form-input" required></div>
        <div><label class="form-label">Major Label</label><input name="major_label" value="{{ old('major_label', $profileType->major_label ?? 'Jurusan') }}" class="form-input" required></div>
        <div><label class="form-label">Level Label</label><input name="level_label" value="{{ old('level_label', $profileType->level_label ?? 'Tingkat') }}" class="form-input" required></div>
        <div><label class="form-label">Sort Order</label><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $profileType->sort_order ?? 0) }}" class="form-input"></div>
        <div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" class="form-textarea">{{ old('description', $profileType->description ?? '') }}</textarea></div>
        <div class="md:col-span-2 grid gap-3 rounded-xl border border-[#c5c6ce] bg-[#f5f3f6] p-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                'show_institution' => 'Show Institution',
                'show_major' => 'Show Major/Program',
                'show_level' => 'Show Level',
                'is_active' => 'Active',
            ] as $field => $label)
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-[#44474d]"><input type="checkbox" name="{{ $field }}" value="1" class="form-check" @checked(old($field, $profileType->{$field} ?? true))>{{ $label }}</label>
            @endforeach
            @foreach([
                'requires_institution' => 'Require Institution',
                'requires_major' => 'Require Major/Program',
                'requires_level' => 'Require Level',
            ] as $field => $label)
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-[#44474d]"><input type="checkbox" name="{{ $field }}" value="1" class="form-check" @checked(old($field, $profileType->{$field} ?? false))>{{ $label }}</label>
            @endforeach
        </div>
    </div>
    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('admin.profile-types.index') }}" class="btn-outline cursor-pointer">Cancel</a><button class="btn-primary cursor-pointer" data-submit-label="Saving">Save Profile Type</button></div>
</form>
