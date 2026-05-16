@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0;">Ticket Received</h2>
    <p>Hello {{ $ticket->caller }},</p>
    <p>A new support ticket has been created for your request. Our team will review the details and get back to you shortly.</p>
    
    <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #e2e8f0;">
        <p style="margin: 0; font-style: italic; color: #475569;">
            "{{ $ticket->description }}"
        </p>
    </div>
@endsection
