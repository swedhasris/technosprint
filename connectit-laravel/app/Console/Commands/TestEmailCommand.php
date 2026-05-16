<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\TicketService;
use App\Events\TicketCreated;
use Illuminate\Console\Command;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test ticket and trigger an email notification';

    /**
     * Execute the console command.
     */
    public function handle(TicketService $ticketService)
    {
        $email = $this->argument('email');
        $this->info("Creating test ticket for {$email}...");

        $ticket = $ticketService->createTicket([
            'title' => 'Test Email Ticket - ' . now()->format('H:i:s'),
            'caller' => 'Test User',
            'caller_email' => $email,
            'impact' => '3 - Low',
            'urgency' => '3 - Low',
            'category' => 'Hardware',
            'description' => 'This is a test ticket to verify the email notification system.',
            'channel' => 'Self-service'
        ]);

        $this->info("Ticket [{$ticket->ticket_number}] created successfully!");
        $this->info("The TicketCreated event has been dispatched.");
        $this->info("Since QUEUE_CONNECTION=sync, the email should be sent immediately.");
        
        $this->info("Checking Email Logs...");
        $log = \App\Models\EmailLog::where('ticket_id', $ticket->id)->latest()->first();
        
        if ($log) {
            $this->info("Email Log Status: {$log->status}");
            if ($log->status === 'failed') {
                $this->error("Error: {$log->error_message}");
            }
        } else {
            $this->warn("No email log found yet. This might happen if the mail was sent but not logged correctly.");
        }
    }
}
