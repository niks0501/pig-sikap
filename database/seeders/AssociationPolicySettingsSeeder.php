<?php

namespace Database\Seeders;

use App\Models\AssociationPolicySetting;
use Illuminate\Database\Seeder;

/**
 * Seeds default association policy settings.
 * These are the starting values; the president/system_admin
 * can change them via the Policy Settings page.
 */
class AssociationPolicySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // Meeting settings
            [
                'key' => 'meeting_schedule_day',
                'value' => 'Saturday',
                'description' => 'Regular meeting day of the week',
                'value_type' => 'string',
                'group' => 'meeting',
            ],
            [
                'key' => 'meeting_quorum_percentage',
                'value' => '50',
                'description' => 'Minimum attendance percentage required for quorum',
                'value_type' => 'float',
                'group' => 'meeting',
            ],
            // Attendance penalties
            [
                'key' => 'attendance_penalty_amount',
                'value' => '0',
                'description' => 'Penalty amount for unexcused absence (0 = no penalty)',
                'value_type' => 'float',
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance_consecutive_absent_limit',
                'value' => '3',
                'description' => 'Max consecutive absences before suspension',
                'value_type' => 'integer',
                'group' => 'attendance',
            ],
            // Financial / dividend / rebate
            [
                'key' => 'dividend_rate_percentage',
                'value' => '0',
                'description' => 'Dividend rate as percentage of net profit',
                'value_type' => 'float',
                'group' => 'financial',
            ],
            [
                'key' => 'rebate_rate_percentage',
                'value' => '0',
                'description' => 'Rebate rate as percentage of net profit',
                'value_type' => 'float',
                'group' => 'financial',
            ],
            // Membership / resignation
            [
                'key' => 'resignation_notice_days',
                'value' => '30',
                'description' => 'Required notice period for resignation in days',
                'value_type' => 'integer',
                'group' => 'membership',
            ],
        ];

        foreach ($defaults as $setting) {
            AssociationPolicySetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}