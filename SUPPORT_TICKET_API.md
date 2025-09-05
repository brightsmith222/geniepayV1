# Support Ticket System API Documentation

## Overview
This document describes the API endpoints for the support ticket system in your Laravel application. The system allows users to create support tickets, add replies, and for admins to manage tickets.

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the `Authorization: Bearer {token}` header in your requests.

## Base URL
```
https://yourdomain.com/api
```

## Endpoints

### User Endpoints

#### 1. Get User's Tickets
**GET** `/tickets`

Returns all tickets for the authenticated user with their replies.

**Response:**
```json
{
    "status": true,
    "message": "Tickets retrieved successfully",
    "data": [
        {
            "id": 1,
                                    "subject": "Payment Issue",
                        "description": "I can't complete my payment",
                        "status": "open",
                        "priority": "high",
                        "category": "billing",
                        "created_at": "2024-01-01 10:00:00",
                        "updated_at": "2024-01-01 10:00:00",
                        "replies_count": 2,
            "replies": [
                {
                    "id": 1,
                    "message": "I can't complete my payment",
                    "author_name": "John Doe",
                    "is_from_admin": false,
                    "created_at": "2024-01-01 10:00:00"
                },
                {
                    "id": 2,
                    "message": "We're looking into this issue",
                    "author_name": "Support Team",
                    "is_from_admin": true,
                    "created_at": "2024-01-01 11:00:00"
                }
            ]
        }
    ]
}
```

#### 2. Create New Ticket
**POST** `/tickets`

Creates a new support ticket.

**Request Body:**
```json
{
    "subject": "Payment Issue",
    "description": "I can't complete my payment transaction",
    "priority": "high",
    "category": "billing"
}
```

**Priority Options:** `low`, `medium`, `high`, `urgent` (default: `medium`)
**Category Options:** `technical`, `billing`, `general`, `feature_request`, `bug_report` (default: `general`)

**Response:**
```json
{
    "status": true,
    "message": "Ticket created successfully",
    "data": {
        "id": 1,
        "subject": "Payment Issue",
        "description": "I can't complete my payment transaction",
        "status": "open",
        "priority": "high",
        "category": "billing",
        "created_at": "2024-01-01 10:00:00"
    }
}
```

#### 3. Get Specific Ticket
**GET** `/tickets/{ticket_id}`

Returns a specific ticket with all its replies.

**Response:**
```json
{
    "status": true,
    "message": "Ticket retrieved successfully",
    "data": {
        "id": 1,
        "subject": "Payment Issue",
        "description": "I can't complete my payment transaction",
        "status": "open",
        "priority": "high",
        "category": "billing",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00",
        "replies": [...]
    }
}
```

#### 4. Add Reply to Ticket
**POST** `/tickets/{ticket_id}/reply`

Adds a reply to an existing ticket.

**Request Body:**
```json
{
    "message": "I've tried multiple times but still getting an error"
}
```

**Response:**
```json
{
    "status": true,
    "message": "Reply added successfully",
    "data": {
        "id": 3,
        "message": "I've tried multiple times but still getting an error",
        "author_name": "John Doe",
        "is_from_admin": false,
        "created_at": "2024-01-01 12:00:00",
        "ticket_status": "in_progress"
    }
}
```

#### 5. Update Ticket Status (Admin Only)
**PATCH** `/tickets/{ticket_id}/status`

Updates the status of a ticket. Only admins can perform this action.

**Request Body:**
```json
{
    "status": "resolved"
}
```

**Response:**
```json
{
    "status": true,
    "message": "Ticket status updated successfully",
    "data": {
        "id": 1,
        "status": "resolved",
        "updated_at": "2024-01-01 15:00:00"
    }
}
```

## Admin Panel (Web Interface)

The admin panel for managing support tickets is available through the web interface at `/tickets` and related routes. These are protected by admin authentication and provide a full web interface for ticket management.

**Admin Routes:**
- `GET /tickets` - View all tickets with filtering options
- `GET /tickets/{ticket}` - View specific ticket details
- `GET /tickets/statistics` - View ticket statistics dashboard
- `POST /tickets/{ticket}/reply` - Add admin reply to ticket
- `PATCH /tickets/{ticket}/status` - Update ticket status
- `POST /tickets/{ticket}/close` - Close a ticket

**Note:** Admin functionality is handled through the web interface, not the API endpoints.

## Ticket Statuses

- **open**: Ticket is newly created and waiting for admin response
- **in_progress**: Ticket is being handled by support team
- **resolved**: Issue has been resolved
- **closed**: Ticket is closed (no more replies allowed)

## Error Responses

All endpoints return consistent error responses:

```json
{
    "status": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

Common HTTP Status Codes:
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `422`: Validation Error
- `500`: Internal Server Error

## Flutter Integration Examples

### Creating a Ticket
```dart
Future<void> createTicket(String subject, String description, {String priority = 'medium', String category = 'general'}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/tickets'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'subject': subject,
      'description': description,
      'priority': priority,
      'category': category,
    }),
  );
  
  if (response.statusCode == 201) {
    // Handle success
  }
}
```

### Getting User Tickets
```dart
Future<List<Ticket>> getUserTickets() async {
  final response = await http.get(
    Uri.parse('$baseUrl/tickets'),
    headers: {
      'Authorization': 'Bearer $token',
    },
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return (data['data'] as List)
        .map((json) => Ticket.fromJson(json))
        .toList();
  }
  
  throw Exception('Failed to load tickets');
}
```

## Notes

1. **Authentication**: All endpoints require a valid Sanctum token
2. **Admin Access**: Admin endpoints check if the user has `role == 1`
3. **Status Updates**: When admins change ticket status, an automatic reply is added
4. **Closed Tickets**: Users cannot reply to closed tickets
5. **Auto Status Update**: Ticket status automatically changes to "in_progress" when admin replies to an "open" ticket
6. **Cascade Deletion**: Deleting a ticket will also delete all its replies
7. **Indexing**: Database is properly indexed for optimal performance

## Database Schema

### tickets table
- `id` (primary key)
- `user_id` (foreign key to users)
- `subject` (string, max 255)
- `description` (text)
- `status` (enum: open, in_progress, resolved, closed)
- `priority` (enum: low, medium, high, urgent)
- `category` (enum: technical, billing, general, feature_request, bug_report)
- `timestamps`

### ticket_replies table
- `id` (primary key)
- `ticket_id` (foreign key to tickets)
- `user_id` (nullable, foreign key to users)
- `message` (text)
- `timestamps`
