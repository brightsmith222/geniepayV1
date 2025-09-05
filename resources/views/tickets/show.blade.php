@extends('layout')

@section('dashboard-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('tickets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Tickets
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Ticket Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Ticket Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Subject:</strong></td>
                                    <td>{{ $ticket->subject }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->status == 'open' ? 'danger' : ($ticket->status == 'in_progress' ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Priority:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->category)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated:</strong></td>
                                    <td>{{ $ticket->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>User Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $ticket->user->full_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td>{{ $ticket->user->username }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $ticket->user->email }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Ticket Description -->
                    <div class="mb-4">
                        <h5>Description</h5>
                        <div class="border p-3 bg-light">
                            {{ $ticket->description }}
                        </div>
                    </div>

                    <!-- Main Ticket Attachments -->
                    @if($ticket->mainAttachments && $ticket->mainAttachments->count() > 0)
                    <div class="mb-4">
                        <h5>Ticket Attachments</h5>
                        <div class="row">
                            @foreach($ticket->mainAttachments as $attachment)
                            <div class="col-md-4 mb-2">
                                <div class="card">
                                    <div class="card-body p-2">
                                        @if(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                            <!-- Image Preview - Click to enlarge -->
                                            <div class="text-center">
                                                <img src="{{ asset('storage/' . $attachment->file_path) }}" 
                                                     alt="{{ $attachment->original_filename }}" 
                                                     class="img-fluid rounded cursor-pointer" 
                                                     style="max-height: 150px; cursor: pointer;"
                                                     onclick="openImageModal('{{ asset('storage/' . $attachment->file_path) }}', '{{ $attachment->original_filename }}')"
                                                     title="Click to enlarge">
                                            </div>
                                        @else
                                            <!-- File Icon for non-images -->
                                            <div class="text-center">
                                                <i class="fas fa-file fa-3x text-muted mb-2"></i>
                                                <p class="mb-1 text-truncate" title="{{ $attachment->original_filename }}">
                                                    {{ Str::limit($attachment->original_filename, 20) }}
                                                </p>
                                            </div>
                                        @endif
                                        
                                        <div class="text-center mt-2">
                                            <small class="text-muted d-block">{{ $attachment->file_size_formatted }}</small>
                                            <div class="btn-group btn-group-sm mt-1">
                                                @if(!in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                                    <a href="{{ route('attachments.download', $attachment) }}" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('attachments.delete', $attachment) }}" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this attachment?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Status Update Form -->
                    @if($ticket->status !== 'closed')
                    <div class="mb-4">
                        <h5>Update Status</h5>
                        <form method="POST" action="{{ route('tickets.updateStatus', $ticket) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="status" class="form-control">
                                        <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-warning">Update Status</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif

                    <!-- Close Ticket Button -->
                    @if($ticket->status !== 'closed')
                    <div class="mb-4">
                        <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to close this ticket?')">
                                <i class="fas fa-times"></i> Close Ticket
                            </button>
                        </form>
                    </div>
                    @endif

                    <!-- Replies Section -->
                    <div class="mb-4">
                        <h5>Conversation History</h5>
                        @foreach($ticket->replies as $reply)
                        <div class="border p-3 mb-3 {{ $reply->isFromAdmin() ? 'bg-info text-white' : 'bg-light' }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $reply->author_name }}</strong>
                                <small>{{ $reply->created_at->format('M d, Y H:i') }}</small>
                            </div>
                            <div class="mt-2">
                                {{ $reply->message }}
                            </div>
                            
                            <!-- Reply Attachments -->
                            @if($reply->attachments && $reply->attachments->count() > 0)
                            <div class="mt-3">
                                <h6 class="mb-2">Attachments:</h6>
                                <div class="row">
                                    @foreach($reply->attachments as $attachment)
                                    <div class="col-md-4 mb-2">
                                        <div class="card {{ $reply->isFromAdmin() ? 'bg-white text-dark' : 'bg-light' }}">
                                            <div class="card-body p-2">
                                                @if(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                                    <!-- Image Preview - Click to enlarge -->
                                                    <div class="text-center">
                                                        <img src="{{ asset('storage/' . $attachment->file_path) }}" 
                                                             alt="{{ $attachment->original_filename }}" 
                                                             class="img-fluid rounded cursor-pointer" 
                                                             style="max-height: 120px; cursor: pointer;"
                                                             onclick="openImageModal('{{ asset('storage/' . $attachment->file_path) }}', '{{ $attachment->original_filename }}')"
                                                             title="Click to enlarge">
                                                    </div>
                                                @else
                                                    <!-- File Icon for non-images -->
                                                    <div class="text-center">
                                                        <i class="fas fa-file fa-2x text-muted mb-2"></i>
                                                        <p class="mb-1 text-truncate" title="{{ $attachment->original_filename }}">
                                                            {{ Str::limit($attachment->original_filename, 15) }}
                                                        </p>
                                                    </div>
                                                @endif
                                                
                                                <div class="text-center mt-2">
                                                    <small class="text-muted d-block">{{ $attachment->file_size_formatted }}</small>
                                                    <div class="btn-group btn-group-sm mt-1">
                                                        @if(!in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                                            <a href="{{ route('attachments.download', $attachment) }}" 
                                                               class="btn btn-outline-primary btn-sm" 
                                                               title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        @endif
                                                        @if($reply->isFromAdmin())
                                                        <form method="POST" action="{{ route('attachments.delete', $attachment) }}" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('Are you sure you want to delete this attachment?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Add Reply Form -->
                    @if($ticket->status !== 'closed')
                    <div class="mb-4">
                        <h5>Add Reply</h5>
                        <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-3">
                                <textarea name="message" class="form-control" rows="4" placeholder="Type your reply here..." required></textarea>
                            </div>
                            
                            <!-- File Upload -->
                            <div class="form-group mb-3">
                                <label for="attachments" class="form-label">Attachments (Optional)</label>
                                <input type="file" name="attachments[]" id="attachments" class="form-control" multiple 
                                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip,.rar">
                                <small class="form-text text-muted">
                                    Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX, TXT, ZIP, RAR. Max size: 10MB per file.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply"></i> Send Reply
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal for Full-Size Preview -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(imageSrc, filename) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalImage').alt = filename;
    document.getElementById('imageModalLabel').textContent = filename;
    
    // Bootstrap 5 modal
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>
@endsection