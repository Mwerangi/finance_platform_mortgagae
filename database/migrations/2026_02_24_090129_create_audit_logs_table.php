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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Institution & User tracking
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable(); // Store name for reference even if user deleted
            $table->string('user_role')->nullable();
            
            // Event information
            $table->string('event_type', 50)->index(); // authentication, authorization, data_modification, etc.
            $table->string('event_category', 50)->index(); // login, application_created, decision_made, etc.
            $table->string('action', 100); // create, update, delete, login, logout, approve, decline, etc.
            $table->text('description'); // Human-readable description
            
            // Entity tracking (what was affected)
            $table->string('entity_type')->nullable()->index(); // Application, Loan, Customer, etc.
            $table->string('entity_id')->nullable()->index(); // ID of affected entity
            
            // Request information
            $table->string('http_method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('request_url')->nullable();
            $table->text('request_body')->nullable(); // JSON encoded
            $table->integer('response_status')->nullable(); // HTTP status code
            
            // Session information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            
            // Change tracking
            $table->json('old_values')->nullable(); // Before changes (JSON)
            $table->json('new_values')->nullable(); // After changes (JSON)
            $table->json('metadata')->nullable(); // Additional context
            
            // Risk & Compliance
            $table->boolean('is_critical')->default(false)->index(); // Flag critical events
            $table->boolean('is_sensitive')->default(false); // Contains sensitive data
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Composite indexes for common queries
            $table->index(['institution_id', 'event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
