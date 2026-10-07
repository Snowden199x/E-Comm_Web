<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivatizeVerificationDocuments extends Command
{
    protected $signature = 'verification:privatize {--dry-run : Count public verification files without moving them}';

    protected $description = 'Move public buyer, seller, and logistics verification uploads to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0;
        $removed = 0;
        $failed = 0;

        foreach (['valid-ids', 'business-permits'] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if ($this->option('dry-run')) {
                    $moved++;
                    continue;
                }

                try {
                    $copied = false;
                    if (! $private->exists($path)) {
                        $stream = $public->readStream($path);
                        if (! is_resource($stream)) {
                            throw new RuntimeException('Could not read a public verification file.');
                        }

                        try {
                            if (! $private->put($path, $stream)) {
                                throw new RuntimeException('Could not copy a verification file to private storage.');
                            }
                        } finally {
                            fclose($stream);
                        }

                        $copied = true;
                    }

                    $publicHash = hash_file('sha256', $public->path($path));
                    $privateHash = hash_file('sha256', $private->path($path));
                    if ($publicHash === false || $privateHash === false || ! hash_equals($publicHash, $privateHash)) {
                        throw new RuntimeException('A private copy is missing or differs from its public source.');
                    }

                    if ($copied) {
                        $moved++;
                    }
                    if (! $public->delete($path)) {
                        throw new RuntimeException('Could not remove a verified public copy.');
                    }
                    $removed++;
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->components->error($exception->getMessage());
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->components->info("{$moved} public verification files would be moved.");
        } else {
            $this->components->info("{$moved} files copied to private storage; {$removed} public copies removed; {$failed} failures.");
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
