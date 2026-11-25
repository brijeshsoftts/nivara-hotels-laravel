<?php

namespace App\Services;

use App\Enums\Category;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationService
{
    /** @param list<string> $userIds */
    public function createForUsers(
        array $userIds,
        NotificationType $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        $recipientIds = User::query()
            ->whereIn('id', array_unique($userIds))
            ->where('is_active', true)
            ->whereNull('soft_deleted_at')
            ->pluck('id');

        foreach ($recipientIds as $recipientId) {
            Notification::query()->create([
                'user_id' => $recipientId,
                'type' => $type->value,
                'title' => $title,
                'message' => $message,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        }
    }

    /** @param list<UserRole> $roles
     * @param  list<string>  $excludedUserIds
     */
    public function createForRoles(
        array $roles,
        NotificationType $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?string $referenceId = null,
        array $excludedUserIds = [],
    ): void {
        $userIds = User::query()
            ->whereIn('role', array_map(fn (UserRole $role): string => $role->value, $roles))
            ->where('is_active', true)
            ->whereNull('soft_deleted_at')
            ->when($excludedUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludedUserIds))
            ->pluck('id')
            ->all();

        $this->createForUsers($userIds, $type, $title, $message, $referenceType, $referenceId);
    }

    /** @return list<array{id: string, type: string, title: string, message: string, referenceType: ?string, referenceId: ?string, isRead: bool, createdAt: ?string}> */
    public function unreadForUser(User $user): array
    {
        $allowedTypes = $this->allowedTypes($user);

        return $this->userNotifications($user)
            ->where('is_read', false)
            ->when($allowedTypes !== null, fn ($query) => $query->whereIn('type', array_map(fn (NotificationType $type): string => $type->value, $allowedTypes)))
            ->orderByDesc('created_at')
            ->get(['id', 'type', 'title', 'message', 'reference_type', 'reference_id', 'is_read', 'created_at'])
            ->map(fn (Notification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->type->value,
                'title' => $notification->title,
                'message' => $notification->message,
                'referenceType' => $notification->reference_type,
                'referenceId' => $notification->reference_id,
                'isRead' => $notification->is_read,
                'createdAt' => $notification->created_at?->toISOString(),
            ])
            ->all();
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $notification = $this->userNotifications($user)
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(User $user): void
    {
        $allowedTypes = $this->allowedTypes($user);

        $this->userNotifications($user)
            ->where('is_read', false)
            ->when($allowedTypes !== null, fn ($query) => $query->whereIn('type', array_map(fn (NotificationType $type): string => $type->value, $allowedTypes)))
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    private function userNotifications(User $user): HasMany
    {
        $allowedTypes = $this->allowedTypes($user);
        $query = $user->notifications();

        if ($allowedTypes !== null) {
            $query->whereIn('type', array_map(fn (NotificationType $type): string => $type->value, $allowedTypes));
        }

        return $query;
    }

    /** @return list<NotificationType>|null */
    private function allowedTypes(User $user): ?array
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        if (in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)) {
            return null;
        }

        $bookingAndPaymentTypes = [
            NotificationType::BOOKING_CONFIRMED,
            NotificationType::CHECK_IN_REMINDER,
            NotificationType::BOOKING_CANCELLED,
            NotificationType::PAYMENT_RECEIVED,
        ];

        if ($role === UserRole::CUSTOMER->value) {
            return [...$bookingAndPaymentTypes, NotificationType::ISSUE_UPDATED];
        }

        if ($role === UserRole::STAFF->value) {
            $category = $user->staff?->category;
            $category = $category instanceof \BackedEnum ? $category->value : (string) $category;

            return [
                NotificationType::ISSUE_ASSIGNED,
                NotificationType::ISSUE_UPDATED,
                ...($category === Category::RECEPTIONIST->value ? $bookingAndPaymentTypes : []),
                ...($category === Category::HOUSEKEEPER->value ? [NotificationType::HOUSEKEEPING_ASSIGNED] : []),
            ];
        }

        return [];
    }
}
