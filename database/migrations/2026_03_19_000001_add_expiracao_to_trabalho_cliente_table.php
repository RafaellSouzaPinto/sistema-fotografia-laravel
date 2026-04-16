<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabalho_cliente', function (Blueprint $table) {
            $table->dateTime('expira_em')->nullable()->after('token');
            $table->enum('status_link', ['disponivel', 'expirado'])->default('disponivel')->after('expira_em');
        });
    }

    public function down(): void
    {
        Schema::table('trabalho_cliente', function (Blueprint $table) {
            $table->dropColumn(['expira_em', 'status_link']);
        });
    }
};
