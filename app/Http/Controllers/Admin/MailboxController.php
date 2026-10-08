<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->input('folder', 'inbox');
        $emails = Email::where('folder', $folder)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.mailbox.index', compact('emails', 'folder'));
    }

    public function show($id)
    {
        $email = Email::findOrFail($id);
        
        if (!$email->is_read) {
            $email->update(['is_read' => true]);
        }

        return view('admin.mailbox.show', compact('email'));
    }

    public function compose()
    {
        return view('admin.mailbox.compose');
    }

    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            Mail::html($request->body, function($message) use ($request) {
                $message->to($request->to)->subject($request->subject);
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        Email::create([
            'to_emails' => $request->to,
            'subject' => $request->subject,
            'body' => $request->body,
            'body_plain' => strip_tags($request->body),
            'folder' => 'sent',
            'is_read' => true,
        ]);

        return redirect()->route('mailbox.index', ['folder' => 'sent'])
            ->with('success', 'Email sent successfully.');
    }

    public function destroy($id)
    {
        $email = Email::findOrFail($id);
        
        if ($email->folder === 'trash') {
            $email->delete();
        } else {
            $email->update(['folder' => 'trash']);
        }

        return redirect()->back()->with('success', 'Email moved to trash.');
    }
}
