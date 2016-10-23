<?php

namespace skeleton\Socialite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AasandhaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'aasandha', Provider::class
        );
    }
}