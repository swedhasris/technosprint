@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0; color: #059669;">✅ Ticket Resolved</h2>
    <p>Hello {{ $ticket->caller }},</p>
    <p>We are pleased to inform you that your ticket <strong>{{ $ticket->ticket_number }}</strong> has been marked as resolved.</p>
    
    <div style="margin: 20px 0; padding: 20px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;">
        <p style="margin: 0;"><strong>Resolution Code:</strong> {{ $ticket->resolution_code }}</p>
        <p style="margin: 10px 0 0 0;"><strong>Resolution Notes:</strong></p>
        <p style="margin: 5px 0 0 0; font-style: italic;">"{{ $ticket->resolution_notes }}"</p>
    </div>

    <p>If you feel this ticket has not been resolved to your satisfaction, you can reopen it by replying to this email or through the portal.</p>
    <p>This ticket will automatically close in 3 business days if no further action is taken.</p>
@endsection
