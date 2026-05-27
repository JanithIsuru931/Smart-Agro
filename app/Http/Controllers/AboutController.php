<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AboutController extends Controller
{
    public function show()
    {
        return view('about');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $body = "Name: {$data['name']}\nEmail: {$data['email']}\n\nMessage:\n{$data['message']}";

        try {
            Mail::raw($body, function ($message) use ($data) {
                $message->to('smartagro2025@gmail.com')
                    ->subject('Website Inquiry from ' . $data['name'])
                    ->replyTo($data['email']);
            });

            return back()->with('status', 'Your message has been sent.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Unable to send message at this time.']);
        }
    }
}
