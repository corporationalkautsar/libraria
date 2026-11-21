<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = User::where('role', 'petugas')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $sortable = [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
            'is_active' => 'is_active',
        ];
        $requestedSort = (string) $request->get('sort', '');
        $sort = array_key_exists($requestedSort, $sortable) ? $requestedSort : 'name';
        $direction = $requestedSort !== ''
            ? ($request->get('direction') === 'asc' ? 'asc' : 'desc')
            : 'asc';
        $query->orderBy($sortable[$sort], $direction);

        $accounts = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => User::where('role', 'petugas')->count(),
            'aktif' => User::where('role', 'petugas')->where('is_active', true)->count(),
            'nonaktif' => User::where('role', 'petugas')->where('is_active', false)->count(),
        ];

        return view('accounts.index', compact('accounts', 'stats', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Nama petugas wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Role selalu dipaksa menjadi petugas; admin tidak bisa dibuat dari sini.
        $account = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'petugas',
            'is_active' => true,
        ]);

        ActivityLogger::log(
            'Akun Petugas Dibuat',
            'Admin menambahkan akun petugas '.$account->name.' ('.$account->email.').'
        );

        return redirect()->route('accounts.index')->with('success', 'Akun petugas berhasil ditambahkan.');
    }

    public function edit(string $account)
    {
        $account = $this->petugasOrFail($account);

        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, string $account)
    {
        $account = $this->petugasOrFail($account);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$account->id],
            'password' => ['nullable', 'min:6', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama petugas wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => (bool) $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        // Role sengaja tidak pernah dimasukkan ke payload.
        $account->update($payload);

        ActivityLogger::log(
            'Akun Petugas Diperbarui',
            'Admin memperbarui akun petugas '.$account->name.'. Status: '.($account->is_active ? 'aktif' : 'nonaktif').'.'
        );

        return redirect()->route('accounts.index')->with('success', 'Akun petugas berhasil diperbarui.');
    }

    public function destroy(string $account)
    {
        $account = $this->petugasOrFail($account);

        if ((int) $account->id === (int) auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($account->loans()->exists() || $account->returns()->exists() || $account->finePayments()->exists()) {
            return back()->with('error', 'Akun tidak dapat dihapus karena memiliki riwayat transaksi. Silakan nonaktifkan akun tersebut.');
        }

        $name = $account->name;
        $account->delete();

        ActivityLogger::log('Akun Petugas Dihapus', 'Admin menghapus akun petugas '.$name.'.');

        return redirect()->route('accounts.index')->with('success', 'Akun petugas berhasil dihapus.');
    }

    public function toggle(string $account)
    {
        $account = $this->petugasOrFail($account);

        if ((int) $account->id === (int) auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $account->is_active = ! $account->is_active;
        $account->save();

        ActivityLogger::log(
            'Status Akun Diubah',
            'Admin '.($account->is_active ? 'mengaktifkan' : 'menonaktifkan').' akun petugas '.$account->name.'.'
        );

        return back()->with('success', 'Status akun '.$account->name.' berhasil diubah.');
    }

    private function petugasOrFail(mixed $id): User
    {
        return User::where('role', 'petugas')->whereKey($id)->firstOrFail();
    }
}
