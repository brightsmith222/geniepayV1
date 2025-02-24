@extends('layout')

@section('dashboard-content')


<h1>Create New Slider</h1>

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
                        <form>
                            <div class="form-group">
                                <label for="sliderImage">Upload Image</label>
                                <input type="file" class="form-control-file" id="sliderImage">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Upload</button>
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
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><img src="slider1.jpg" alt="Slider 1" width="100"></td>
                        <td>
                            <span class="material-icons-outlined action-icon" title="Edit">edit</span>
                            <span class="material-icons-outlined action-icon text-danger" title="Delete">delete</span>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><img src="slider2.jpg" alt="Slider 2" width="100"></td>
                        <td>
                            <span class="material-icons-outlined action-icon" title="Edit">edit</span>
                            <span class="material-icons-outlined action-icon text-danger" title="Delete">delete</span>
                        </td>
                    </tr>
                    <!-- Add more rows as needed -->
                </tbody>
            </table>
        </div>

@stop