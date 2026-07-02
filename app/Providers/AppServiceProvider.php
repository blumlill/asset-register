<?php

declare(strict_types=1);

namespace App\Providers;

use App\Business\AssetRegistry\Contracts\IAssetRepository;
use App\Business\AssetRegistry\Contracts\IContractRepository;
use App\Business\AssetRegistry\Contracts\IUuidGenerator;
use App\Repository\Eloquent\Repositories\EloquentAssetRepository;
use App\Repository\Eloquent\Repositories\EloquentContractRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IAssetRepository::class, EloquentAssetRepository::class);
        $this->app->bind(IContractRepository::class, EloquentContractRepository::class);
        $this->app->bind(IUuidGenerator::class, fn () => new class implements IUuidGenerator
        {
            public function generate(): string
            {
                return (string) Str::uuid();
            }
        });
    }

    public function boot(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
