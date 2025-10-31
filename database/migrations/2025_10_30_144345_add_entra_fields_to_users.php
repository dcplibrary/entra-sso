<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('entra_id')->nullable()->unique()->after('email');
            $table->string('role')->default('user')->after('entra_id');
            $table->json('entra_groups')->nullable()->after('role');
            $table->json('entra_custom_claims')->nullable()->after('entra_groups');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['entra_id', 'role', 'entra_groups', 'entra_custom_claims']);
        });
    }
};
