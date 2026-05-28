@php
    $isEdit = isset($category) && $category?->exists;
    $imageUrl = old('image_preview') ?: ($category?->image_url ?? null);
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="lumina-card p-5 sm:p-6">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label">Name</label>
                <input name="name" value="{{ old('name', $category->name ?? '') }}" class="form-input" placeholder="Teknologi & Komputer" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Slug</label>
                <input name="slug" value="{{ old('slug', $category->slug ?? $category->code ?? '') }}" class="form-input" placeholder="teknologi-komputer" required>
                <p class="form-help">Dipakai untuk filter dan future API/mobile route.</p>
                @error('slug')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Code</label>
                <input name="code" value="{{ old('code', $category->code ?? '') }}" class="form-input" placeholder="teknologi" required>
                @error('code')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Material Icon Name</label>
                <input name="icon_name" value="{{ old('icon_name', $category->icon_name ?? '') }}" class="form-input" placeholder="computer">
                <p class="form-help">Optional fallback icon dari Material Symbols.</p>
                @error('icon_name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-input" min="0">
                @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#c5c6ce] bg-[#f5f3f6] px-4 py-3 text-sm font-semibold text-[#44474d] transition hover:bg-[#e9e7ea]">
                    <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', $category->is_active ?? true))>
                    Active category
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" placeholder="Jelaskan cakupan kategori ini untuk admin dan rekomendasi buku." required>{{ old('description', $category->description ?? '') }}</textarea>
                @error('description')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl border border-dashed border-[#c5c6ce] bg-[#f5f3f6] p-4">
            <label class="form-label">Category Icon/Image</label>
            <div class="mb-4 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl border border-[#c5c6ce] bg-white">
                <img data-image-preview="category-image" src="{{ $imageUrl ?: asset('lumina-logo.png') }}" alt="Category preview" class="h-full w-full object-cover">
            </div>
            <input type="file" name="image_file" accept="image/png,image/jpeg,image/jpg,image/webp," class="block w-full cursor-pointer rounded-lg border border-[#c5c6ce] bg-white text-sm text-[#44474d] file:mr-4 file:cursor-pointer file:border-0 file:bg-[#001828] file:px-4 file:py-3 file:text-sm file:font-semibold file:text-white hover:file:bg-[#006c49]" data-preview-target="category-image" @required(! $isEdit)>
            <p class="form-help">Upload JPG, PNG, WEBP, Maksimal 2MB. Admin tidak input URL manual.</p>
            @error('image_file')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.categories.index') }}" class="btn-outline cursor-pointer">Cancel</a>
        <button class="btn-primary cursor-pointer" data-submit-label="Saving">Save Category</button>
    </div>
</form>
