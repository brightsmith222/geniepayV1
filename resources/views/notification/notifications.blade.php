@extends('layout')

@section('dashboard-content')

<div class="card">
    <div class="card-body">

        <div style="margin-top: 20px; margin-bottom: 20px;">
            <h3 class="" style="display: inline-block; width: 200px;">Notifications</h3>
            <a href="{{ URL::to('notification-form') }}" style="float:right; color: white; background-color: #9900CE; border-radius: 5px;
            padding: 10px; ">Add New Notification</a>
        </div>

        <ul class="notification-list">
            @foreach($notifications as $notification)
            <li class="notification-item">
                <div class="icon">
                    <i class="bi bi-info-circle text-primary"></i>
                </div>
                <div class="notification-content">
                    <h4 class="notification-title">{!! $notification->notification_title !!}</h4>
                    <p class="notification-message">{!! $notification->notification_message !!}</p>
                    <p class="notification-time">{{ \Carbon\Carbon::parse($notification->created_at)->timezone('Africa/Lagos')->format('F j, Y, g:i A') }}</p>
                </div>
                <hr class="dropdown-divider">
            </li>
            @endforeach
        </ul>

        <style>
            .notification-list {
                list-style-type: none;
                padding: 0;
                margin: 0;
                max-height: 400px;
                /* Set a fixed height for the list */
                overflow-y: auto;
                /* Enable vertical scrolling */
                /* border: 1px solid #ddd; */
                /* Optional: Add a border to better visualize the scrollable area */
                padding-right: 10px;
                /* Optional: Adjust padding for better aesthetics */
            }

            .notification-item {
                display: flex;
                align-items: center;
                padding: 10px 0;
            }

            .notification-item .icon {
                margin-right: 10px;
            }

            .notification-content {
                flex-grow: 1;
            }

            .notification-title {
                font-size: 1.2em;
                margin: 0;
            }

            .notification-message {
                margin: 5px 0;
            }

            .notification-time {
                font-size: 0.8em;
                color: #888;
            }

            .dropdown-divider {
                margin: 10px 0;
            }
        </style>



    </div>
</div>



@stop