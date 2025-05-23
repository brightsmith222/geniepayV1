@extends('layout')

@section('dashboard-content')

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-images  mr-2"></i>Slider Management
        </h1>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
            <i class="fas fa-plus mr-2"></i>Add New Slider
        </button>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="slider-alert slider-alert-success alert-dismissible fade show" role="slider-alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="slider-alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('failed'))
        <div class="slider-alert slider-alert-danger alert-dismissible fade show" role="slider-alert">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('failed') }}
            <button type="button" class="close" data-dismiss="slider-alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>Upload New Slider
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('add-slider') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="slider_title" class="font-weight-bold">Slider Title</label>
                            <input type="text" class="form-control border-primary" id="slider_title" name="slider_title" required>
                        </div>
                        <div class="form-group">
                            <label for="slider_message" class="font-weight-bold">Slider Message</label>
                            <textarea class="form-control border-primary" id="slider_message" name="slider_message" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="sliderImage" class="font-weight-bold">Slider Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="sliderImage" name="image" required>
                                <label class="custom-file-label" for="sliderImage">Choose image file...</label>
                            </div>
                            <small class="form-text text-muted">Recommended size: 1920×800 pixels</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload mr-2"></i>Upload Slider
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sliders Table -->
    <div class="slider-card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold ">
                <i class="fas fa-list mr-2"></i>Current Sliders
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="slidersTable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Title</th>
                            <th width="30%">Message</th>
                            <th width="25%">Image Preview</th>
                            <th width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sliders as $slider)
                            <tr>
                                <td>{{ ($sliders->currentPage() - 1) * $sliders->perPage() + $loop->iteration }}</td>
                                <td class="font-weight-bold">{{ $slider->slider_title }}</td>
                                <td>{{ Str::limit($slider->slider_message, 50) }}</td>
                                <td>
                                    <div class="slider-preview-container">
                                        <img src="{{ $slider->image }}" alt="Slider Image" class="img-thumbnail slider-preview" data-toggle="modal" data-target="#imageModal{{ $slider->id }}">
                                        <div class="preview-overlay" data-toggle="modal" data-target="#imageModal{{ $slider->id }}">
                                            <i class="fas fa-search-plus"></i>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('edit-slider', $slider->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <form action="{{ route('delete-slider', $slider->id) }}" method="POST" class="delete-form d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Image Preview Modal -->
                            <div class="modal fade" id="imageModal{{ $slider->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $slider->slider_title }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="{{ $slider->image }}" class="img-fluid" alt="Slider Full Preview">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-images fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Sliders Found</h4>
                                        <p class="text-muted">You haven't created any sliders yet. Click the "Add New Slider" button to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination - Only show if there are records -->
            @if($sliders->count() > 0)
                <div class="d-flex justify-content-center mt-4">
                    {{ $sliders->links('vendor.pagination.bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection


@section('scripts')
    <script src="{{ asset('assets/js/slider.js') }}"></script>
@endsection