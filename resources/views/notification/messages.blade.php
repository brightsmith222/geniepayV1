@extends('layout')

@section('dashboard-content')

<div class="card">
    <div class="card-body">

        <div style="margin-top: 20px; margin-bottom: 20px;">
            <h3 class="" style="display: inline-block; width: 200px;">Messages</h3>
            <a href="{{ URL::to('notification-form') }}" style="float:right; color: white; background-color: #9900CE; border-radius: 5px; padding: 10px;">Message a User</a>
        </div>

        <ul class="message-list">
            @foreach($messages as $message)
            <li class="message-item">
                <a href="{{ URL::to('chat') }}/{{ $message['sender_id'] }}">
                    <img src="{{ $message['sender_photo'] }}" alt="Sender Photo" class="rounded-circle message-photo">
                    <div class="message-content">
                        <h4 class="message-poster">{!! $message['poster'] !!}</h4>
                        <p class="message-text">{!! $message['message'] !!}</p>
                        <p class="message-time">{{ \Carbon\Carbon::parse($message['created_at'])->timezone('Africa/Lagos')->format('F j, Y, g:i A') }}</p>
                    </div>
                </a>
            </li>
            @endforeach
        </ul>

        <style>
            .message-list {
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

            .message-item {
                display: flex;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #ddd;
            }

            .message-item a {
                display: flex;
                align-items: center;
                text-decoration: none;
                color: inherit;
                width: 100%;
            }

            .message-photo {
                width: 50px;
                height: 50px;
                margin-right: 10px;
            }

            .message-content {
                flex-grow: 1;
            }

            .message-poster {
                font-size: 1.1em;
                margin: 0;
            }

            .message-text {
                margin: 5px 0;
            }

            .message-time {
                font-size: 0.8em;
                color: #888;
            }
        </style>

    </div>
</div>

@stop
