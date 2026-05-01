<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id')->index();
            $table->enum('sender_role', ['customer', 'vendor', 'workation_admin']);
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->string('sender_display_name', 120)->default('');
            $table->text('message_text');
            $table->boolean('is_flagged')->default(false);
            $table->string('flagged_reason', 500)->nullable();
            $table->string('flagged_pattern', 200)->nullable();
            $table->boolean('vendor_read')->default(false);
            $table->boolean('customer_read')->default(false);
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_messages');
    }
};
