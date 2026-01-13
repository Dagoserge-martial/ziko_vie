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
        Schema::table('depenses_medicales', function (Blueprint $table) {
            $table->string('nom_delegue')->nullable()->after('nom_prestataire');
            $table->decimal('montant_transport', 10, 2)->nullable()->after('nom_delegue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depenses_medicales', function (Blueprint $table) {
            $table->dropColumn(['nom_delegue', 'montant_transport']);
        });
    }
};
