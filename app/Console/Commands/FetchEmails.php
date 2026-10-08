<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use App\Models\Email;
use Illuminate\Support\Str;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mails:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new emails from the configured IMAP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Check if IMAP is configured
            if (!config('imap.accounts.default.host')) {
                $this->error('IMAP is not configured.');
                return;
            }

            $client = Client::account('default');
            $client->connect();
            
            $folders = $client->getFolders();
            
            foreach($folders as $folder) {
                if(strtolower($folder->name) !== 'inbox') continue;

                // Limit to 20 emails per fetch to avoid Memory Exhaustion on large inboxes
                $messages = $folder->query()->unseen()->limit(20)->get();
                
                foreach($messages as $message){
                    
                    // Check if email already exists
                    if (Email::where('message_id', $message->getMessageId())->exists()) {
                        continue;
                    }

                    $from = $message->getFrom()[0];
                    $bodyPlain = $message->getTextBody();
                    $bodyHtml = $message->getHTMLBody();

                    Email::create([
                        'message_id' => $message->getMessageId(),
                        'from_email' => $from->mail,
                        'from_name'  => $from->personal,
                        'to_emails'  => collect($message->getTo())->pluck('mail')->implode(','),
                        'subject'    => $message->getSubject(),
                        'body'       => $bodyHtml ?? nl2br($bodyPlain),
                        'body_plain' => $bodyPlain ?? strip_tags($bodyHtml),
                        'folder'     => 'inbox',
                        'is_read'    => false,
                        'has_attachments' => $message->hasAttachments()
                    ]);

                    // Mark as seen on the server
                    $message->setFlag(['Seen']);
                }
            }

            $this->info('Emails fetched successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to fetch emails: ' . $e->getMessage());
        }
    }
}
