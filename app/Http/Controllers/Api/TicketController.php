<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Get all tickets for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            $tickets = $user->tickets()
                ->with(['replies.user:id,full_name,username', 'replies.attachments', 'mainAttachments'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($ticket) use ($user) {
                    return [
                        'id' => $ticket->id,
                        'user_id' => $ticket->user_id,
                        'subject' => $ticket->subject,
                        'description' => $ticket->description,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'category' => $ticket->category,
                        'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $ticket->updated_at->format('Y-m-d H:i:s'),
                        'replies_count' => $ticket->replies->count(),
                        'attachments_count' => $ticket->attachments->count(),
                        'replies' => $ticket->replies->map(function ($reply) {
                            return [
                                'id' => $reply->id,
                                'message' => $reply->message,
                                'author_name' => $reply->author_name,
                                'is_from_admin' => $reply->isFromAdmin(),
                                'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                                'attachments' => $reply->attachments->map(function ($attachment) {
                                    return [
                                        'id' => $attachment->id,
                                        'original_filename' => $attachment->original_filename,
                                        'stored_filename' => $attachment->stored_filename,
                                        'file_size' => $attachment->file_size_formatted,
                                        'mime_type' => $attachment->mime_type,
                                        'url' => asset('storage/' . $attachment->file_path),
                                    ];
                                }),
                            ];
                        }),
                        'main_attachments' => $ticket->mainAttachments->map(function ($attachment) {
                            return [
                                'id' => $attachment->id,
                                'original_filename' => $attachment->original_filename,
                                'stored_filename' => $attachment->stored_filename,
                                'file_size' => $attachment->file_size_formatted,
                                'mime_type' => $attachment->mime_type,
                                'url' => asset('storage/' . $attachment->file_path),
                            ];
                        }),
                        'userStats' => [
                            'total_tickets' => $user->tickets()->count(),
                            'open_tickets' => $user->tickets()->where('status', 'open')->count(),
                            'pending_tickets' => $user->tickets()->whereIn('status', ['open', 'in_progress'])->count(),
                            'resolved_tickets' => $user->tickets()->where('status', 'resolved')->count(),
                        ],
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Tickets retrieved successfully',
                'data' => $tickets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new ticket.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:5000',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'category' => 'sometimes|in:technical,billing,general,feature_request,bug_report',
                'attachments.*' => 'sometimes|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip,rar',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'description' => $request->description,
                'status' => 'open',
                'priority' => $request->priority ?? 'medium',
                'category' => $request->category ?? 'general'
            ]);

            Log::info('Ticket created', ['ticket' => $ticket]);

            // Create initial reply from the user
            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description
            ]);

            Log::info('Initial ticket reply created', ['reply' => $reply]);

            // Handle attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('ticket-attachments', $filename, 'public');

                    $attachment = TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_reply_id' => $reply->id,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_filename' => $filename,
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);

                    Log::info('Ticket attachment created', ['attachment' => $attachment]);

                    $attachments[] = [
                        'id' => $attachment->id,
                        'original_filename' => $attachment->original_filename,
                        'stored_filename' => $attachment->stored_filename,
                        'file_size' => $attachment->file_size_formatted,
                        'mime_type' => $attachment->mime_type,
                        'url' => asset('storage/' . $attachment->file_path),
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Ticket created successfully',
                'data' => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category,
                    'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                    'attachments' => $attachments,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a reply to an existing ticket.
     */
    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:5000',
                'attachments.*' => 'sometimes|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip,rar',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Check if user owns the ticket or is admin
            if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access to this ticket'
                ], 403);
            }

            // Check if ticket is closed
            if ($ticket->status === 'closed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot reply to a closed ticket'
                ], 400);
            }

            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->message
            ]);

            // Handle attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('ticket-attachments', $filename, 'public');

                    $attachment = TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_reply_id' => $reply->id,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_filename' => $filename,
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);

                    $attachments[] = [
                        'id' => $attachment->id,
                        'original_filename' => $attachment->original_filename,
                        'stored_filename' => $attachment->stored_filename,
                        'file_size' => $attachment->file_size_formatted,
                        'mime_type' => $attachment->mime_type,
                        'url' => asset('storage/' . $attachment->file_path),
                    ];
                }
            }

            // Update ticket status to in_progress if it was open and reply is from admin
            if ($ticket->status === 'open' && $user->isAdmin()) {
                $ticket->update(['status' => 'in_progress']);
            }

            return response()->json([
                'status' => true,
                'message' => 'Reply added successfully',
                'data' => [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'author_name' => $reply->author_name,
                    'is_from_admin' => $reply->isFromAdmin(),
                    'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                    'ticket_status' => $ticket->status,
                    'attachments' => $attachments,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ticket status (admin only).
     */
    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access. Admin privileges required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldStatus = $ticket->status;
            $ticket->update(['status' => $request->status]);

            // Add admin reply if status is being changed
            if ($oldStatus !== $request->status) {
                $statusMessages = [
                    'open' => 'Ticket status changed to open',
                    'in_progress' => 'Ticket is now being processed by our support team',
                    'resolved' => 'Ticket has been resolved. Please let us know if you need further assistance.',
                    'closed' => 'Ticket has been closed. If you need to reopen it, please create a new ticket.'
                ];

                TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => null, // Admin reply
                    'message' => $statusMessages[$request->status]
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Ticket status updated successfully',
                'data' => [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'updated_at' => $ticket->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update ticket status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific ticket with replies.
     */
    public function show(Ticket $ticket): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user owns the ticket or is admin
            if ($ticket->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access to this ticket'
                ], 403);
            }

            $ticket->load(['replies.user:id,full_name,username', 'replies.attachments', 'mainAttachments']);

            return response()->json([
                'status' => true,
                'message' => 'Ticket retrieved successfully',
                'data' => [
                    'id' => $ticket->id,
                    'user_id' => $ticket->user_id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category,
                    'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $ticket->updated_at->format('Y-m-d H:i:s'),
                    'attachments' => $ticket->mainAttachments->map(function ($attachment) {
                        return [
                            'id' => $attachment->id,
                            'original_filename' => $attachment->original_filename,
                            'stored_filename' => $attachment->stored_filename,
                            'file_size' => $attachment->file_size_formatted,
                            'mime_type' => $attachment->mime_type,
                            'url' => asset('storage/' . $attachment->file_path),
                        ];
                    }),
                    'replies' => $ticket->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'message' => $reply->message,
                            'author_name' => $reply->author_name,
                            'is_from_admin' => $reply->isFromAdmin(),
                            'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                            'attachments' => $reply->attachments->map(function ($attachment) {
                                return [
                                    'id' => $attachment->id,
                                    'original_filename' => $attachment->original_filename,
                                    'stored_filename' => $attachment->stored_filename,
                                    'file_size' => $attachment->file_size_formatted,
                                    'mime_type' => $attachment->mime_type,
                                    'url' => asset('storage/' . $attachment->file_path),
                                ];
                            }),
                        ];
                    }),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View ticket attachment (display in browser).
     */
    public function viewAttachment(TicketAttachment $attachment): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user has access to this attachment
            if ($attachment->ticket->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access to this attachment'
                ], 403);
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Generate view URL for the file
            $viewUrl = Storage::url($attachment->file_path);

            return response()->json([
                'status' => true,
                'message' => 'View link generated',
                'data' => [
                    'view_url' => $viewUrl,
                    'filename' => $attachment->original_filename,
                    'stored_filename' => $attachment->stored_filename,
                    'file_size' => $attachment->file_size_formatted,
                    'mime_type' => $attachment->mime_type,
                    'is_image' => in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
                    'is_pdf' => $attachment->mime_type === 'application/pdf',
                    'is_text' => in_array($attachment->mime_type, ['text/plain', 'text/html']),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate view link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download ticket attachment (force download).
     */
    public function downloadAttachment(TicketAttachment $attachment): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user has access to this attachment
            if ($attachment->ticket->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access to this attachment'
                ], 403);
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Generate download URL for the file
            $downloadUrl = Storage::url($attachment->file_path);

            return response()->json([
                'status' => true,
                'message' => 'Download link generated',
                'data' => [
                    'download_url' => $downloadUrl,
                    'filename' => $attachment->original_filename,
                    'stored_filename' => $attachment->stored_filename,
                    'file_size' => $attachment->file_size_formatted,
                    'mime_type' => $attachment->mime_type,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate download link',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
