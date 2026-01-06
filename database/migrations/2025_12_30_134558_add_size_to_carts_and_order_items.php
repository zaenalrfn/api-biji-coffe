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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('size')->default('MD')->after('quantity');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size')->default('MD')->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
