<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('photo_url')->nullable();
            $table->string('phone')->nullable();
            $table->double('current_lat')->nullable();
            $table->double('current_lng')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
        });

        // Update status enum values using raw SQL for MySQL
        // 'pending', 'confirmed', 'processing', 'on_delivery', 'completed', 'cancelled'
        \DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'on_delivery', 'completed', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        // Revert status enum to original values (assuming strictly reversing)
        // Original: 'pending', 'processing', 'completed', 'cancelled'
        // Note: data might be lost/truncated if status was one of the new ones.
        // For safety, we might keep it or try to revert.
        \DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending'");

        Schema::dropIfExists('drivers');
    }
};
