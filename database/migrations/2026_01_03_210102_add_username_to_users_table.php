<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id')->nullable();
        });

        // Update existing users with username from email
        DB::table('users')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'username' => explode('@', $user->email)[0]
            ]);
        });

        // Make username not nullable after setting values
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
