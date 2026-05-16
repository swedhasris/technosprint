@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0;">Approval Required</h2>
    <p>Hello,</p>
    <p>Your approval is required for ticket <strong>{{ $ticket->ticket_number }}</strong>.</p>
    
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px;">
        <p style="margin: 0;"><strong>Requested By:</strong> {{ $requested_by }}</p>
        <p style="margin: 10px 0 0 0;"><strong>Reason for Approval:</strong></p>
        <p style="margin: 5px 0 0 0; font-style: italic;">"{{ $approval_reason }}"</p>
    </div>

    <p>Please log in to the portal to approve or reject this request.</p>
@endsection
