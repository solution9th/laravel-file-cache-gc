<?php

namespace Solution9th\LaravelFileCacheGC;

use Carbon\Carbon;
use Illuminate\Console\Command;

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
        foreach ($fileStores as $store) {
            foreach ($this->getFiles($store['path']) as $file) {
                try {
                    $expire = substr(@file_get_contents($file), 0, 10);
                    if ($expire <= $currentTime) {
                        @unlink($file);
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

        $this->info('GC done, ' . $deleted . ' files has deleted.');
    }

    /**
     * Get all files
     * @param $path
     * @return \Generator
     */
    protected function getFiles($path)
    {
        $ignoreFiles = ['.', '..', '.gitignore'];
        if ($dh = @opendir($path)) {
            while (($filename = readdir($dh)) !== false) {
                if (! in_array($filename, $ignoreFiles)) {
                    $file = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                    $type = @filetype($file);
                    if ($type === 'dir') {
                        foreach ($this->getFiles($file) as $subFile) {
                            yield $subFile;
                        }
                    }

                    if ($type === 'file') {
                        yield $file;
                    }
                }
            }
            closedir($dh);
        }
    }

}
