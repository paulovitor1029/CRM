<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentAutosaveRequest;
use App\Http\Requests\DocumentStoreRequest;
use App\Http\Requests\DocumentUpdateRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentController
{
    public function __construct(private readonly DocumentService $docs)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $list = Document::where('organization_id', $tenant)->orderByDesc('updated_at')->paginate(20);
        return response()->json(['data' => $list->items(), 'meta' => ['current_page' => $list->currentPage()]]);
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $data['owner_id'] = optional($request->user())->id;
        $doc = Document::create($data);
        if (!empty($data['content'])) {
            $doc = $this->docs->autosave($doc, $data['content'], $data['owner_id']);
        }
        return response()->json(['data' => $doc], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        return response()->json(['data' => $doc]);
    }

    public function update(string $id, DocumentUpdateRequest $request): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $data = $request->validated();
        if (array_key_exists('content', $data)) {
            $doc = $this->docs->autosave($doc, (string) $data['content'], optional($request->user())->id);
            unset($data['content']);
        }
        if (!empty($data)) {
            $doc->fill($data)->save();
        }
        return response()->json(['data' => $doc]);
    }

    public function destroy(string $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $doc->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function autosave(string $id, DocumentAutosaveRequest $request): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $doc = $this->docs->autosave($doc, (string) $request->validated('content'), optional($request->user())->id);
        return response()->json(['data' => $doc]);
    }

    public function versions(string $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $versions = DocumentVersion::where('document_id', $doc->id)->orderByDesc('version')->get(['id','version','created_at','created_by']);
        return response()->json(['data' => $versions]);
    }

    public function rollback(string $id, string $version): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $doc = $this->docs->rollback($doc, (int) $version);
        return response()->json(['data' => $doc]);
    }
}
