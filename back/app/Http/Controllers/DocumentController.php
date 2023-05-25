<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;

class DocumentController extends Controller{
    public function index(){ return Document::all(); }
    public function show(Document $document){ return $document; }
    public function store(StoreDocumentRequest $request){ return Document::create($request->all()); }
    public function update(UpdateDocumentRequest $request, Document $document){ return $document->update($request->all()); }
    public function destroy(Document $document){ return $document->delete(); }
}
