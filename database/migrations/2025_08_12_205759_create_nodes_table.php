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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('nodes')->nullableOnDelete();
            $table->enum('type', ['Corporation', 'Building', 'Property', 'Tenancy Period', 'Tenant']);
            $table->unsignedInteger('height')->default(0);

            $table->string('zip_code')->nullable(); //For Buildings
            $table->decimal('monthly_rent', 10, 2)->nullable(); //For Property
            $table->boolean('active')->nullable(); //For Tenancy periods
            $table->date('move_in_date')->nullable(); //For Tenants


            $table->timestamps();

            $table->index('parent_id');
            $table->index(['type', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
