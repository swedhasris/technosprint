@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0; color: #9a3412;">⚠️ SLA Warning</h2>
    <p>Hello {{ $ticket->assigned_to_name ?? 'Team' }},</p>
    <p>Ticket <strong>{{ $ticket->ticket_number }}</strong> is approaching its SLA deadline.</p>
    
    <div style="margin: 20px 0; padding: 15px; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; color: #92400e;">
        <p style="margin: 0;"><strong>SLA Type:</strong> {{ $sla_type }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Deadline:</strong> {{ $deadline }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Time Remaining:</strong> {{ $time_remaining }}</p>
    </div>

    <p>Please take immediate action to resolve this ticket before the SLA is breached.</p>
@endsection
