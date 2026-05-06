<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Create the new roles
        $canvasser = Role::create([
            'name' => 'Canvassing/Purchasing Officer',
            'slug' => 'canvasser',
            'description' => 'Handles canvass records, supplier directory, and purchase evidence uploads.',
        ]);

        $caretaker = Role::create([
            'name' => 'Caretaker/Association Officer',
            'slug' => 'caretaker',
            'description' => 'Manages assigned cycles, health updates, sick and deceased pig reports.',
        ]);

        // Reassign existing users with the old 'officer' role to 'caretaker'
        $officerRole = Role::where('slug', 'officer')->first();

        if ($officerRole) {
            \App\Models\User::where('role_id', $officerRole->id)
                ->update(['role_id' => $caretaker->id]);

            $officerRole->delete();
        }
    }

    public function down(): void
    {
        $caretakerRole = Role::where('slug', 'caretaker')->first();
        $memberRole = Role::where('slug', 'member')->first();

        if ($caretakerRole) {
            // Reassign caretaker users to member role (do not recreate officer)
            $targetId = $memberRole?->id ?? null;

            if ($targetId) {
                \App\Models\User::where('role_id', $caretakerRole->id)
                    ->update(['role_id' => $targetId]);
            }
        }

        Role::where('slug', 'canvasser')->delete();
        Role::where('slug', 'caretaker')->delete();
    }
};
