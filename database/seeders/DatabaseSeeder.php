<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\User;
use App\Customer;
use App\Driver;
use App\VehicleType;
use App\DriverVehicle;
use App\City;
use App\PricingZone;
use App\PricingTier;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Vehicle Types
        $vehicleTypes = [
            ['name' => 'Motorcycle', 'weight_min' => 0.1, 'weight_max' => 50, 'pricing_multiplier' => 1.0],
            ['name' => 'Small Van', 'weight_min' => 50, 'weight_max' => 500, 'pricing_multiplier' => 1.5],
            ['name' => 'Medium Truck', 'weight_min' => 500, 'weight_max' => 2000, 'pricing_multiplier' => 2.0],
            ['name' => 'Large Truck', 'weight_min' => 2000, 'weight_max' => 10000, 'pricing_multiplier' => 3.0],
        ];

        foreach ($vehicleTypes as $type) {
            VehicleType::create($type);
        }

        // Create Cities
        $cities = [
            ['name' => 'Dubai', 'lat' => 25.2048, 'lng' => 55.2708],
            ['name' => 'Abu Dhabi', 'lat' => 24.4539, 'lng' => 54.3773],
            ['name' => 'Sharjah', 'lat' => 25.3463, 'lng' => 55.4209],
        ];

        foreach ($cities as $cityData) {
            $city = City::create($cityData);

            // Create Internal Pricing Zone
            $internalZone = PricingZone::create([
                'city_id' => $city->id,
                'zone_type' => PricingZone::TYPE_INTERNAL,
                'name' => $city->name . ' Internal'
            ]);

            // Create External Pricing Zone
            $externalZone = PricingZone::create([
                'city_id' => $city->id,
                'zone_type' => PricingZone::TYPE_EXTERNAL,
                'name' => $city->name . ' External'
            ]);

            // Create pricing tiers for internal zone
            $internalTiers = [
                ['min_km' => 0, 'max_km' => 5, 'base_price' => 25.00],
                ['min_km' => 5, 'max_km' => 15, 'base_price' => 35.00],
                ['min_km' => 15, 'max_km' => 30, 'base_price' => 50.00],
            ];

            foreach ($internalTiers as $tier) {
                PricingTier::create(array_merge($tier, ['zone_id' => $internalZone->id]));
            }

            // Create pricing tiers for external zone
            $externalTiers = [
                ['min_km' => 0, 'max_km' => 50, 'base_price' => 75.00],
                ['min_km' => 50, 'max_km' => 100, 'base_price' => 125.00],
                ['min_km' => 100, 'max_km' => 200, 'base_price' => 200.00],
            ];

            foreach ($externalTiers as $tier) {
                PricingTier::create(array_merge($tier, ['zone_id' => $externalZone->id]));
            }
        }

        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'phone' => '+971501234567',
            'email' => 'admin@trix.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Create Sample Customer
        $customerUser = User::create([
            'name' => 'John Customer',
            'phone' => '+971507654321',
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        Customer::create(['user_id' => $customerUser->id]);

        // Create Sample Drivers
        $drivers = [
            ['name' => 'Ahmed Driver', 'phone' => '+971509876543', 'email' => 'driver1@example.com'],
            ['name' => 'Mohammad Driver', 'phone' => '+971508765432', 'email' => 'driver2@example.com'],
            ['name' => 'Ali Driver', 'phone' => '+971507111222', 'email' => 'driver3@example.com'],
        ];

        foreach ($drivers as $index => $driverData) {
            $driverUser = User::create([
                'name' => $driverData['name'],
                'phone' => $driverData['phone'],
                'email' => $driverData['email'],
                'password' => Hash::make('password123'),
                'role' => User::ROLE_DRIVER,
            ]);

            $driver = Driver::create([
                'user_id' => $driverUser->id,
                'is_verified' => true,
                'status' => Driver::STATUS_AVAILABLE,
            ]);

            // Assign vehicles to drivers
            $vehicleTypeId = ($index % 3) + 1; // Cycle through first 3 vehicle types
            DriverVehicle::create([
                'driver_id' => $driver->id,
                'vehicle_type_id' => $vehicleTypeId,
                'plate_number' => 'DXB-' . (1000 + $index),
                'max_weight' => VehicleType::find($vehicleTypeId)->weight_max,
            ]);
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@trix.com / password123');
        $this->command->info('Customer: customer@example.com / password123');
        $this->command->info('Drivers: driver1@example.com, driver2@example.com, driver3@example.com / password123');
    }
}
