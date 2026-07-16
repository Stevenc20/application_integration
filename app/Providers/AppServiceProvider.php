<?php

namespace App\Providers;

use App\Events\ProductionPlanUpdated;
use App\Listeners\RegenerateTimelineListener;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ProductionPlanUpdated::class, RegenerateTimelineListener::class);

        Blade::directive('fmtQty', fn ($expr) => "<?php echo \\App\\Support\\ProductionFormat::qty({$expr}); ?>");
        Blade::directive('fmtMin', fn ($expr) => "<?php echo \\App\\Support\\ProductionFormat::minutes({$expr}); ?>");
        Blade::directive('fmtCt', fn ($expr) => "<?php echo \\App\\Support\\ProductionFormat::ct({$expr}); ?>");
        Blade::directive('fmtGsph', fn ($expr) => "<?php echo \\App\\Support\\ProductionFormat::gsph({$expr}); ?>");
    }
}
