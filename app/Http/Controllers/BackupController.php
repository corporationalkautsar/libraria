<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class BackupController extends Controller
{
    private const DUMP_BINARY = 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';
    private const RESTORE_BINARY = 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe';
    private const FOLDER = 'backups';

    public function index()
    {
        $files = glob($this->backupDirectory().DIRECTORY_SEPARATOR.'*.sql') ?: [];

        $backups = collect($files)
            ->map(fn ($file) => [
                'filename' => basename($file),
                'path' => $file,
                'size' => (int) filesize($file),
                'modified' => Carbon::createFromTimestamp((int) filemtime($file)),
            ])
            ->sortByDesc('modified')
            ->values();

        return view('backup.index', compact('backups'));
    }

    public function store()
    {
        $filename = 'backup_'.date('Y-m-d_His').'.sql';
        $absolute = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        try {
            $dumped = false;
            if (is_file(self::DUMP_BINARY)) {
                try {
                    $this->dumpWithBinary($absolute);
                    $dumped = true;
                } catch (Throwable $e) {
                    // Web server bawaan PHP dapat gagal membuka socket untuk mysqldump.
                    // Hapus berkas parsial lalu gunakan dump PDO yang memakai koneksi aktif.
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
            }

            if (! $dumped) {
                $this->dumpWithPhp($absolute);
            }

            if (! is_file($absolute) || filesize($absolute) <= 0) {
                throw new \RuntimeException('Berkas backup tidak berhasil dibuat.');
            }
        } catch (Throwable $e) {
            ActivityLogger::log('Backup Gagal', 'Gagal membuat backup database: '.$e->getMessage());

            return back()->with('error', 'Backup gagal: '.$e->getMessage());
        }

        ActivityLogger::log('Backup Dibuat', 'Admin membuat backup database '.$filename.'.');

        return back()->with('success', 'Backup database berhasil dibuat: '.$filename);
    }

    public function download(string $filename)
    {
        $resolved = $this->resolveBackup($filename);

        if (! $resolved) {
            abort(404, 'Berkas backup tidak ditemukan.');
        }

        [$name, $absolute] = $resolved;

        return response()->download($absolute, $name, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function destroy(string $filename)
    {
        $resolved = $this->resolveBackup($filename);

        if (! $resolved) {
            return back()->with('error', 'Berkas backup tidak ditemukan atau tidak valid.');
        }

        [$name, $absolute] = $resolved;
        unlink($absolute);

        ActivityLogger::log('Backup Dihapus', 'Admin menghapus berkas backup '.$name.'.');

        return back()->with('success', 'Berkas backup '.$name.' berhasil dihapus.');
    }

    public function restore(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'confirmation' => ['required', 'in:RESTORE'],
        ], [
            'file.required' => 'Pilih berkas backup yang akan dipulihkan.',
            'confirmation.required' => 'Konfirmasi pemulihan wajib diisi.',
            'confirmation.in' => 'Ketik RESTORE dengan huruf kapital untuk mengonfirmasi.',
        ]);

        $resolved = $this->resolveBackup($data['file']);

        if (! $resolved) {
            return back()->with('error', 'Berkas backup tidak ditemukan atau tidak valid.');
        }

        [$filename, $absolute] = $resolved;

        try {
            $imported = false;
            if (is_file(self::RESTORE_BINARY)) {
                try {
                    $this->importWithBinary($absolute);
                    $imported = true;
                } catch (Throwable $e) {
                    // Jika klien mysql gagal (misalnya socket diblokir), ulangi dengan PDO.
                    $imported = false;
                }
            }

            if (! $imported) {
                $sql = file_get_contents($absolute);
                if ($sql === false) {
                    throw new \RuntimeException('Berkas backup tidak dapat dibaca.');
                }
                $this->importWithPdo($sql);
            }
        } catch (Throwable $e) {
            ActivityLogger::log('Pemulihan Gagal', 'Gagal memulihkan database dari '.$filename.': '.$e->getMessage());

            return back()->with('error', 'Pemulihan gagal: '.$e->getMessage());
        }

        Settings::flush();
        ActivityLogger::log('Database Dipulihkan', 'Admin memulihkan database dari berkas '.$filename.'.');

        return redirect()->route('backup.index')->with('success', 'Database berhasil dipulihkan dari '.$filename.'.');
    }

    /** Membuat dump dengan mysqldump; Symfony Process mengescaping argumen array. */
    private function dumpWithBinary(string $absolute): void
    {
        $config = config('database.connections.'.config('database.default'));
        $command = [
            self::DUMP_BINARY,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--no-tablespaces',
            '--single-transaction',
            '--routines',
            '--default-character-set=utf8mb4',
            '--result-file='.$absolute,
            $config['database'],
        ];

        // Root Laragon umumnya tanpa password; jangan kirim opsi password kosong.
        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $process = new Process($command, null, null, null, 300);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput().' '.$process->getOutput());

            throw new \RuntimeException($message !== '' ? $message : 'mysqldump mengembalikan kesalahan.');
        }
    }

    /** Cadangan murni PHP bila mysqldump tidak tersedia. */
    private function dumpWithPhp(string $absolute): void
    {
        $pdo = DB::connection()->getPdo();
        $config = config('database.connections.'.config('database.default'));
        $database = (string) ($config['database'] ?? 'libraria');

        $lines = [
            '-- Backup database Libraria (PHP fallback)',
            '-- Dibuat: '.now()->toDateTimeString(),
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";',
            'SET NAMES utf8mb4;',
            'USE `'.str_replace('`', '``', $database).'`;',
        ];

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_NUM);

        foreach ($tables as $row) {
            $table = (string) $row[0];
            $escaped = str_replace('`', '``', $table);
            $create = $pdo->query('SHOW CREATE TABLE `'.$escaped.'`')->fetch(\PDO::FETCH_NUM);

            $lines[] = '';
            $lines[] = '-- Struktur tabel '.$table;
            $lines[] = 'DROP TABLE IF EXISTS `'.$escaped.'`;';
            $lines[] = ($create[1] ?? '').';';

            $rows = $pdo->query('SELECT * FROM `'.$escaped.'`')->fetchAll(\PDO::FETCH_ASSOC);
            if ($rows === []) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $columnList = implode(', ', array_map(fn ($column) => '`'.str_replace('`', '``', (string) $column).'`', $columns));
            $lines[] = '-- Data tabel '.$table;

            foreach (array_chunk($rows, 100) as $chunk) {
                $tuples = [];
                foreach ($chunk as $rowData) {
                    $values = [];
                    foreach ($columns as $column) {
                        $value = $rowData[$column] ?? null;
                        $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
                    }
                    $tuples[] = '('.implode(', ', $values).')';
                }
                $lines[] = 'INSERT INTO `'.$escaped.'` ('.$columnList.') VALUES '.implode(', ', $tuples).';';
            }
        }

        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        if (file_put_contents($absolute, implode("\n", $lines), LOCK_EX) === false) {
            throw new \RuntimeException('Berkas backup tidak dapat ditulis.');
        }
    }

    private function importWithBinary(string $absolute): void
    {
        $config = config('database.connections.'.config('database.default'));
        $command = [
            self::RESTORE_BINARY,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--database='.$config['database'],
            '--default-character-set=utf8mb4',
        ];

        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $handle = fopen($absolute, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Berkas backup tidak dapat dibaca.');
        }

        $process = new Process($command, null, null, null, 600);
        try {
            $process->setInput($handle);
            $process->run();
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput().' '.$process->getOutput());

            throw new \RuntimeException($message !== '' ? $message : 'mysql client mengembalikan kesalahan.');
        }
    }

    private function importWithPdo(string $sql): void
    {
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        foreach ($this->splitSql($sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    /** Memecah SQL dengan mempertimbangkan kutipan, komentar, dan DELIMITER. */
    private function splitSql(string $sql): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        $statements = [];
        $buffer = '';
        $delimiter = ';';
        $quote = null;
        $lineComment = false;
        $blockComment = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($lineComment) {
                $buffer .= $char;
                if ($char === "\n") {
                    $lineComment = false;
                }
                continue;
            }

            if ($blockComment) {
                $buffer .= $char;
                if ($char === '*' && $next === '/') {
                    $buffer .= $next;
                    $i++;
                    $blockComment = false;
                }
                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === '\\' && $quote !== '`' && $next !== '') {
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                if ($char === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $i++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if (($i === 0 || $sql[$i - 1] === "\n")
                && preg_match('/^DELIMITER\s+(\S+)/i', substr($sql, $i), $matches)) {
                if (trim($buffer) !== '') {
                    $statements[] = $buffer;
                    $buffer = '';
                }
                $delimiter = $matches[1];
                $i += strlen($matches[0]);
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
                $lineComment = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '#') {
                $lineComment = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $blockComment = true;
                $buffer .= $char;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if (substr($sql, $i, strlen($delimiter)) === $delimiter) {
                if (trim($buffer) !== '') {
                    $statements[] = $buffer;
                }
                $buffer = '';
                $i += strlen($delimiter) - 1;
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function backupDirectory(): string
    {
        $directory = public_path(self::FOLDER);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Folder public/backups tidak dapat dibuat.');
        }

        return $directory;
    }

    /** Hanya terima nama berkas .sql di dalam public/backups (anti path traversal). */
    private function resolveBackup(string $file): ?array
    {
        $directory = realpath(public_path(self::FOLDER));
        $filename = basename(str_replace('\\', '/', $file));

        if ($directory === false || $filename === '' || $filename === '.' || $filename === '..'
            || ! preg_match('/^[\w.-]+\.sql$/i', $filename)) {
            return null;
        }

        $absolute = realpath($directory.DIRECTORY_SEPARATOR.$filename);

        if ($absolute === false || dirname($absolute) !== $directory || ! is_file($absolute)) {
            return null;
        }

        return [$filename, $absolute];
    }
}
