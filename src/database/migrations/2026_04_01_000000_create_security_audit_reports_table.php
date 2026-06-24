<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audit_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('hostname')->nullable();
            $table->string('php_version')->nullable();
            $table->string('php_branch')->nullable();
            $table->integer('risk_score')->default(0);
            $table->json('summary_checks')->nullable();
            $table->json('version_profile')->nullable();
            $table->json('paths')->nullable();
            $table->json('outside_docroot')->nullable();
            $table->json('dangerous_functions')->nullable();
            $table->json('sensitive_files')->nullable();
            $table->json('upload_risks')->nullable();
            $table->json('headers')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('audited_at');
            $table->timestamps();

            $table->index('audited_at');
            $table->index('risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_reports');
    }
};
