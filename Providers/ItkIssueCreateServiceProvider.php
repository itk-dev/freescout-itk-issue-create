<?php

namespace Modules\ItkIssueCreate\Providers;

use App\Conversation;
use App\Customer;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\ItkIssueCreate\Service\Helper;
use Modules\ItkIssueCreate\Service\TeamsHelper;
use Modules\ItkLeantimeSync\Service\LeantimeHelper;
use TorMorten\Eventy\Facades\Events as Eventy;

define('ITK_ISSUE_CREATE_MODULE', 'itkissuecreate');

class ItkIssueCreateServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        Eventy::addAction(
            'conversation.created_by_customer',
            function (Conversation $conversation, Thread $thread, Customer $customer) {
                if (!$customer->exists) {
                    return;
                }

                // Create Leantime ticket.
                $leantimeResult = app(LeantimeHelper::class)
                ->sendToLeantime($conversation, $thread, $this->getCustomerName($customer));

                // Create teams message.
                app(TeamsHelper::class)
                ->sendToTeams($conversation, $this->getCustomerName($customer), $leantimeResult['url']);

                // Create Freescout note with a Leantime reference and add ticket Id.
                app(Helper::class)
                ->addLeantimeReference($conversation->getOriginal()['id'], $leantimeResult);
            },
            20,
            3
        );

        Eventy::addAction(
          'conversation.created_by_user_can_undo',
          function (Conversation $conversation, Thread $thread) {
            // Create Leantime ticket.
            $leantimeResult = app(LeantimeHelper::class)
              ->sendToLeantime($conversation, $thread, 'ITK Support');
            //$thread->created_by_user_cached()->getFullName();

            // Create Freescout note with a Leantime reference and add ticket Id.
            app(Helper::class)
              ->addLeantimeReference($conversation->getOriginal()['id'], $leantimeResult);
          },
          20,
          3
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('itkissuecreate.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php',
            'itkissuecreate'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/itkissuecreate');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/itkissuecreate';
        }, \Config::get('view.paths')), [$sourcePath]), 'itkissuecreate');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     *
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

  /**
   * Create customer name from first name and last name.
   *
   * @param Customer $customer
   *   The customer class.
   *
   * @return string
   *   The customer name.
   */
    private function getCustomerName(Customer $customer): string
    {
        return $customer->getOriginal()['first_name'] . ' ' . ($customer->getOriginal()['last_name'] ?? '');
    }
}
