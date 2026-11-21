<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UpdateHistoryController extends Controller
{
    public function index()
    {
        $histories = \App\Models\UpdateHistory::orderBy('tanggal', 'desc')->get();
        return view('admin.update-history.index', compact('histories'));
    }

    public function create()
    {
        // Only super_admin can create, we can check here or use middleware, 
        // since middleware is applied in routes, we assume user is authorized.
        return view('admin.update-history.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'versi' => 'required|string|max:50',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'is_published' => 'boolean',
        ]);

        \App\Models\UpdateHistory::create([
            'versi' => $request->versi,
            'tanggal' => now(),
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'is_published' => $request->has('is_published') ? true : false,
        ]);

        \App\Helpers\MenuHelper::flushCache();

        return redirect()->route('admin.update-history.index')->with('success', 'Update History berhasil ditambahkan');
    }

    public function show($id)
    {
        $history = \App\Models\UpdateHistory::findOrFail($id);
        return view('admin.update-history.show', compact('history'));
    }

    public function edit($id)
    {
        $history = \App\Models\UpdateHistory::findOrFail($id);
        return view('admin.update-history.edit', compact('history'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'versi' => 'required|string|max:50',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $history = \App\Models\UpdateHistory::findOrFail($id);
        $history->update([
            'versi' => $request->versi,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'is_published' => $request->has('is_published') ? true : false,
        ]);

        \App\Helpers\MenuHelper::flushCache();

        return redirect()->route('admin.update-history.index')->with('success', 'Update History berhasil diperbarui');
    }

    public function destroy($id)
    {
        $history = \App\Models\UpdateHistory::findOrFail($id);
        $history->delete();

        \App\Helpers\MenuHelper::flushCache();

        return redirect()->route('admin.update-history.index')->with('success', 'Update History berhasil dihapus');
    }
}
