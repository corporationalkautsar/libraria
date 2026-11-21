<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rak tidak lagi dipakai di aplikasi. Kolom lama sengaja dibuat nullable
     * agar data lama tetap tersimpan tanpa menghalangi buku baru.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['shelf_id']);
            $table->foreignId('shelf_id')->nullable()->change();
            $table->foreign('shelf_id')->references('id')->on('shelves')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['shelf_id']);
            $table->foreignId('shelf_id')->nullable(false)->change();
            $table->foreign('shelf_id')->references('id')->on('shelves')->restrictOnDelete();
        });
    }
};
