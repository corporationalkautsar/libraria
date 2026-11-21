<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_code', 20)->unique();
            $table->string('full_name', 150);
            $table->string('school', 150);
            $table->string('origin', 100);
            $table->timestamps();

            $table->index('full_name');
            $table->index('school');
            $table->index('origin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
