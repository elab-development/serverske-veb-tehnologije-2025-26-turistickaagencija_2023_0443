<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrangements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('destination');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days');
            $table->text('description')->nullable();
            $table->integer('discount_percent')->default(0);
            $table->boolean('is_last_minute')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrangements');
    }
};
