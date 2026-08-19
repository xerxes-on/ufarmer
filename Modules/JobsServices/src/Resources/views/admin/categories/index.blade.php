@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Service Categories</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.service-categories.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Category
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="categories-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Applies To</th>
                        <th>Services</th>
                        <th>Jobs</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                    <tr data-id="{{ $category->id }}">
                        <td class="handle">
                            <i class="fas fa-grip-vertical"></i>
                            {{ $category->sort_order }}
                        </td>
                        <td>
                            @if($category->getFirstMediaUrl(Modules\JobsServices\Models\ServiceCategory::MEDIA_COLLECTION_ICON))
                                <img src="{{ $category->getFirstMediaUrl(Modules\JobsServices\Models\ServiceCategory::MEDIA_COLLECTION_ICON) }}"
                                     alt="Icon" style="width: 32px; height: 32px; object-fit: cover;">
                            @else
                                <span style="font-size: 32px;">{{ $category->icon }}</span>
                            @endif
                        </td>
                        <td>
                            <strong>EN:</strong> {{ $category->name['en'] ?? '' }}<br>
                            <strong>RU:</strong> {{ $category->name['ru'] ?? '' }}<br>
                            <strong>UZ:</strong> {{ $category->name['uz'] ?? '' }}
                        </td>
                        <td>
                            @if($category->applies_to === 'both')
                                <span class="badge bg-primary">Both</span>
                            @elseif($category->applies_to === 'offers')
                                <span class="badge bg-success">Services</span>
                            @else
                                <span class="badge bg-info">Jobs</span>
                            @endif
                        </td>
                        <td>{{ $category->service_offers_count ?? 0 }}</td>
                        <td>{{ $category->job_announcements_count ?? 0 }}</td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-active"
                                       type="checkbox"
                                       data-id="{{ $category->id }}"
                                       {{ $category->is_active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.service-categories.edit', $category) }}"
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.service-categories.destroy', $category) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $categories->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle active status
    document.querySelectorAll('.toggle-active').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const categoryId = this.dataset.id;
            fetch(`/admin/service-categories/${categoryId}/toggle-active`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    alert('Failed to update status');
                }
            });
        });
    });

    // Sortable for reordering
    const tbody = document.querySelector('#categories-table tbody');
    if (tbody) {
        Sortable.create(tbody, {
            handle: '.handle',
            animation: 150,
            onEnd: function(evt) {
                const categories = [];
                tbody.querySelectorAll('tr').forEach(function(row, index) {
                    categories.push({
                        id: row.dataset.id,
                        sort_order: index
                    });
                });

                fetch('/admin/service-categories/sort', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ categories: categories })
                });
            }
        });
    }
});
</script>
@endpush
@endsection