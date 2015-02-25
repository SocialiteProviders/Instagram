<?php
namespace SocialiteProviders\Instagram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'instagram', __NAMESPACE__.'\Provider'
        );
    }
}
