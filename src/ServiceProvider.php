<?php

namespace DQ\EInvoice;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->bind('einvoice', function () {
            return new EInvoiceService(config('services.einvoice'));
        });
    }
}
