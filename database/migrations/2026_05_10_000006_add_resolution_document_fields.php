<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->string('resolution_number')->unique()->nullable()->after('id');
            $table->string('generated_pdf_path')->nullable()->after('resolution_file_path');
            $table->string('generated_docx_path')->nullable()->after('generated_pdf_path');
            $table->string('signed_file_path')->nullable()->after('generated_docx_path');
            $table->string('physical_signatures_pdf_path')->nullable()->after('signed_file_path');
            $table->string('dswd_approval_file_path')->nullable()->after('physical_signatures_pdf_path');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('signature_verified_at')->nullable();
            $table->enum('workflow_status', [
                'draft',
                'generated',
                'printed',
                'signature_sheet_uploaded',
                'pending_member_approval',
                'member_approved',
                'dswd_pending',
                'dswd_approved',
                'withdrawal_ready',
                'withdrawn',
                'archived',
            ])->default('draft');
            $table->timestamp('resolution_number_assigned_at')->nullable();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->enum('document_type', [
                'generated_pdf',
                'generated_docx',
                'signed_resolution',
                'dswd_approval',
                'signature_sheet',
                'supporting_attachment',
            ]);
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['resolution_id', 'document_type']);
        });

        // Assign resolution numbers to existing resolutions
        $resolutions = \App\Models\Resolution::whereNull('resolution_number')
            ->orderBy('id')
            ->get();

        $count = 1;
        foreach ($resolutions as $resolution) {
            $resolution->update([
                'resolution_number' => sprintf('RES-%s-%03d', date('Y'), $count),
                'workflow_status' => 'draft',
            ]);
            $count++;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');

        Schema::table('resolutions', function (Blueprint $table) {
            $table->dropColumn([
                'resolution_number',
                'generated_pdf_path',
                'generated_docx_path',
                'signed_file_path',
                'physical_signatures_pdf_path',
                'dswd_approval_file_path',
                'version',
                'signature_verified_at',
                'workflow_status',
                'resolution_number_assigned_at',
            ]);
        });
    }
};
