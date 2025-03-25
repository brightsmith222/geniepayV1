<div>
    <h1>Create New Slider</h1>

   

    <!-- Upload Form -->
    <button type="button" class="btn btn-primary mb-4" data-toggle="modal" data-target="#uploadModal">
        <span class="material-icons-outlined">add</span> Upload New Slider
    </button>

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
                    <div>
                        <!-- File Input & Upload Button -->
                        <form id="imageUploadForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="slider_id" value="{{ $sliders->first()->id }}">
                            <input type="file" id="imageInput" name="image" accept="image/*">
                            <button type="button" id="uploadBtn">Upload</button>
                        </form>
                    
                        <!-- Image Preview -->
                        <div id="preview">
                            @if($sliders->first()->image)
                                <img id="previewImage" src="{{ asset('storage/' . $sliders->first()->image) }}" width="100">
                            @else
                                <p>No image uploaded.</p>
                            @endif
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Display Sliders -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sliders as $index => $slider)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><img src="{{ asset('storage/' . $slider->image) }}" alt="Slider {{ $index + 1 }}" width="100"></td>
                        <td>
                            <button wire:click="editSlider({{ $slider->id }})" class="btn btn-sm btn-warning">Edit</button>
                            <button wire:click="deleteSlider({{ $slider->id }})" class="btn btn-sm btn-danger" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

