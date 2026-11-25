<?php

namespace Database\Seeders;

use App\Enums\AmenityCategory;
use App\Enums\BedType;
use App\Enums\BookingStatus;
use App\Enums\Category;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\HousekeeperRoomAssignment;
use App\Models\HousekeepingTask;
use App\Models\Image;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('admin123');

        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Officer',
                'full_name' => 'General Administration',
                'phone' => '+91 98765 43210',
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => UserRole::ADMIN->value,
                'is_active' => true,
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@nivarahotels.com'],
            [
                'name' => 'Operations Manager',
                'full_name' => 'Hotel Operations Manager',
                'phone' => '+91 98765 43211',
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => UserRole::MANAGER->value,
                'is_active' => true,
            ]
        );

        $receptionistUser = User::firstOrCreate(
            ['email' => 'receptionist@nivarahotels.com'],
            [
                'name' => 'Front Desk Executive',
                'full_name' => 'Reception Team',
                'phone' => '+91 98765 43212',
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => UserRole::STAFF->value,
                'is_active' => true,
            ]
        );

        $housekeeperUser = User::firstOrCreate(
            ['email' => 'housekeeper@nivarahotels.com'],
            [
                'name' => 'Housekeeping Staff',
                'full_name' => 'Lead Housekeeper',
                'phone' => '+91 98765 43213',
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => UserRole::STAFF->value,
                'is_active' => true,
            ]
        );

        $customerUser = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Saim Khan',
                'full_name' => 'Saim Khan',
                'phone' => '+91 98765 00001',
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role' => UserRole::CUSTOMER->value,
                'is_active' => true,
            ]
        );

        // 2. Staff Profiles
        Staff::firstOrCreate(
            ['user_id' => $receptionistUser->id],
            [
                'father_name' => 'Ramesh Sharma',
                'mother_name' => 'Sunita Sharma',
                'id_proof_number' => 'AADHAAR-8901-2345',
                'qualification' => 'Hotel Management Degree',
                'experience' => '4 years in Front Desk Operations',
                'category' => Category::RECEPTIONIST->value,
                'emergency_contact' => '+91 98765 99991',
                'address' => 'Gomti Nagar, Lucknow, UP',
            ]
        );

        Staff::firstOrCreate(
            ['user_id' => $housekeeperUser->id],
            [
                'father_name' => 'Mahesh Verma',
                'mother_name' => 'Kusum Verma',
                'id_proof_number' => 'AADHAAR-7812-4567',
                'qualification' => 'Higher Secondary',
                'experience' => '3 years in Hospitality Cleaning',
                'category' => Category::HOUSEKEEPER->value,
                'emergency_contact' => '+91 98765 99992',
                'address' => 'Alambagh, Lucknow, UP',
            ]
        );

        Customer::firstOrCreate(
            ['user_id' => $customerUser->id],
            [
                'id_proof_number' => 'PASSPORT-Z9876543',
                'address' => 'Hazratganj, Lucknow, UP',
            ]
        );

        // 3. Amenities
        $amenityData = [
            ['name' => 'High-speed Wi-Fi', 'icon' => 'wifi', 'category' => AmenityCategory::CONNECTIVITY->value, 'description' => 'Fast fiber optic wireless internet throughout property.'],
            ['name' => 'Air Conditioning', 'icon' => 'air-vent', 'category' => AmenityCategory::COMFORT->value, 'description' => 'Individual climate control in every room.'],
            ['name' => '24-hour Front Desk', 'icon' => 'concierge-bell', 'category' => AmenityCategory::OTHER->value, 'description' => 'Round-the-clock concierge and assistance.'],
            ['name' => 'Daily Housekeeping', 'icon' => 'sparkles', 'category' => AmenityCategory::ROOM->value, 'description' => 'Daily room cleaning and linen change.'],
            ['name' => 'Rain Shower', 'icon' => 'shower-head', 'category' => AmenityCategory::BATHROOM->value, 'description' => 'High pressure hot and cold rain shower.'],
            ['name' => 'In-room Safe', 'icon' => 'shield-check', 'category' => AmenityCategory::SAFETY->value, 'description' => 'Digital keypad safe for valuables.'],
            ['name' => 'Guarded Parking', 'icon' => 'car-front', 'category' => AmenityCategory::OTHER->value, 'description' => 'Valet and private basement parking.'],
            ['name' => 'Power Backup', 'icon' => 'zap', 'category' => AmenityCategory::COMFORT->value, 'description' => '100% generator backup for uninterrupted power.'],
            ['name' => 'Smart TV', 'icon' => 'tv', 'category' => AmenityCategory::ENTERTAINMENT->value, 'description' => '55-inch 4K Smart TV with streaming apps.'],
            ['name' => 'Tea & Coffee Maker', 'icon' => 'coffee', 'category' => AmenityCategory::FOOD->value, 'description' => 'Electric kettle with assorted fine teas and coffees.'],
        ];

        $amenities = [];
        foreach ($amenityData as $item) {
            $amenities[] = Amenity::firstOrCreate(['name' => $item['name']], $item);
        }

        // 4. Room Types
        $roomTypeData = [
            [
                'name' => 'Deluxe Room',
                'slug' => 'deluxe-room',
                'description' => 'A comfortable room with a king bed, work desk, air conditioning and attached bathroom.',
                'size_sq_ft' => 320,
                'max_guests' => 3,
                'adults' => 2,
                'children' => 1,
                'base_price' => 6200,
                'currency' => 'INR',
                'bed_type' => BedType::KING->value,
                'bed_count' => 1,
                'smoking_allowed' => false,
                'pets_allowed' => false,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Premium Room',
                'slug' => 'premium-room',
                'description' => 'A well-appointed room with twin beds, a tea and coffee maker and dedicated workspace.',
                'size_sq_ft' => 360,
                'max_guests' => 3,
                'adults' => 2,
                'children' => 1,
                'base_price' => 7800,
                'currency' => 'INR',
                'bed_type' => BedType::TWIN->value,
                'bed_count' => 2,
                'smoking_allowed' => false,
                'pets_allowed' => false,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Executive Room',
                'slug' => 'executive-room',
                'description' => 'An executive room with a king bed, work desk, smart television, and luxury amenities.',
                'size_sq_ft' => 420,
                'max_guests' => 3,
                'adults' => 2,
                'children' => 1,
                'base_price' => 8500,
                'currency' => 'INR',
                'bed_type' => BedType::KING->value,
                'bed_count' => 1,
                'smoking_allowed' => false,
                'pets_allowed' => false,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Luxury Suite',
                'slug' => 'luxury-suite',
                'description' => 'A spacious suite with a separate sitting lounge, king bed, and panoramic city vistas.',
                'size_sq_ft' => 620,
                'max_guests' => 4,
                'adults' => 3,
                'children' => 1,
                'base_price' => 14500,
                'currency' => 'INR',
                'bed_type' => BedType::KING->value,
                'bed_count' => 1,
                'smoking_allowed' => false,
                'pets_allowed' => true,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Family Suite',
                'slug' => 'family-suite',
                'description' => 'A generous two-bedroom suite tailored for family getaways with separate living area.',
                'size_sq_ft' => 540,
                'max_guests' => 5,
                'adults' => 4,
                'children' => 2,
                'base_price' => 12000,
                'currency' => 'INR',
                'bed_type' => BedType::QUEEN->value,
                'bed_count' => 2,
                'smoking_allowed' => false,
                'pets_allowed' => false,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1200&q=80',
            ],
        ];

        foreach ($roomTypeData as $rt) {
            $imageUrl = $rt['image_url'];
            unset($rt['image_url']);

            $roomType = RoomType::firstOrCreate(['slug' => $rt['slug']], $rt);
            $roomType->amenities()->sync(array_slice(array_column($amenities, 'id'), 0, 6));

            Image::firstOrCreate(
                ['url' => $imageUrl],
                [
                    'alt_text' => $roomType->name . ' view',
                    'is_primary' => true,
                    'sort_order' => 1,
                    'room_type_id' => $roomType->id,
                ]
            );

            // 5. Physical Rooms
            for ($floor = 1; $floor <= 3; $floor++) {
                $roomNum = sprintf('%d0%d', $floor, match ($rt['slug']) {
                    'deluxe-room' => 1,
                    'premium-room' => 2,
                    'executive-room' => 3,
                    'luxury-suite' => 4,
                    'family-suite' => 5,
                    default => 6,
                });

                Room::firstOrCreate(
                    ['room_number' => $roomNum],
                    [
                        'name' => "Room {$roomNum}",
                        'room_type_id' => $roomType->id,
                        'floor' => $floor,
                        'description' => "Floor {$floor} {$roomType->name}",
                        'occupancy_status' => OccupancyStatus::VACANT->value,
                        'housekeeping_status' => HousekeepingStatus::CLEAN->value,
                        'is_active' => true,
                    ]
                );
            }
        }

        // 6. Inquiries
        Inquiry::firstOrCreate(
            ['email' => 'priya.s@example.com'],
            [
                'full_name' => 'Priya Srivastava',
                'phone' => '+91 98765 11111',
                'subject' => 'Corporate Retreat Booking for 20 Rooms',
                'message' => 'Hello team, we are planning a 3-night corporate retreat in January and would like to request package pricing including dinner and conference space.',
            ]
        );

        // 7. Housekeeping Tasks
        $firstRoom = Room::first();
        if ($firstRoom) {
            HousekeepingTask::firstOrCreate(
                ['room_id' => $firstRoom->id, 'task_type' => TaskType::CLEANING->value],
                [
                    'status' => TaskStatus::PENDING->value,
                    'scheduled_date' => Carbon::today(),
                    'assigned_to' => $housekeeperUser->id,
                ]
            );
        }
    }
}
