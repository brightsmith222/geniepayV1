@extends('layout')

@section('dashboard-content')

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="card-title mb-0">Update Slider</h3>
      <a href="{{ URL::to('sliders') }}" class="btn btn-primary" style="background-color: #9900CE; border: none; border-radius: 5px; padding: 10px 20px;">
        <i class="fas fa-arrow-left"></i> View Sliders
      </a>
    </div>

    <!-- Success and Error Messages -->
    @if(Session::get('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ Session::get('success') }}</strong>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif

    @if(Session::get('failed'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ Session::get('failed') }}</strong>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif

    <!-- Edit Form -->
    <form action="{{ URL::to('update-slider') }}/{{ $slider->id }}" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
      @csrf

      <!-- Slider Title -->
      <div class="form-group mb-4">
        <label for="slider_title" class="form-label">Slider Title (Optional)</label>
        <input type="text" class="form-control" id="slider_title" name="slider_title" value="{{ $slider->slider_title }}" placeholder="Enter Slider Title">
      </div>

      <!-- Slider Message -->
      <div class="form-group mb-4">
        <label for="slider_message" class="form-label">Slider Message (Optional)</label>
        <textarea class="form-control" id="editor1" name="slider_message" rows="4" placeholder="Enter Slider Message">{{ $slider->slider_message }}</textarea>
      </div>

      <!-- Slider Image -->
      <div class="form-group mb-4">
        <label for="image" class="form-label">Slider Image *</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="image" name="image" onchange="loadPhoto(event)">
          <label class="custom-file-label" for="image">Choose file</label>
        </div>
        <input type="hidden" name="image_update" value="{{ $slider->image }}">
        <small class="form-text text-muted">Upload a new image to replace the existing one.</small>
      </div>

      <!-- Image Preview -->
      <div class="form-group mb-4 text-center">
        <img src="{{ $slider->image }}" id="photo" class="img-thumbnail" style="width: 150px; height: 150px; border-radius: 10px; object-fit: cover;">
      </div>

      <!-- Submit Button -->
      <div class="form-group text-center">
        <button type="submit" class="btn btn-primary" style="background-color: #9900CE; border: none; border-radius: 5px; padding: 10px 30px;">
          <i class="fas fa-save"></i> Update Slider
        </button>
      </div>
    </form>

  </div>
</div>

<!-- Script for Image Preview -->
<script>
  function loadPhoto(event) {
    var reader = new FileReader();
    reader.onload = function() {
      var output = document.getElementById('photo');
      output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
  }
</script>

<!-- Script for Bootstrap Custom File Input -->
<script>
  document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = document.getElementById('image').files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
  });
</script>

@stop