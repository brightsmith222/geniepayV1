<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Geniepay - Dashboard</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="{{ URL::to('nexhublogo.png')}}" rel="icon">
  <link href="{{ URL::to('nexhublogo.png')}}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
  <link href="{{ URL::to('assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">

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

  <!-- ======= Header ======= -->
  <nav class="navbar navbar-light bg-light">
        <button class="navbar-toggler" type="button" id="toggleSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="dropdown ml-auto">
            <img src="{{ URL::to('assets/img/avatar.png')}}" alt="Avatar" class="avatar rounded-circle" id="avatarDropdown" width="40" height="40" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="dropdown-menu" id="profileMenu">
                <a class="dropdown-item" href="#">Profile</a>
                <a class="dropdown-item" href="#">Logout</a>
            </div>
        </div>
        
    </nav>
    <div class="sidebar" id="sidebar">
        <!-- Logo at the top of the sidebar -->
        <div class="sidebar-logo">
            <img src="{{ URL::to('assets/img/logo.png')}}" alt="Logo">
        </div>
        <ul>
            <li><a href="{{ URL::to('dashboard') }}"><span class="material-icons-outlined">dashboard</span> Dashboard</a></li>
            <li><a href="{{ URL::to('transaction') }}"><span class="material-icons-outlined">receipt</span> Transactions</a></li>
            <li><a href="{{ URL::to('wallet_transac') }}"><span class="material-icons-outlined">trending_up</span> Wallet Transac</a></li>
            <li><a href="{{ URL::to('users') }}"><span class="material-icons-outlined">people</span> Users</a></li>
            <li><a href="{{ URL::to('sliders') }}"><span class="material-icons-outlined">person</span> Slider</a></li>
            <li><a href="{{ URL::to('data_settings')}}"><span class="material-icons-outlined">assignment</span> Data Settings</a></li>
            <li><a href="{{ URL::to('notifications') }}"><span class="material-icons-outlined">group</span> Notification</a></li>
            <li><a href="#"><span class="material-icons-outlined">account_balance</span> Financial Services</a></li>
            <li><a href="#"><span class="material-icons-outlined">payment</span> Bill Payments</a></li>
            <li><a href="#"><span class="material-icons-outlined">credit_card</span> Virtual Accounts</a></li>
            <li><a href="#"><span class="material-icons-outlined">card_giftcard</span> Cashback</a></li>
            <li><a href="#"><span class="material-icons-outlined">shopping_bag</span> Products & Support</a></li>
            <li><a href="#"><span class="material-icons-outlined">sim_card</span> eSIMs</a></li>
            <li><a href="#"><span class="material-icons-outlined">settings</span> Settings</a></li>
            <li><a href="#"><span class="material-icons-outlined">support</span> Support Tickets</a></li>
            <li><a href="#"><span class="material-icons-outlined">logout</span> Log Out</a></li>
        </ul>
    </div>




    <div class="content" id="content">


    @yield('dashboard-content')


  </div><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer class="footer">
    <div class="container">
        <div class="row">
          <div class="copyright">
            &copy; Copyright <strong><span>Geniepay</span></strong>. All Rights Reserved
          </div>
          <div class="credits">
            Developed by <a href="#">Edugenie Tech</a>
          </div>
        </div>
    </div>
</footer>
<!-- End Footer -->

  <!-- Jquery File -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>

  <!-- Vendor JS Files -->
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




 






</body>

</html>