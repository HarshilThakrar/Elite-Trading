<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $chats = \App\Models\Chat::with('user')->orderBy('created_at', 'asc')->get();
        return response()->json($chats);
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:20480',
        ]);

        if (!$request->message && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment is required.'], 422);
        }

        $chat = new \App\Models\Chat();
        $chat->user_id = auth()->id();
        $chat->message = $request->message;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mimeType = $file->getMimeType();
            $path = $file->store('chat_attachments', 'public');
            
            $chat->file_path = $path;
            
            if (str_starts_with($mimeType, 'video/')) {
                $chat->file_type = 'video';
            } else {
                $chat->file_type = 'image';
            }
        }

        $chat->save();
        $chat->load('user');

        return response()->json($chat);
    }
}
