<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletters')) {
            return;
        }

        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('title', 220);
            $table->string('subject', 300);
            // draft | scheduled | sent | archived
            $table->string('status', 32)->default('draft');
            $table->longText('body');
            $table->string('audience', 64)->default('all'); // all | members | partners
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
