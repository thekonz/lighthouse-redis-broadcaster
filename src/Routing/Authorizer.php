<?php


namespace thekonz\LighthouseRedisBroadcaster\Routing;


use Illuminate\Http\Request;
use Nuwave\Lighthouse\Subscriptions\Authorizer as BaseAuthorizer;

class Authorizer extends BaseAuthorizer
{
    /**
     * @param Request $request
     * @return bool
     */
    public function authorize(Request $request): bool
    {
        $request['channel_name'] = str_replace(
            'presence-',
            '',
            $request->input('channel_name', '')
        );

        return parent::authorize($request); // TODO: Change the autogenerated stub
    }
}
