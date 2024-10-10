<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientInterface::class, Client::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
//            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

//        \Illuminate\Database\Eloquent\Model::preventLazyLoading();

        // @see https://laravel.com/docs/11.x/eloquent-resources#data-wrapping
        JsonResource::withoutWrapping();
    }
}
