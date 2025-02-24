@extends('layout')

@section('dashboard-content')


<div class="pagetitle">
  <h1 style="color: #4FBF67">Dashboard</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.html">Home</a></li>
      <li class="breadcrumb-item active" >Dashboard</li>
    </ol>
  </nav>
</div><!-- End Page Title -->

<section class="section dashboard">
  <div class="row">

    <!-- Left side columns -->
    <div class="col-lg-12">
      <div class="row">


        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; padding-bottom: 0px;">
                <div style="float: left;">
                  <p style="font-weight: 500; color: grey;">Total Users</p>
                  <h4 style="font-weight: bold;">10,000,000</h4>
                </div>
                <div style="float: right; background-color:#cef5d7;" class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-people" style="color: #4FBF67;"></i>
                </div>

                <div style="clear: both; margin-top: 20px;">
                  <i class="bi bi-arrow-up-right" style="color: #4FBF67;"> </i><span style="color: #4FBF67;" class="small pt-1 fw-bold">8.5%</span> <span class="text-muted small pt-2 ps-1">Up from yesterday</span>
                </div>

              </div>

            </div>

          </div>
        </div><!-- End Sales Card -->

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; padding-bottom: 20px;">

                <p style="font-weight: 500; color: grey;">Total active Users</p>
                <h4 style="font-weight: bold;">5,000,000</h4>

              </div>

            </div>

          </div>
        </div><!-- End Sales Card -->

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; padding-bottom: 20px;">

                <p style="font-weight: 500; color: grey;">Total API Users</p>
                <h4 style="font-weight: bold;">400,000</h4>

              </div>

            </div>

          </div>
        </div><!-- End Sales Card -->


        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card" style="background-color: #4FBF67; ">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; padding-bottom: 20px; padding: auto;">


                <h4 style="font-weight: bolder; color: white;" id="current-time"></h4>
                <p style="font-weight: 500; color: #F3EDF5;" id="current-date"></p>

              </div>

            </div>

          </div>
        </div><!-- End Sales Card -->



        <div class="col-lg-12 mb-2">
          <h5 style="color: #4FBF67; font-weight: bolder;">Sales</h5>

        </div>

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Total </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">4,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Data </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">2,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->


        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Airtime </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">1,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->



        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Electricity</p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"> </p> --}}
                <h4 style="font-weight: bold; text-align: right;">800,000</h4>
              </div>

            </div>

          </div>
        </div><!-- End Sales Card -->


        <div class="col-lg-12 mb-2">
          <h5 style="color: #4FBF67; font-weight: bolder;">Revenue</h5>

        </div>

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Total </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">N100,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->

        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Data </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">N60,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->


        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Airtime </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">N30,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->



        <!-- Sales Card -->
        <div class="col-xxl-3 col-md-3">
          <div class="card">

            <div class="card-body">
              <!-- <h5 class="card-title">Sales <span>| Today</span></h5> -->

              <div class="" style="padding-top: 20px; ">

                <p style="font-weight: 500; color: grey;">Electriciy </p>
                {{-- <p style="font-weight: 500; color: grey; text-align: right;"></p> --}}
                <h4 style="font-weight: bold; text-align: right;">N1,000,000</h4>
      

              </div>

            </div>

          </div>
        </div>
        <!-- End Sales Card -->



        



 




       
        {{-- <!-- Sales Card -->
        <div class="col-xxl-2 col-md-2">
          <div class="card p-2">

            <div class="">
              <p style="font-weight: 500; color: grey; float: left;">plan['name']</p>
              <span style="text-align: right; float: right; font-weight: 300; color: grey;">plan['duration']yr(s)</span>

              <h4 style="font-weight: bold; clear: both;">plan['price']</h4>
              <span style="text-align: right; font-weight: 300; color: grey;">Subs: plan['users']</span>

            </div>

          </div>
        </div><!-- End Sales Card --> --}}
       



      </div>
    </div><!-- End Left side columns -->



  </div>

  <script>
    setInterval(function() {
      const now = new Date();
      const dayOfWeek = now.toLocaleDateString(undefined, {
        weekday: 'short'
      });
      const time = now.toLocaleTimeString();
      const day = now.getDate();
      const month = now.toLocaleDateString(undefined, {
        month: 'short'
      });
      const year = now.getFullYear();
      document.getElementById('current-time').innerHTML = time;
      document.getElementById('current-date').innerHTML = dayOfWeek + ', ' + day + ' ' + month + ', ' + year;
    }, 1000); // updates every 1 second (1000 milliseconds)
  </script>


</section>


@stop