<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        Device::updateOrCreate(['code' => 'MQ3-001']);
        Device::updateOrCreate(['code' => 'MQ3-002']);
    }
}
