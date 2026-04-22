<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\PdfMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(private PdfMetadataService $pdfService) {}

    public function index(Request $request)
    {
        $query = Document::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('document_title', 'like', "%{$search}%")
                  ->orWhere('document_author', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:51200',
            'uploaded_by' => 'nullable|string|max:100',
        ]);

        $file = $request->file('pdf_file');
        $originalName = $file->getClientOriginalName();
        $storedName = Str::uuid() . '.pdf';
        $path = $file->storeAs('documents', $storedName, 'local');

        $fullPath = Storage::disk('local')->path($path);
        $meta = $this->pdfService->extract($fullPath);

        Document::create([
            'original_filename' => $originalName,
            'stored_filename'   => $storedName,
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'document_title'    => $meta['title'],
            'document_author'   => $meta['author'],
            'document_creator'  => $meta['creator'],
            'document_producer' => $meta['producer'],
            'pdf_created_at'    => $meta['created_at'],
            'pdf_modified_at'   => $meta['modified_at'],
            'uploaded_by'       => $request->input('uploaded_by'),
        ]);

        return redirect()->route('documents.index')->with('success', "File \"{$originalName}\" berhasil diupload.");
    }

    public function show(Document $document)
    {
        return view('documents.show', compact('document'));
    }

    public function download(Document $document)
    {
        $path = Storage::disk('local')->path($document->file_path);
        return response()->download($path, $document->original_filename);
    }

    public function destroy(Document $document)
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil dihapus.');
    }
}
