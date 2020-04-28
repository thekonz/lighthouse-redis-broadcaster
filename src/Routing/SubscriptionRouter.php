<?php


namespace thekonz\LighthouseRedisBroadcaster\Routing;


use Illuminate\Routing\Router;

class SubscriptionRouter
{
    public function routes(Router $router)
    {
        $router->post('graphql/subscriptions/auth', [
            'as' => 'lighthouse.subscriptions.auth',
            'uses' => SubscriptionAuthenticationController::class,
        ]);
    }
}