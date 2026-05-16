<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\NotificationQueue;
use App\Models\EmailLog;
use Webklex\IMAP\Facades\Client;

class DiagnosticsController extends Controller
{
    /**
     * Get email health and status
     */
    public function emailHealth()
    {
        $diagnostics = [
            'smtp' => $this->checkSmtp(),
            'imap' => $this->checkImap(),
            'queue' => $this->checkQueue(),
            'stats' => $this->getStats(),
            'timestamp' => now()->toDateTimeString()
        ];

        return response()->json($diagnostics);
    }

    private function checkSmtp()
    {
        try {
            $transport = Mail::mailer()->getSymfonyTransport();
            return [
                'status' => 'healthy',
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkImap()
    {
        try {
            $client = Client::account('default');
            $client->connect();
            return [
                'status' => 'healthy',
                'host' => config('imap.accounts.default.host'),
                'username' => config('imap.accounts.default.username')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkQueue()
    {
        $pending = NotificationQueue::where('status', 'pending')->count();
        $failed = NotificationQueue::where('status', 'failed')->count();
        
        return [
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'status' => $failed > 10 ? 'degraded' : 'healthy'
        ];
    }

    private function getStats()
    {
        return [
            'sent_last_24h' => EmailLog::where('direction', 'outbound')->where('status', 'sent')->where('created_at', '>=', now()->subDay())->count(),
            'received_last_24h' => EmailLog::where('direction', 'inbound')->where('created_at', '>=', now()->subDay())->count(),
            'failed_last_24h' => EmailLog::where('direction', 'outbound')->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count(),
        ];
    }
}
