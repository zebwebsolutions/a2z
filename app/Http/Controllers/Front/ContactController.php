<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function index()
    {
        return view('front.contact');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $emailBody = "New contact form submission\n\n"
            . "Name: {$data['name']}\n"
            . "Email: {$data['email']}\n"
            . "Phone: " . ($data['phone'] ?: 'N/A') . "\n\n"
            . "Message:\n{$data['message']}\n";

        try {
            Mail::raw($emailBody, function ($mail) use ($data) {
                $mail->to('rahmanzeb@gmail.com')
                    ->subject('New Contact Form Message - A2Z')
                    ->replyTo($data['email'], $data['name']);
            });
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'We could not send your message right now. Please try again.');
        }

        return back()->with('success', 'Your message has been sent successfully.');
    }
}
