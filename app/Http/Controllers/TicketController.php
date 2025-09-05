<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Get all tickets (admin only).
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $query = Ticket::with(['user:id,full_name,username,email', 'replies.user:id,full_name,username', 'replies.attachments', 'mainAttachments']);

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['open', 'in_progress', 'resolved', 'closed'])) {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->has('priority') && in_array($request->priority, ['low', 'medium', 'high', 'urgent'])) {
                $query->where('priority', $request->priority);
            }

            // Filter by category
            if ($request->has('category') && in_array($request->category, ['technical', 'billing', 'general', 'feature_request', 'bug_report'])) {
                $query->where('category', $request->category);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Search by subject or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $tickets = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return view('tickets.index', compact('tickets'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to retrieve tickets: ' . $e->getMessage());
        }
    }

    /**
     * Show ticket details.
     */
    public function show(Ticket $ticket)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $ticket->load(['replies.user:id,full_name,username', 'replies.attachments', 'user:id,full_name,username,email', 'mainAttachments']);

            return view('tickets.show', compact('ticket'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to retrieve ticket: ' . $e->getMessage());
        }
    }

    /**
     * Get ticket statistics (admin only).
     */
    public function statistics()
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $stats = [
                'total_tickets' => Ticket::count(),
                'open_tickets' => Ticket::where('status', 'open')->count(),
                'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
                'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
                'closed_tickets' => Ticket::where('status', 'closed')->count(),
                'urgent_tickets' => Ticket::where('priority', 'urgent')->count(),
                'high_priority_tickets' => Ticket::where('priority', 'high')->count(),
                'technical_tickets' => Ticket::where('category', 'technical')->count(),
                'billing_tickets' => Ticket::where('category', 'billing')->count(),
                'tickets_today' => Ticket::whereDate('created_at', today())->count(),
                'tickets_this_week' => Ticket::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'tickets_this_month' => Ticket::whereMonth('created_at', now()->month)->count(),
            ];

            return view('tickets.statistics', compact('stats'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to retrieve statistics: ' . $e->getMessage());
        }
    }

    /**
     * Add admin reply to ticket.
     */
    public function addReply(Request $request, Ticket $ticket)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:5000',
                'attachments.*' => 'sometimes|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip,rar',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if ticket is closed
            if ($ticket->status === 'closed') {
                return redirect()->back()->with('error', 'Cannot reply to a closed ticket');
            }

            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => null, // Admin reply
                'message' => $request->message
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('ticket-attachments', $filename, 'public');
                    
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_reply_id' => $reply->id,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_filename' => $filename,
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Update ticket status to in_progress if it was open
            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }

            return redirect()->back()->with('success', 'Reply added successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to add reply: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket status (admin only).
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:open,in_progress,resolved,closed',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
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

            return redirect()->back()->with('success', 'Ticket status updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update ticket status: ' . $e->getMessage());
        }
    }

    /**
     * Close a ticket (admin only).
     */
    public function close(Ticket $ticket)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            $ticket->update(['status' => 'closed']);

            // Add admin reply
            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'message' => 'Ticket has been closed. If you need to reopen it, please create a new ticket.'
            ]);

            return redirect()->back()->with('success', 'Ticket closed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to close ticket: ' . $e->getMessage());
        }
    }

        /**
     * View ticket attachment (display in browser).
     */
    public function viewAttachment(TicketAttachment $attachment)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return redirect()->back()->with('error', 'File not found');
            }

            $filePath = Storage::disk('public')->path($attachment->file_path);
            $mimeType = $attachment->mime_type;
            
            // For images and PDFs, display in browser
            if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'])) {
                return response()->file($filePath, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $attachment->original_filename . '"'
                ]);
            }
            
            // For text files, display content
            if (in_array($mimeType, ['text/plain', 'text/html'])) {
                $content = file_get_contents($filePath);
                return response($content, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $attachment->original_filename . '"'
                ]);
            }
            
            // For other files, show a preview page
            return view('tickets.attachment-preview', compact('attachment'));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to view file: ' . $e->getMessage());
        }
    }

    /**
     * Download ticket attachment (force download).
     */
    public function downloadAttachment(TicketAttachment $attachment)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return redirect()->back()->with('error', 'File not found');
            }

            $filePath = Storage::disk('public')->path($attachment->file_path);
            
            return response()->download($filePath, $attachment->original_filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download file: ' . $e->getMessage());
        }
    }

    /**
     * Delete ticket attachment.
     */
    public function deleteAttachment(TicketAttachment $attachment)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized access. Admin privileges required.');
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // Delete attachment record
            $attachment->delete();

            return redirect()->back()->with('success', 'Attachment deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete attachment: ' . $e->getMessage());
        }
    }
}