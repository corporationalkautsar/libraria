<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LookupController extends Controller
{
    /** Daftar tipe master data yang diizinkan beserta model dan labelnya. */
    private const TYPES = [
        'categories' => [
            'model' => Category::class,
            'table' => 'categories',
            'title' => 'Kategori',
            'singular' => 'Kategori',
            'label' => 'Nama Kategori',
            'icon' => 'tags',
            'max' => 100,
            'shelves' => false,
        ],
        'authors' => [
            'model' => Author::class,
            'table' => 'authors',
            'title' => 'Penulis',
            'singular' => 'Penulis',
            'label' => 'Nama Penulis',
            'icon' => 'pen-line',
            'max' => 150,
            'shelves' => false,
        ],
        'publishers' => [
            'model' => Publisher::class,
            'table' => 'publishers',
            'title' => 'Penerbit',
            'singular' => 'Penerbit',
            'label' => 'Nama Penerbit',
            'icon' => 'building-2',
            'max' => 150,
            'shelves' => false,
        ],
        'shelves' => [
            'model' => Shelf::class,
            'table' => 'shelves',
            'title' => 'Rak Buku',
            'singular' => 'Rak',
            'label' => 'Nama Rak',
            'icon' => 'archive',
            'max' => 100,
            'shelves' => true,
        ],
    ];

    private function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    public function index(Request $request, string $type)
    {
        $config = $this->config($type);
        $model = $config['model'];

        $query = $model::query();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search, $config) {
                if ($config['shelves']) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                } else {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });
        }

        $records = $query->orderBy($config['shelves'] ? 'code' : 'name')
            ->paginate(15)
            ->withQueryString();

        $counts = [];
        foreach (self::TYPES as $key => $item) {
            $counts[$key] = $item['model']::count();
        }

        return view('lookups.index', [
            'type' => $type,
            'records' => $records,
            'title' => $config['title'],
            'config' => $config,
            'counts' => $counts,
            'search' => $search,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);
        $data = $this->validateData($request, $config);

        $config['model']::create($data);

        ActivityLogger::log(
            'Penambahan '.$config['singular'],
            $config['singular'].' '.$this->recordLabel($data, $config).' ditambahkan.'
        );

        return redirect()->route('lookups.index', $type)
            ->with('success', $config['singular'].' berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, string $lookup)
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($lookup);

        $data = $this->validateData($request, $config, (int) $record->id);
        $record->update($data);

        ActivityLogger::log(
            'Perubahan '.$config['singular'],
            $config['singular'].' '.$this->recordLabel($data, $config).' diperbarui.'
        );

        return redirect()->route('lookups.index', $type)
            ->with('success', $config['singular'].' berhasil diperbarui.');
    }

    public function destroy(string $type, string $lookup)
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($lookup);

        if ($record->books()->exists()) {
            return back()->with('error', $config['singular'].' tidak dapat dihapus karena masih digunakan oleh buku.');
        }

        $label = $config['shelves'] ? $record->code : $record->name;
        $record->delete();

        ActivityLogger::log('Hapus '.$config['singular'], $config['singular'].' '.$label.' dihapus.');

        return redirect()->route('lookups.index', $type)
            ->with('success', $config['singular'].' berhasil dihapus.');
    }

    private function validateData(Request $request, array $config, ?int $ignoreId = null): array
    {
        if ($config['shelves']) {
            $codeRule = Rule::unique('shelves', 'code');
            if ($ignoreId) {
                $codeRule->ignore($ignoreId);
            }

            $rules = [
                'code' => ['required', 'string', 'max:30', $codeRule],
                'name' => ['required', 'string', 'max:'.$config['max']],
                'location' => ['nullable', 'string', 'max:150'],
            ];
        } else {
            $nameRule = Rule::unique($config['table'], 'name');
            if ($ignoreId) {
                $nameRule->ignore($ignoreId);
            }

            $rules = [
                'name' => ['required', 'string', 'max:'.$config['max'], $nameRule],
                'description' => ['nullable', 'string'],
            ];
        }

        return $request->validate($rules, [
            'name.required' => $config['label'].' wajib diisi.',
            'name.unique' => $config['singular'].' dengan nama tersebut sudah ada.',
            'code.required' => 'Kode rak wajib diisi.',
            'code.unique' => 'Kode rak tersebut sudah digunakan.',
        ]);
    }

    private function recordLabel(array $data, array $config): string
    {
        return (string) ($data['name'] ?? $data['code'] ?? '') ?: '-';
    }
}
