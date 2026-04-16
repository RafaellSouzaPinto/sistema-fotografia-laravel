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
        DB::statement("ALTER TABLE trabalhos MODIFY COLUMN status ENUM('rascunho', 'publicado', 'finalizado') NOT NULL DEFAULT 'rascunho'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trabalhos MODIFY COLUMN status ENUM('rascunho', 'publicado') NOT NULL DEFAULT 'rascunho'");
    }
};
