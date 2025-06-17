<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use ShubhKansara\PhpQuickbooksConnector\Models\QwcFile;

class QwcController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'run_every_n_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $owner_id = '{' . Str::uuid() . '}';
        $file_id = '{' . Str::uuid() . '}';

        // Build AppURL automatically (like your command)
        $app_url = config('php-quickbooks.url', url('qbwc'));

        $xml = view('php-quickbooks::qwc.template', [
            'title' => $validated['title'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'app_url' => $app_url,
            'description' => $validated['description'] ?? 'QuickBooks Web Connector',
            'owner_id' => $owner_id,
            'file_id' => $file_id,
            'run_every_n_minutes' => $validated['run_every_n_minutes'],
        ])->render();

        $filename = Str::slug($validated['title']) . '.qwc';
        Storage::disk('public')->put('qwc/' . $filename, $xml);

        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'run_every_n_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $owner_id = '{' . Str::uuid() . '}';
        $file_id = '{' . Str::uuid() . '}';
        $app_url = config('php-quickbooks.url', url('qbwc'));

        $xml = view('php-quickbooks::qwc.template', [
            'title' => $validated['title'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'app_url' => $app_url,
            'description' => $validated['description'] ?? 'QuickBooks Web Connector',
            'owner_id' => $owner_id,
            'file_id' => $file_id,
            'run_every_n_minutes' => $validated['run_every_n_minutes'],
        ])->render();

        $filename = Str::slug($validated['title']) . '-' . time() . '.qwc';
        $filePath = 'qwc/' . $filename;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, $xml);

        $downloadUrl = url('storage/' . $filePath);

        $qwc = QwcFile::create([
            ...$validated,
            'file_path' => $filePath,
            'download_url' => $downloadUrl,
            'enabled' => true,
        ]);

        return response()->json($qwc);
    }

    public function download($id)
    {
        $qwc = QwcFile::findOrFail($id);
        return response()->download(storage_path('app/public/' . $qwc->file_path), $qwc->title . '.qwc', [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function index()
    {
        return QwcFile::all();
    }

    public function toggle($id)
    {
        $qwc = QwcFile::findOrFail($id);

        // If enabling this QWC, disable all others
        if (! $qwc->enabled) {
            QwcFile::where('id', '!=', $qwc->id)->update(['enabled' => false]);
            $qwc->enabled = true;
        } else {
            $qwc->enabled = false;
        }

        $qwc->save();

        return response()->json($qwc);
    }
}
