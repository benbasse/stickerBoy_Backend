<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'maintenance_enabled' => '0',
            'maintenance_title' => 'Coming Soon',
            'maintenance_message' => 'We are working on something amazing...',
            'maintenance_target_date' => null,
            'maintenance_show_countdown' => '1',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
