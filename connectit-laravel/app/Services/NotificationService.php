<?php

namespace App\Services;

use App\Models\NotificationQueue;
use App\Models\EmailLog;
use App\Models\Ticket;
use App\Mail\OmnichannelMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Queue an email notification
     */
    public function queueEmail(Ticket $ticket, string $recipient, string $subject, string $view, array $data): NotificationQueue
    {
        return NotificationQueue::create([
            'ticket_id' => $ticket->id,
            'recipient' => $recipient,
            'channel' => 'email',
            'subject' => $subject,
            'body' => $view, // We store the view name, data will be passed to job
            'status' => 'pending',
            'metadata' => json_encode($data) // Assuming metadata column or using body for data
        ]);
    }

    /**
     * Send email directly and log it
     */
    public function sendEmail(Ticket $ticket, string $recipient, string $subject, string $view, array $data): bool
    {
        try {
            Mail::to($recipient)->send(new OmnichannelMail($subject, $view, $data));

            $this->logEmail($ticket, 'outbound', $recipient, config('mail.from.address'), $subject, 'sent');
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$recipient}: " . $e->getMessage());
            
            $this->logEmail($ticket, 'outbound', $recipient, config('mail.from.address'), $subject, 'failed', null, $e->getMessage());
            
            return false;
        }
    }

    /**
     * Log email activity
     */
    public function logEmail(Ticket $ticket, string $direction, ?string $recipient, ?string $sender, ?string $subject, string $status, ?string $messageId = null, ?string $errorMessage = null): EmailLog
    {
        return EmailLog::create([
            'ticket_id' => $ticket->id,
            'direction' => $direction,
            'recipient' => $recipient,
            'sender' => $sender,
            'subject' => $subject,
            'message_id' => $messageId,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $direction === 'outbound' ? Carbon::now() : null,
            'received_at' => $direction === 'inbound' ? Carbon::now() : null,
        ]);
    }
}
