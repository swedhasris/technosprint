@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0;">New Update</h2>
    <p>Hello,</p>
    <p>A new public comment has been added to ticket <strong>{{ $ticket->ticket_number }}</strong> by <strong>{{ $comment_author }}</strong>.</p>
    
    <div style="margin: 20px 0; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <p style="margin: 0; white-space: pre-wrap;">{{ $comment_text }}</p>
    </div>

    <p>You can respond by replying to this email or by clicking the button below.</p>
@endsection
