<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['staff', 'avatar']);

        return response()->json(['data' => $this->profileData($user)]);
    }

    public function details(Request $request)
    {
        $user = $request->user();
        $user->loadMissing([
            'staff.idProofImage',
            'staff.signatureImage',
            'customer.idProofImage',
            'customer.signatureImage',
            'avatar',
        ]);

        return response()->json(['data' => $this->detailsData($user)]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        $user->loadMissing([
            'staff.idProofImage',
            'staff.signatureImage',
            'customer.idProofImage',
            'customer.signatureImage',
            'avatar',
        ]);
        $profile = $this->profileData($user);
        $details = $this->detailsData($user);

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
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $validated = $request->validate([
            'fullName' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'required', 'string', 'min:7', 'max:20'],
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
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
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
            'oldPassword' => ['required_without:currentPassword', 'string', 'min:8', 'max:128'],
            'currentPassword' => ['required_without:oldPassword', 'string', 'min:8', 'max:128'],
            'newPassword' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        $current = $validated['oldPassword'] ?? $validated['currentPassword'] ?? '';
        if (!Hash::check($current, $user->password)) {
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => 'Current password does not match'], 422);
            }
            return redirect()->back()->withErrors(['oldPassword' => 'Current password does not match']);
        }

        $passwordHash = Hash::make($validated['newPassword']);
        $user->password = $passwordHash;
        $user->password_hash = $passwordHash;
        $user->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Password updated successfully']);
        }

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function updateAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $user = $request->user();
        $oldAvatar = $user->avatar;
        $path = $validated['avatar']->storePublicly('avatars', 'public');

        try {
            DB::transaction(function () use ($user, $oldAvatar, $path): void {
                $avatar = Image::create([
                    'url' => Storage::disk('public')->url($path),
                    'alt_text' => ($user->full_name ?? $user->name) . ' avatar',
                ]);
                $user->profile_id = $avatar->id;
                $user->save();
                $oldAvatar?->delete();
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        if ($oldAvatar) {
            $this->deleteStoredImage($oldAvatar);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Profile image updated successfully.']);
        }

        return redirect()->back()->with('success', 'Profile image updated successfully.');
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        $avatar = $user->avatar;

        if ($avatar) {
            DB::transaction(function () use ($user, $avatar): void {
                $user->profile_id = null;
                $user->save();
                $avatar->delete();
            });
            $this->deleteStoredImage($avatar);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Profile image removed successfully.']);
        }

        return redirect()->back()->with('success', 'Profile image removed successfully.');
    }

    private function profileData($user): array
    {
        return [
            'id' => $user->id,
            'fullName' => $user->full_name ?? $user->name,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
            'category' => $user->staff?->category instanceof \BackedEnum ? $user->staff->category->value : ($user->staff?->category ? (string) $user->staff->category : null),
            'avatar' => $user->avatar ? [
                'id' => $user->avatar->id,
                'url' => $user->avatar->url,
                'altText' => $user->avatar->alt_text,
            ] : null,
            'isActive' => (bool) $user->is_active,
            'createdAt' => $user->created_at?->toIso8601String(),
            'lastLoginAt' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function detailsData($user): array
    {
        $details = $user->staff ?? $user->customer;

        return [
            ...$this->profileData($user),
            'idProofNumber' => $details?->id_proof_number,
            'experience' => $user->staff?->experience,
            'qualification' => $user->staff?->qualification,
            'emergencyContact' => $user->staff?->emergency_contact,
            'address' => $details?->address,
            'idProofImage' => $this->imageData($details?->idProofImage),
            'signatureImage' => $this->imageData($details?->signatureImage),
        ];
    }

    private function imageData(?Image $image): ?array
    {
        return $image ? [
            'id' => $image->id,
            'url' => $image->url,
            'altText' => $image->alt_text,
        ] : null;
    }

    private function deleteStoredImage(Image $image): void
    {
        $publicUrl = rtrim(Storage::disk('public')->url(''), '/');
        if (str_starts_with($image->url, $publicUrl . '/')) {
            Storage::disk('public')->delete(substr($image->url, strlen($publicUrl) + 1));
        }
    }
}
