<?php

namespace App\Console\Commands;

use App\Services\OmniChannelService;
use Illuminate\Console\Command;

class PollEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll the support mailbox for new incoming emails';

    /**
     * Execute the console command.
     */
    public function handle(OmniChannelService $service)
    {
        $this->info('Starting email polling...');
        $service->pollIncomingEmails();
        $this->info('Email polling completed.');
    }
}
