<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['staff', 'customer', 'avatar']);

        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'fullName' => $user->full_name ?? $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
            'isActive' => (bool) $user->is_active,
            'category' => $user->staff?->category instanceof \BackedEnum ? $user->staff->category->value : ($user->staff?->category ? (string) $user->staff->category : null),
            'avatar' => $user->avatar ? [
                'id' => $user->avatar->id,
                'url' => $user->avatar->url,
            ] : null,
        ];

        $details = null;
        if ($user->staff) {
            $details = [
                'category' => $user->staff->category instanceof \BackedEnum ? $user->staff->category->value : (string) $user->staff->category,
                'employmentStatus' => $user->staff->employment_status,
                'joiningDate' => $user->staff->joining_date?->toIso8601String(),
                'salary' => $user->staff->salary,
            ];
        } elseif ($user->customer) {
            $details = [
                'idProofType' => $user->customer->id_proof_type,
                'idProofNumber' => $user->customer->id_proof_number,
                'city' => $user->customer->city,
                'state' => $user->customer->state,
                'country' => $user->customer->country,
            ];
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => [
                    'user' => $profile,
                    'details' => $details,
                ],
            ]);
        }

        return Inertia::render('protect/profile/index', [
            'profile' => [
                'data' => $profile,
            ],
            'details' => [
                'data' => $details,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'fullName' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
        ]);

        if (isset($validated['fullName'])) {
            $user->full_name = $validated['fullName'];
            $user->name = $validated['fullName'];
        } elseif (isset($validated['name'])) {
            $user->name = $validated['name'];
            $user->full_name = $validated['name'];
        }
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Profile updated successfully']);
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'oldPassword' => ['required', 'string'],
            'currentPassword' => ['nullable', 'string'],
            'newPassword' => ['required', 'string', 'min:6'],
        ]);

        $current = $validated['oldPassword'] ?? $validated['currentPassword'] ?? '';
        if (!Hash::check($current, $user->password)) {
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => 'Current password does not match'], 422);
            }
            return redirect()->back()->withErrors(['oldPassword' => 'Current password does not match']);
        }

        $user->password = Hash::make($validated['newPassword']);
        $user->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Password updated successfully']);
        }

        return redirect()->back()->with('success', 'Password updated successfully.');
    }
}
