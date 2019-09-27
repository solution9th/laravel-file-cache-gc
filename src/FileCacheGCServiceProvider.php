<?php

namespace Solution9th\LaravelFileCacheGC;

use Illuminate\Support\ServiceProvider;

class FileCacheGCServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->commands(FileCacheGC::class);
    }

}