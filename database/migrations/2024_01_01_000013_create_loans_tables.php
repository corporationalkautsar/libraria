<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 30)->unique();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('loan_date');
            $table->date('due_date');
            $table->enum('status', ['dipinjam', 'terlambat', 'selesai', 'dibatalkan'])->default('dipinjam');
            $table->unsignedInteger('total_books')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('loan_date');
            $table->index('due_date');
            $table->index('status');
        });

        Schema::create('loan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->enum('status', ['dipinjam', 'terlambat', 'dikembalikan'])->default('dipinjam');
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('returned_at');
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('return_date');
            $table->unsignedInteger('total_books')->default(0);
            $table->decimal('total_fine', 12, 2)->default(0);
            $table->timestamps();

            $table->index('return_date');
        });

        Schema::create('return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->unsignedInteger('late_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_details');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('loan_details');
        Schema::dropIfExists('loans');
    }
};
