<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default super admin user
        $user = User::create([
            User::FIELD_NAME => 'Admin',
            User::FIELD_EMAIL => 'admin@searchtweak.com',
            User::FIELD_PASSWORD => Hash::make('12345678'),
        ]);

        $user->{User::FIELD_SUPER_ADMIN} = true;
        $user->save();

        // Create personal team for the user
        $team = Team::forceCreate([
            'user_id' => $user->id,
            'name' => "Admin's Team",
            'personal_team' => true,
        ]);

        $user->forceFill([
            User::FIELD_CURRENT_TEAM_ID => $team->id,
        ])->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $user = User::where(User::FIELD_EMAIL, 'admin@searchtweak.com')->first();

        if ($user) {
            // Delete personal team
            Team::where('user_id', $user->id)
                ->where('personal_team', true)
                ->delete();

            $user->delete();
        }
    }
};
