@extends('layout')

@section('dashboard-content')

<div class="container-fluid">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0 text-gray-800">
          <i class="fas fa-edit text-primary mr-2"></i>Edit Slider
      </h1>
      <a href="{{ URL::to('sliders') }}" class="btn btn-primary">
          <i class="fas fa-arrow-left mr-2"></i> Back to Sliders
      </a>
  </div>

  <!-- Success and Error Messages -->
  @if(Session::get('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle mr-2"></i> {{ Session::get('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  @endif

  @if(Session::get('failed'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle mr-2"></i> {{ Session::get('failed') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  @endif

  <!-- Edit Form Card -->
  <div class="slider-card shadow-sm border-0">
      <div class="card-body p-4">
          <form action="{{ URL::to('update-slider') }}/{{ $slider->id }}" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
              @csrf

              <!-- Form Sections -->
              <div class="row">
                  <!-- Left Column - Form Fields -->
                  <div class="col-lg-8">
                      <!-- Slider Title -->
                      <div class="form-group mb-4">
                          <label for="slider_title" class="font-weight-bold text-primary mb-2">
                              <i class="fas fa-heading mr-2"></i>Slider Title (Optional)
                          </label>
                          <input type="text" class="form-control border-primary" id="slider_title" name="slider_title" 
                                 value="{{ $slider->slider_title }}" placeholder="Enter a catchy title for your slider">
                      </div>

                      <!-- Slider Message -->
                      <div class="form-group mb-4">
                          <label for="slider_message" class="font-weight-bold text-primary mb-2">
                              <i class="fas fa-comment-alt mr-2"></i>Slider Message (Optional)
                          </label>
                          <textarea class="form-control border-primary" id="editor1" name="slider_message" 
                                    rows="5" placeholder="Enter your slider message here">{{ $slider->slider_message }}</textarea>
                      </div>
                  </div>

                  <!-- Right Column - Image Upload -->
                  <div class="col-lg-4">
                      <div class="slider-card border-0 shadow-sm mb-4">
                          <div class="slide-hd">
                              <h6 class="font-weight-bold text-primary mb-0">
                                  <i class="fas fa-image mr-2"></i>Slider Image
                              </h6>
                          </div>
                          <div class="card-body text-center">
                              <!-- Current Image Preview -->
                              <div class="mb-3">
                                  <img src="{{ $slider->image }}" id="photo" 
                                       class="img-fluid rounded shadow" 
                                       style="max-height: 200px; width: auto;">
                              </div>
                              
                              <!-- Image Upload -->
                              <div class="form-group">
                                  <div class="custom-file">
                                      <input type="file" class="custom-file-input" id="image" 
                                             name="image" onchange="loadPhoto(event)">
                                      <label class="custom-file-label" for="image">Choose new image</label>
                                  </div>
                                  <input type="hidden" name="image_update" value="{{ $slider->image }}">
                                  <small class="form-text text-muted">Recommended size: 1920×800 pixels</small>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Submit Button -->
              <div class="form-group text-center mt-4">
                  <button type="submit" class="btn btn-primary btn-lg px-5">
                      <i class="fas fa-save mr-2"></i> Update Slider
                  </button>
              </div>
          </form>
      </div>
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