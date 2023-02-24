<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('forenames');
            $table->string('surname');
            $table->boolean('is_staff')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->boolean('wants_phd_emails')->default(false);
            $table->boolean('wants_postgrad_project_emails')->default(false);
            $table->string('email')->unique();
            $table->string('password');
            $table->datetime('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_silenced')->default(false);
            $table->text('silenced_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
