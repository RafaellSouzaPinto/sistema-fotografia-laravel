<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabalho_cliente', function (Blueprint $table) {
            $table->dateTime('visualizado_em')->nullable()->after('status_link');
            $table->unsignedInteger('total_visualizacoes')->default(0)->after('visualizado_em');
        });
    }

    public function down(): void
    {
        Schema::table('trabalho_cliente', function (Blueprint $table) {
            $table->dropColumn(['visualizado_em', 'total_visualizacoes']);
        });
    }
};
