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
        Schema::create('homeowners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('first_name')->nullable();
            $table->string('initial', 1)->nullable();
            $table->string('last_name');
            $table->string('full_name')->nullable(); // Computed field for easier searching
            $table->timestamps();

            $table->index('last_name');
            $table->index('full_name');
            $table->index(['title', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homeowners');
    }
};
