<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', ['settings' => Settings::all()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'library_name' => ['required', 'string', 'max:150'],
            'library_address' => ['required', 'string', 'max:255'],
            'library_info' => ['nullable', 'string', 'max:1000'],
            'max_books' => ['required', 'integer', 'min:1', 'max:20'],
            'loan_days' => ['required', 'integer', 'min:1', 'max:90'],
            'fine_per_day' => ['required', 'numeric', 'min:0'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'library_name.required' => 'Nama perpustakaan wajib diisi.',
            'library_address.required' => 'Alamat perpustakaan wajib diisi.',
            'max_books.min' => 'Maksimal buku minimal 1.',
            'max_books.max' => 'Maksimal buku tidak boleh lebih dari 20.',
            'loan_days.min' => 'Lama peminjaman minimal 1 hari.',
            'loan_days.max' => 'Lama peminjaman tidak boleh lebih dari 90 hari.',
            'fine_per_day.min' => 'Denda per hari tidak boleh negatif.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ]);

        $oldLogo = Settings::get('library_logo');
        $values = [
            'library_name' => $data['library_name'],
            'library_address' => $data['library_address'],
            'library_info' => $data['library_info'] ?? '',
            'max_books' => (string) $data['max_books'],
            'loan_days' => (string) $data['loan_days'],
            'fine_per_day' => (string) $data['fine_per_day'],
        ];

        if ($request->hasFile('logo')) {
            Storage::disk('public')->makeDirectory('logo');
            $values['library_logo'] = $request->file('logo')->store('logo', 'public');
        }

        Settings::setMany($values);
        Settings::flush();

        if (! empty($values['library_logo']) && ! empty($oldLogo)
            && $oldLogo !== $values['library_logo']
            && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        ActivityLogger::log('Pengaturan Diperbarui', 'Admin memperbarui pengaturan identitas dan aturan peminjaman perpustakaan.');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
