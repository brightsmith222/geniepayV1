@extends('layout')

@section('dashboard-content')

<div class="card">
  <div class="card-body">

    <div style="margin-top: 20px; margin-bottom: 20px;">
      <h3 class="" style="display: inline-block; width: 200px;">Slider Form</h3>
      <a href="{{ URL::to('sliders') }}" class="btn btn-primary" style="float:right; color: white; background-color: #9900CE; border-radius: 5px;
            padding: 10px; ">View Sliders</a>
    </div>

    @if(Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="gone">
      <strong> {{ Session::get('success') }} </strong>
      <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button> -->
    </div>
    @endif

    @if(Session::get('failed'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert" id="gone">
      <strong> {{ Session::get('failed') }} </strong>
      <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button> -->
    </div>
    @endif


    <form action="{{URL::to('add-slider')}}" class="row g-3" method='post' enctype="multipart/form-data" id="dataform">
      @csrf


      <div class="form-group">
        <label for="exampleInputEmail1" class="form-label">Slider Title(Optional)</label>
        <input type="text" class="form-control" id="id_amount" aria-describedby="emailHelp" placeholder="Enter Slider Title" name="slider_title">
      </div>


      <div class="form-group">
        <label for="exampleInputEmail1"> Slider Message(Optional) </label>
        <textarea id="editor1" name="slider_message"></textarea>
      </div>

      <div class="form-group">
        <label for="exampleInputEmail1" class="form-label">Slider Image*</label>
        <input type="file" class="form-control" placeholder="Insert subject icon" name="image" onchange="loadPhoto(event)">
      </div>

      <div>

        <img id="photo" style="width: 100px; height: 100px; border-radius: 10px;  padding: 1px;" />

      </div>




      <div class="form-group" style="text-align: center;">
      <button type="submit" style="margin: auto; color: white; background-color: #9900CE; border-radius: 5px;
            padding: 10px; " id="btnsubmit">Save Slider</button>
      </div>
    </form>
    <br><br>


  </div>
</div>


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

@stop