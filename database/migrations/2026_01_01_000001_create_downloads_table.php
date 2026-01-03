<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->string('filename')->nullable();
            $table->text('file_path')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->enum('status', ['queued', 'downloading', 'completed', 'failed'])->default('queued');
            $table->integer('progress')->default(0);
            $table->bigInteger('downloaded_bytes')->default(0);
            $table->bigInteger('total_bytes')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
