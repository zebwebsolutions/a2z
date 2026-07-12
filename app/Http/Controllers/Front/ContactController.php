<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function index()
    {
        return view('front.contact', [
            'contactFormStartedAt' => now()->timestamp,
        ]);
    }

    public function send(Request $request)
    {
        if ($this->isBotSubmission($request)) {
            return back()->with('success', 'Your message has been submitted successfully.');
        }

        $rateLimitKey = 'contact-form:'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            throw ValidationException::withMessages([
                'message' => 'Too many messages were sent from your connection. Please try again later.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
            'website' => ['nullable', 'prohibited'],
            'contact_form_started_at' => ['required', 'integer'],
        ]);

        if ($this->looksLikeSpam($data)) {
            return back()->with('success', 'Your message has been submitted successfully.');
        }

        ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 65535),
        ]);

        return back()->with('success', 'Your message has been submitted successfully.');
    }

    private function isBotSubmission(Request $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        $startedAt = (int) $request->input('contact_form_started_at', 0);

        return $startedAt <= 0 || now()->timestamp - $startedAt < 3;
    }

    private function looksLikeSpam(array $data): bool
    {
        $text = Str::lower(($data['name'] ?? '').' '.($data['email'] ?? '').' '.($data['message'] ?? ''));
        $linkCount = preg_match_all('/https?:\/\/|www\.|\.ru\b|\.cn\b|\.xyz\b|\.top\b/i', $text);
        $spamWords = [
            'casino',
            'crypto',
            'forex',
            'loan',
            'seo',
            'backlink',
            'viagra',
            'telegram',
            'whatsapp marketing',
        ];

        if ($linkCount >= 2) {
            return true;
        }

        foreach ($spamWords as $word) {
            if (Str::contains($text, $word)) {
                return true;
            }
        }

        return false;
    }
}
