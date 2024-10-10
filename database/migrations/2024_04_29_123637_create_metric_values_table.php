<?php

use App\Models\MetricValue;
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
        Schema::create('metric_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId(MetricValue::FIELD_EVALUATION_METRIC_ID)->constrained()->cascadeOnDelete();
            $table->float(MetricValue::FIELD_VALUE);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_values');
    }
};
