
# Laravel file cache gc

In some cases, file cache driver is the more suitable choice for our project, but laravel do not clean up the old files unless you get it, and the expired cache files may fill up the disk.

This package create an artisan command `cache:file-gc` do the garbage collection work that help you clean up the expired cache files.


## Installation

`composer require solution9th/laravel-file-cache-gc`

## Usage

- Run the command manually, and you can with option `-d` to output the deleted files.
```php
php artisan cache:file-gc
```

- As a schedule write in `app/Console/Kernel.php`.
```php
$schedule->command('cache:file-gc')->dailyAt('02:00');
```


## License

[MIT](LICENSE).
