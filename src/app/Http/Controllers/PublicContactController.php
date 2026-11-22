<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicContactController extends Controller
{
    public function index()
    {
        return Inertia::render('public/contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullName' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $name = $validated['fullName'] ?? $validated['name'] ?? 'Website Visitor';

        Inquiry::create([
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? 'Website Inquiry',
            'message' => $validated['message'],
            'status' => 'UNREAD',
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Thank you for your message. We will get back to you shortly.']);
        }

        return redirect()->back()->with('success', 'Thank you for your message. We will get back to you shortly.');
    }
}
