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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique(); // Short code (e.g., 'INST001')
            $table->text('description')->nullable();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Tanzania');
            
            // Configuration
            $table->string('timezone')->default('Africa/Dar_es_Salaam');
            $table->string('currency')->default('TZS');
            $table->string('date_format')->default('Y-m-d');
            
            // Branding Configuration (JSON)
            $table->json('branding')->nullable();
            // Structure: {
            //   "logo_url": "path/to/logo.png",
            //   "favicon_url": "path/to/favicon.ico",
            //   "primary_color": "#1E40AF",
            //   "secondary_color": "#64748B",
            //   "accent_color": "#10B981",
            //   "custom_domain": "example.com",
            //   "email_from_name": "Institution Name",
            //   "email_from_address": "noreply@example.com"
            // }
            
            // Settings (JSON)
            $table->json('settings')->nullable();
            // Structure: {
            //   "features": ["analytics", "collections"],
            //   "loan_account_prefix": "LN",
            //   "customer_id_prefix": "CUS",
            //   "max_file_size_mb": 50,
            //   "allowed_file_types": ["xlsx", "csv"],
            //   "require_kyc_verification": true,
            //   "auto_run_analytics": true
            // }
            
            // Status
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
