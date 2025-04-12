<?php

namespace App\Providers;

use App\Models\LargeFile;
use App\Observers\LargeFileObserver;
use App\Repositories\ElasticsearchLargeFileRepository;
use App\Repositories\LargeFileRepository;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Auth\Notifications\ResetPassword;
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
      // alterando a URL do e-mail de reset de senha
      ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
         return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
      });

      // bindando o client do Elasticsearch e os repositórios
      $this->bindSearchClient();
      $this->bindRepositories();
   }

   private function bindSearchClient()
   {
      $this->app->bind(Client::class, function () {
         return ClientBuilder::create()
            ->setHosts(config('database.elastic.hosts'))
            ->build();
      });
   }

   private function bindRepositories()
   {
      $this->app->bind(LargeFileRepository::class, ElasticsearchLargeFileRepository::class);
   }
}
