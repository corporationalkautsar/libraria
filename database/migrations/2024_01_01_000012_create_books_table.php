<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('book_code', 20)->unique();
            $table->string('isbn', 32)->nullable()->unique();
            $table->string('title', 200);
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->foreignId('publisher_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('shelf_id')->constrained()->restrictOnDelete();
            $table->string('shelf_number', 20)->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('stock')->default(1);
            $table->unsignedInteger('available')->default(1);
            $table->text('description')->nullable();
            $table->string('cover', 255)->nullable();
            $table->enum('status', ['tersedia', 'dipinjam', 'stok_habis', 'tidak_aktif'])->default('tersedia');
            $table->timestamps();

            $table->index('title');
            $table->index('status');
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
