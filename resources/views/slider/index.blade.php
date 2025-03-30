@extends('layout')

@section('dashboard-content')

<h1>Create New Slider</h1>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('failed'))
    <div class="alert alert-danger">
        {{ session('failed') }}
    </div>
@endif

<!-- Button to Open Modal -->
<button type="button" class="btn btn-primary mb-4" data-toggle="modal" data-target="#uploadModal">
    <span class="material-icons-outlined">add</span> Upload New Slider
</button>

<!-- Modal for Uploading Slider -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload New Slider</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('add-slider') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="slider_title">Slider Title</label>
                        <input type="text" class="form-control" id="slider_title" name="slider_title" required>
                    </div>
                    <div class="form-group">
                        <label for="slider_message">Slider Message</label>
                        <textarea class="form-control" id="slider_message" name="slider_message" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="sliderImage">Upload Image</label>
                        <input type="file" class="form-control-file" id="sliderImage" name="image" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Table to Display Sliders -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Message</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sliders as $slider)
                <tr>
                    <td>{{ ($sliders->currentPage() - 1) * $sliders->perPage() + $loop->iteration }}</td>
                    <td>{{ $slider->slider_title }}</td>
                    <td>{{ $slider->slider_message }}</td>
                    <td><img src="{{ $slider->image }}" alt="Slider Image" width="100"></td>
                    <td>
                        <a href="{{ route('edit-slider', $slider->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('delete-slider', $slider->id) }}" method="POST" class="delete-form" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Pagination Links -->
<div class="d-flex justify-content-center mt-4">
    {{ $sliders->links('vendor.pagination.bootstrap-4') }}
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const paginationLinks = document.querySelectorAll('.pagination');


    // SweetAlert2 Delete Confirmation
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the form from submitting immediately

        // Show SweetAlert2 confirmation dialog
        Swal.fire({
          title: 'Are you sure?',
          text: 'You will not be able to recover this slider!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            // If the user confirms, submit the form
            form.submit();
          }
        });
      });
    });
  });
</script>

@stop