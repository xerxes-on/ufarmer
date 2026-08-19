@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Create Service Category</h1>
            <a href="{{ route('admin.service-categories.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.service-categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h4>Name Translations</h4>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="name_en" class="form-label">Name (English) *</label>
                        <input type="text" class="form-control @error('name.en') is-invalid @enderror"
                               id="name_en" name="name[en]" value="{{ old('name.en') }}" required>
                        @error('name.en')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="name_ru" class="form-label">Name (Russian) *</label>
                        <input type="text" class="form-control @error('name.ru') is-invalid @enderror"
                               id="name_ru" name="name[ru]" value="{{ old('name.ru') }}" required>
                        @error('name.ru')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="name_uz" class="form-label">Name (Uzbek) *</label>
                        <input type="text" class="form-control @error('name.uz') is-invalid @enderror"
                               id="name_uz" name="name[uz]" value="{{ old('name.uz') }}" required>
                        @error('name.uz')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="icon" class="form-label">Emoji Icon</label>
                        <input type="text" class="form-control @error('icon') is-invalid @enderror"
                               id="icon" name="icon" value="{{ old('icon', '🛠') }}"
                               maxlength="10" placeholder="🛠">
                        <small class="form-text text-muted">Enter an emoji to use as icon</small>
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="applies_to" class="form-label">Applies To *</label>
                        <select class="form-select @error('applies_to') is-invalid @enderror"
                                id="applies_to" name="applies_to" required>
                            <option value="both" {{ old('applies_to') === 'both' ? 'selected' : '' }}>Both</option>
                            <option value="offers" {{ old('applies_to') === 'offers' ? 'selected' : '' }}>Service Offers</option>
                            <option value="announcements" {{ old('applies_to') === 'announcements' ? 'selected' : '' }}>Job Announcements</option>
                        </select>
                        @error('applies_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}"
                               min="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="icon_image" class="form-label">Icon Image</label>
                        <input type="file" class="form-control @error('icon_image') is-invalid @enderror"
                               id="icon_image" name="icon_image" accept="image/*">
                        <small class="form-text text-muted">Upload an icon image (max 2MB)</small>
                        @error('icon_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="icon_preview" class="mt-2"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="category_image" class="form-label">Category Image</label>
                        <input type="file" class="form-control @error('category_image') is-invalid @enderror"
                               id="category_image" name="category_image" accept="image/*">
                        <small class="form-text text-muted">Upload a category image (max 5MB)</small>
                        @error('category_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="category_preview" class="mt-2"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Category
                        </button>
                        <a href="{{ route('admin.service-categories.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Image preview
document.getElementById('icon_image').addEventListener('change', function(e) {
    const preview = document.getElementById('icon_preview');
    preview.innerHTML = '';
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100px; max-height: 100px;">`;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

document.getElementById('category_image').addEventListener('change', function(e) {
    const preview = document.getElementById('category_preview');
    preview.innerHTML = '';
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px;">`;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>
@endpush
@endsection