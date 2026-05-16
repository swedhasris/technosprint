<?php

namespace App\Jobs;

use App\Mail\OmnichannelMail;
use App\Models\Ticket;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900, 1800, 3600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ticket $ticket,
        public string $recipient,
        public string $subject,
        public string $view,
        public array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Mail::to($this->recipient)->send(new OmnichannelMail($this->subject, $this->view, $this->data));

            $notificationService->logEmail(
                $this->ticket,
                'outbound',
                $this->recipient,
                config('mail.from.address'),
                $this->subject,
                'sent'
            );
        } catch (\Exception $e) {
            Log::error("Job failed to send email to {$this->recipient}: " . $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                $notificationService->logEmail(
                    $this->ticket,
                    'outbound',
                    $this->recipient,
                    config('mail.from.address'),
                    $this->subject,
                    'failed',
                    null,
                    $e->getMessage()
                );
            }
            
            throw $e;
        }
    }
}
