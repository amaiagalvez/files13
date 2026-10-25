<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // A document is a template, or a file document copied from a template or uploaded to a file.
        // The stored file has a generated name; the name the user sees is data only.
        Schema::create('FIL_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('template')->default(false)->index();
            $table->foreignId('parent_id')->nullable()->constrained('FIL_documents')->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('extension', 10)->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            // The key OnlyOffice caches a version of the document under; it changes on every save.
            $table->string('key', 128)->nullable();
            $table->boolean('editing')->default(false);

            addCommonColumns($table);
            addAuditColumns($table);
        });

        Schema::create('FIL_document_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('FIL_documents')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('FIL_subjects')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'subject_id']);
        });

        // Folders are shared by every file: a name is created once and reused.
        Schema::create('FIL_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('FIL_file_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('FIL_files')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('FIL_documents')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('FIL_folders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['file_id', 'document_id']);
        });

        // The tracks outlive their document: deleting it for good keeps them, with its name.
        Schema::create('FIL_document_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('FIL_documents')->nullOnDelete();
            $table->string('document_name');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FIL_document_tracks');
        Schema::dropIfExists('FIL_file_document');
        Schema::dropIfExists('FIL_folders');
        Schema::dropIfExists('FIL_document_subject');
        Schema::dropIfExists('FIL_documents');
    }
};
