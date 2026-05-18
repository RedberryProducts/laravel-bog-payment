<?php

namespace RedberryProducts\LaravelBogPayment\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use RedberryProducts\LaravelBogPayment\BogPaymentServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'RedberryProducts\\LaravelBogPayment\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            BogPaymentServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('bog-payment.callback_url', 'https://example.com/callback');
        config()->set('bog-payment.redirect_urls', [
            'success' => 'https://example.com/success',
            'fail' => 'https://example.com/fail',
        ]);

        /*
        $migration = include __DIR__.'/../database/migrations/create_bog-payment_table.php.stub';
        $migration->up();
        */
    }
}
