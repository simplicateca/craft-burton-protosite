<?php
/**
 * Burton - Control Panel Asset Bundle Loader
 */

namespace simplicateca\burton\assetbundles\burton;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class BurtonAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Burton-Admin.js',
        ];

        $this->css = [
            'css/Burton-Admin.css',
        ];
    }
}
