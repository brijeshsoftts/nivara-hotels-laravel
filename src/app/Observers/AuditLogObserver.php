<?php

namespace App\Observers;

use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogObserver
{
    private const ENTITY_TYPES = [
        Amenity::class => 'AMENITY',
        Booking::class => 'BOOKING',
        Customer::class => 'CUSTOMER',
        HousekeepingTask::class => 'HOUSEKEEPING_TASK',
        Inquiry::class => 'INQUIRY',
        Invoice::class => 'INVOICE',
        Issue::class => 'ISSUE',
        Payment::class => 'PAYMENT',
        Room::class => 'ROOM',
        RoomType::class => 'ROOM_TYPE',
        Staff::class => 'STAFF',
        User::class => 'USER',
    ];

    private const AUDITED_FIELDS = [
        'action',
        'amount',
        'assigned_to',
        'assigned_to_id',
        'booking_reference',
        'category',
        'check_in_date',
        'check_out_date',
        'discount_amount',
        'id',
        'is_active',
        'name',
        'payment_method',
        'payment_reference',
        'priority',
        'role',
        'room_id',
        'room_type_id',
        'scheduled_date',
        'status',
        'task_type',
        'title',
        'total_amount',
        'transaction_id',
    ];

    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function created(EloquentModel $model): void
    {
        if (! $this->shouldRecord($model)) {
            return;
        }

        $this->record($model, 'CREATE', [], $this->safeValues($model->getAttributes()));
    }

    public function updated(EloquentModel $model): void
    {
        if (! $this->shouldRecord($model)) {
            return;
        }

        $newValues = $this->safeValues($model->getChanges());
        if ($newValues === []) {
            return;
        }

        $oldValues = array_intersect_key($this->safeValues($model->getRawOriginal()), $newValues);
        $this->record($model, 'UPDATE', $oldValues, $newValues);
    }

    public function deleted(EloquentModel $model): void
    {
        if (! $this->shouldRecord($model)) {
            return;
        }

        $this->record($model, 'DELETE', $this->safeValues($model->getAttributes()), []);
    }

    private function shouldRecord(EloquentModel $model): bool
    {
        if (! isset(self::ENTITY_TYPES[$model::class])) {
            return false;
        }

        $request = request();

        return $request->route() !== null
            && in_array($request->method(), self::MUTATION_METHODS, true);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string|int|float|bool|null>
     */
    private function safeValues(array $values): array
    {
        $safeValues = [];

        foreach (self::AUDITED_FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $value = $values[$field];
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            }

            if (is_string($value)) {
                $safeValues[$field] = mb_substr($value, 0, 200);
            } elseif (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $safeValues[$field] = $value;
            }
        }

        return $safeValues;
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $oldValues
     * @param  array<string, string|int|float|bool|null>  $newValues
     */
    private function record(EloquentModel $model, string $action, array $oldValues, array $newValues): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => self::ENTITY_TYPES[$model::class],
                'entity_id' => $model->getKey() === null ? null : (string) $model->getKey(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to persist audit log.', [
                'action' => $action,
                'entity_type' => self::ENTITY_TYPES[$model::class],
                'entity_id' => $model->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
