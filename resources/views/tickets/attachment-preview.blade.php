@extends('layout')

@section('dashboard-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">File Preview: {{ $attachment->original_filename }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('tickets.show', $attachment->ticket) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Ticket
                        </a>
                        <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h5>File Information</h5>
                        <p><strong>Name:</strong> {{ $attachment->original_filename }}</p>
                        <p><strong>Size:</strong> {{ $attachment->file_size_formatted }}</p>
                        <p><strong>Type:</strong> {{ $attachment->mime_type }}</p>
                        <p><strong>Uploaded:</strong> {{ $attachment->created_at->format('M d, Y H:i') }}</p>
                        
                        <div class="mt-4">
                            <p class="text-muted">This file type cannot be previewed in the browser.</p>
                            <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-lg btn-primary">
                                <i class="fas fa-download"></i> Download File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection