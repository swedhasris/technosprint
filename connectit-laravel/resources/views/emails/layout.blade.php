<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        body {
            background-color: #f6f9fc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
            -webkit-font-smoothing: antialiased;
            height: 100% !important;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 30px;
            color: #334155;
        }
        .ticket-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .ticket-field {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .ticket-field strong {
            color: #64748b;
            width: 120px;
            display: inline-block;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-priority-1 { background-color: #fee2e2; color: #991b1b; } /* Critical */
        .badge-priority-2 { background-color: #ffedd5; color: #9a3412; } /* High */
        .badge-priority-3 { background-color: #fef9c3; color: #854d0e; } /* Medium */
        .badge-priority-4 { background-color: #f1f5f9; color: #475569; } /* Low */
        
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #94a3b8;
        }
        .reply-instruction {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="wrapper">
            <div class="header">
                <h1>Ticklora ITSM</h1>
            </div>
            <div class="content">
                <div class="reply-instruction">
                    --- Reply above this line to update your ticket [{{ $ticket->ticket_number }}] ---
                </div>
                
                @yield('content')
                
                <div class="ticket-card">
                    <div class="ticket-field"><strong>Ticket:</strong> {{ $ticket->ticket_number }}</div>
                    <div class="ticket-field"><strong>Subject:</strong> {{ $ticket->title }}</div>
                    <div class="ticket-field"><strong>Status:</strong> {{ is_string($ticket->status) ? $ticket->status : $ticket->status->value }}</div>
                    <div class="ticket-field">
                        <strong>Priority:</strong> 
                        <span class="badge badge-priority-{{ substr($ticket->priority->value ?? $ticket->priority, 0, 1) }}">
                            {{ is_string($ticket->priority) ? $ticket->priority : $ticket->priority->value }}
                        </span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" class="button">View in Portal</a>
                </div>
            </div>
            <div class="footer">
                <p>Sent by Ticklora Support. All rights reserved.</p>
                <p>This is an automated notification. To update this ticket, please reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
