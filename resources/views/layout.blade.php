<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Geniepay - Dashboard</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Favicons -->
  <link href="{{ URL::to('nexhublogo.png')}}" rel="icon">
  <link href="{{ URL::to('nexhublogo.png')}}" rel="apple-touch-icon">

  <!-- Font awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

  
<!-- Livewire css -->
    @livewireStyles
  <!-- Vendor CSS Files -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">
  
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
   <!-- Flatpickr JS -->
 <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <!-- Template Main CSS File -->
  <link href="{{ URL::to('assets/css/main.css')}}" rel="stylesheet">
  <script src="{{URL::to('ckeditor/ckeditor.js')}}"></script>

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Updated: May 30 2023 with Bootstrap v5.3.0
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
  <style>
    .v-scroll {
      max-height: 500px;
      overflow-y: auto;
      padding-right: 10px;

    }
  </style>
</head>

<body>
  
  <x:notify::notify/>
  
  <!-- ======= Header ======= -->
  <nav class="navbar navbar-light bg-light">
        <button class="navbar-toggler" type="button" id="toggleSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="dropdown ml-auto">
            <img src="{{ URL::to('assets/img/avatar.png')}}" alt="Avatar" class="avatar rounded-circle" id="avatarDropdown" width="40" height="40" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="dropdown-menu" id="profileMenu">
                <a class="dropdown-item" href="#">Profile</a>
                <form id="logout-forms" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                  @csrf
              </form>
              <a href="#" onclick="event.preventDefault(); document.getElementById('logout-forms').submit();"> Logout
              </a>
            </div>
        </div>
        
    </nav>
    <div class="sidebar" id="sidebar">
    <!-- Logo at the top of the sidebar -->
    <div class="sidebar-logo">
        <img src="{{ URL::to('assets/img/logo.png')}}" alt="Logo">
    </div>
    <ul>
        <li><a href="{{ URL::to('dashboard') }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="{{ URL::to('reported') }}"><i class="fas fa-flag"></i> Reported</a></li>
        <li><a href="{{ URL::to('transaction') }}"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
        <li><a href="{{ URL::to('wallet_transac') }}"><i class="fas fa-wallet"></i> Wallet Transac</a></li>
        <li><a href="{{ URL::to('users') }}"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="{{ URL::to('sliders') }}"><i class="fas fa-sliders-h"></i> Slider</a></li>
        <li><a href="{{ URL::to('data_settings')}}"><i class="fas fa-database"></i> Data Settings</a></li>
        <li><a href="{{ URL::to('notifications') }}"><i class="fas fa-bell"></i> Notification</a></li>
        <li><a href="{{ URL::to('settings') }}"><i class="fas fa-cog"></i> Settings</a></li>
        
        <!-- Logout Button -->
        <li class="logout-item">
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </form>
        </li>
    </ul>
</div>




    <div class="content" id="content">


    @yield('dashboard-content')


  </div><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer class="footer">
    <div class="foot-con">
        <div class="row">
          <div class="copyright">
          <span> &copy; Copyright <strong><span>Geniepay</span></strong>. All Rights Reserved. <span class="credits">Developed by <a href="#">Edugenie Tech</a></span>
        </span> 
      </div>
      </div>
   </div>
    </div>
</footer>
<!-- End Footer -->


  <!-- Livewire JS-->
  @livewireScripts

  <!-- Jquery File -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
     
   

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{ URL::to('assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script src="{{ URL::to('assets/vendor/chart.js/chart.umd.js')}}"></script>
  <script src="{{ URL::to('assets/vendor/echarts/echarts.min.js')}}"></script>
  <script src="{{ URL::to('assets/vendor/quill/quill.min.js')}}"></script>
  <script src="{{ URL::to('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <script src="{{ URL::to('assets/vendor/tinymce/tinymce.min.js')}}"></script>
  <script src="{{ URL::to('assets/vendor/php-email-form/validate.js')}}"></script>



  <!-- Template Main JS File -->
  <script src="{{ URL::to('assets/js/script.js')}}"></script>

    <!-- Yield script for each page -->
    @yield('scripts')

    


</body>

</html>