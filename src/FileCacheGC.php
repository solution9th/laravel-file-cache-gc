<?php

namespace Solution9th\LaravelFileCacheGC;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class FileCacheGC extends Command
{

    public $signature = 'cache:file-gc {--d|detail : Output deleted file info}';

    public $description = 'File cache garbage collection';

    public function handle()
    {
        $outputDetail = $this->option('detail');

        $this->info('GC start: ');

        // get all cache stores use file driver
        $fileStores = array_filter(config('cache.stores'), function ($v) {
            return isset($v['driver']) && $v['driver'] === 'file' && isset($v['path']);
        });

        $deleted     = 0;
        $currentTime = Carbon::now()->getTimestamp();
        $ignoreFiles = ['.', '..', '.gitignore'];
        foreach ($fileStores as $store) {
            $disk = md5($store['path']);
            Config::set('filesystems.disks.' . $disk, ['driver' => 'local', 'root' => $store['path']]);

            foreach ($this->getFiles($disk) as $file) {
                if (! in_array(basename($file), $ignoreFiles)) {
                    try {
                        $expire = substr(Storage::disk($disk)->get($file), 0, 10);
                        if ($expire <= $currentTime) {
                            Storage::disk($disk)->delete($file);
                            $deleted++;

                            if ($outputDetail) {
                                $this->info($file);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->error(sprintf('delete file %s failed, %s', $file, $e->getMessage()));
                    }
                }
            }
        }

        $this->info('GC done, ' . $deleted . ' files has deleted.');
    }

    /**
     * Get all directories
     * @param string $disk
     * @param null   $dir
     * @return \Generator
     */
    protected function getDirectories($disk, $dir = null)
    {
        foreach (Storage::disk($disk)->directories($dir) as $dir) {
            yield $dir;

            foreach ($this->getDirectories($disk, $dir) as $sub) {
                yield $sub;
            }
        }
    }

    /**
     * Get all files
     * @param $disk
     * @return \Generator
     */
    protected function getFiles($disk)
    {
        foreach ($this->getDirectories($disk) as $dir) {
            foreach (Storage::disk($disk)->files($dir) as $file) {
                yield $file;
            }
        }
    }

}