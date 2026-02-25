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
        Schema::table('products', function (Blueprint $table) {
            // Add title field (alias for name for public API) - nullable initially
            $table->string('title')->nullable()->after('name');
            
            // Add SKU field - nullable initially
            $table->string('sku', 20)->nullable()->after('title');
            
            // Add cost price field
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            
            // Add weight and dimensions
            $table->string('weight', 20)->nullable()->after('stock');
            $table->string('dimensions', 50)->nullable()->after('weight');
            
            // Add images field (JSON array for admin API)
            $table->json('images')->nullable()->after('image');
            
            // Add active and featured flags
            $table->boolean('active')->default(true)->after('rating');
            $table->boolean('featured')->default(false)->after('active');
            
            // Add status field
            $table->enum('status', ['active', 'out_of_stock', 'low_stock'])->default('active')->after('featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['title', 'sku', 'cost_price', 'weight', 'dimensions', 'images', 'active', 'featured', 'status']);
        });
    }
};
