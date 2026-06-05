<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('ai-security-guardian.database.connection');

        Schema::connection($connection)->table('security_findings', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('security_findings', 'scanner_name')) {
                $table->string('scanner_name')->nullable()->after('scan_id');
            }

            if (!Schema::connection($connection)->hasColumn('security_findings', 'business_impact')) {
                $table->text('business_impact')->nullable()->after('description');
            }

            if (!Schema::connection($connection)->hasColumn('security_findings', 'technical_impact')) {
                $table->text('technical_impact')->nullable()->after('business_impact');
            }

            if (!Schema::connection($connection)->hasColumn('security_findings', 'test_plan')) {
                $table->text('test_plan')->nullable()->after('recommendation');
            }

            if (!Schema::connection($connection)->hasColumn('security_findings', 'references')) {
                $table->json('references')->nullable()->after('test_plan');
            }
        });
    }

    public function down(): void
    {
        $connection = config('ai-security-guardian.database.connection');

        Schema::connection($connection)->table('security_findings', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('security_findings', 'references')) {
                $table->dropColumn('references');
            }

            if (Schema::connection($connection)->hasColumn('security_findings', 'test_plan')) {
                $table->dropColumn('test_plan');
            }

            if (Schema::connection($connection)->hasColumn('security_findings', 'technical_impact')) {
                $table->dropColumn('technical_impact');
            }

            if (Schema::connection($connection)->hasColumn('security_findings', 'business_impact')) {
                $table->dropColumn('business_impact');
            }

            if (Schema::connection($connection)->hasColumn('security_findings', 'scanner_name')) {
                $table->dropColumn('scanner_name');
            }
        });
    }
};
