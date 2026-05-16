@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0;">Ticket Assigned</h2>
    <p>Hello {{ $ticket->assigned_to_name }},</p>
    <p>A ticket has been assigned to you for resolution.</p>
    
    <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #3b82f6; background-color: #eff6ff;">
        <p style="margin: 0;"><strong>Requester:</strong> {{ $ticket->caller }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Category:</strong> {{ $ticket->category }}</p>
    </div>

    <p>Please review the ticket details and provide an update as soon as possible to meet the SLA requirements.</p>
@endsection
