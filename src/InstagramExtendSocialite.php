<?php
namespace SocialiteProviders\Instagram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('instagram', __NAMESPACE__.'\Provider');
    }
}
