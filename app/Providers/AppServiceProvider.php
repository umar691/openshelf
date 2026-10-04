<?php

namespace App\Providers;

use App\Services\Payments\PaymentGatewayRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\LinkedIn\Provider as LinkedInProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use SocialiteProviders\TikTok\Provider as TikTokProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('linkedin', LinkedInProvider::class);
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
            $event->extendSocialite('tiktok', TikTokProvider::class);
        });
    }
}
