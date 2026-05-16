@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0; color: #dc2626;">🚨 SLA BREACHED</h2>
    <p>Hello Management,</p>
    <p>Ticket <strong>{{ $ticket->ticket_number }}</strong> has breached its SLA deadline.</p>
    
    <div style="margin: 20px 0; padding: 15px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #991b1b;">
        <p style="margin: 0;"><strong>SLA Type:</strong> {{ $sla_type }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Breached At:</strong> {{ $breached_at }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Assigned To:</strong> {{ $ticket->assigned_to_name ?? 'Unassigned' }}</p>
        <p style="margin: 5px 0 0 0;"><strong>Group:</strong> {{ $ticket->assignment_group ?? 'None' }}</p>
    </div>

    <p>Immediate intervention is required to address this delay and resolve the customer issue.</p>
@endsection
