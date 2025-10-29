<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\DatabaseManager;

class DocumentService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function autosave(Document $doc, string $content, ?string $userId = null): Document
    {
        return $this->db->transaction(function () use ($doc, $content, $userId) {
            $nextVersion = (int) $doc->current_version + 1;
            DocumentVersion::create([
                'document_id' => $doc->id,
                'version' => $nextVersion,
                'content' => $content,
                'created_by' => $userId,
            ]);
            $doc->forceFill([
                'content' => $content,
                'current_version' => $nextVersion,
                'autosave_at' => now(),
            ])->save();
            return $doc->fresh();
        });
    }

    public function rollback(Document $doc, int $version): Document
    {
        $ver = DocumentVersion::where('document_id', $doc->id)->where('version', $version)->firstOrFail();
        return $this->db->transaction(function () use ($doc, $ver) {
            $doc->forceFill([
                'content' => $ver->content,
                'current_version' => $ver->version,
                'autosave_at' => now(),
            ])->save();
            return $doc->fresh();
        });
    }
}

