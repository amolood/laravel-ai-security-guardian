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
        $connection = config('ai-security-guardian.database.connection');

        Schema::connection($connection)->create('security_scans', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('running');
            $table->integer('risk_score')->default(0);
            $table->json('summary')->nullable();
            $table->string('provider');
            $table->string('model');
            $table->timestamps();
        });

        Schema::connection($connection)->create('security_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('security_scans')->cascadeOnDelete();
            $table->string('severity');
            $table->string('category');
            $table->string('package_name')->nullable();
            $table->string('cve')->nullable();
            $table->string('advisory_url')->nullable();
            $table->string('affected_file')->nullable();
            $table->integer('affected_line')->nullable();
            $table->string('title');
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->string('status')->default('open');
            $table->boolean('safe_auto_fix_allowed')->default(false);
            $table->boolean('human_review_required')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('security_patches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('security_findings')->cascadeOnDelete();
            $table->string('branch_name')->nullable();
            $table->string('pull_request_url')->nullable();
            $table->text('patch_file')->nullable();
            $table->string('original_file_path')->nullable();
            $table->string('backup_path')->nullable();
            $table->string('tests_status')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('ai-security-guardian.database.connection');

        Schema::connection($connection)->dropIfExists('security_patches');
        Schema::connection($connection)->dropIfExists('security_findings');
        Schema::connection($connection)->dropIfExists('security_scans');
    }
};
