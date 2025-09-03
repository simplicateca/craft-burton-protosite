<?php

namespace simplicateca\burton\variables;

use Craft;

class BurtonVariable
{
    /**
     * Example: Return a static string
     */
    public function hello(): string
    {
        return "Hello Burton!";
    }

    /**
     * Example: Return something from plugin settings
     */
    // public function getSettingValue(): string
    // {
    //     return Craft::$app->plugins->getPlugin('my-plugin')->getSettings()->someSetting ?? '';
    // }

    /**
     * Example: Return something dynamic (like current user email)
     */
    // public function currentUserEmail(): ?string
    // {
    //     $user = Craft::$app->getUser()->getIdentity();
    //     return $user?->email;
    // }
}
