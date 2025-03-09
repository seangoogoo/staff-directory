<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip creating the staff table if it already exists
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                $table->string('firstName', 100);
                $table->string('lastName', 100);
                $table->string('department', 100);
                $table->string('jobTitle', 100);
                $table->string('profilePicture', 255)->default('');
                $table->timestamp('createdAt')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
