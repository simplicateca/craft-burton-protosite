<?php

namespace simplicateca\burton;

use simplicateca\burton\base\BurtonThemeBase;

class Burton extends BurtonThemeBase
{
    // protected ?string $_moduleAlias = '@modules/burton';
    protected ?string $_consoleNamespace = 'simplicateca\\burton\\console\\controllers';

    protected array $_extensions = [
        \simplicateca\burton\twigextensions\TextBaseTwig::class,
        \simplicateca\burton\twigextensions\CardBaseTwig::class,
        \simplicateca\burton\twigextensions\BuilderBaseTwig::class,
        \simplicateca\burton\twigextensions\CollectionBaseTwig::class,
        \simplicateca\burton\twigextensions\MediaBaseTwig::class,
        \simplicateca\burton\twigextensions\HelpersTwig::class,
        \simplicateca\burton\twigextensions\MacroChainTwig::class,
    ];

    protected array $_siteTemplatePath = [
        '_burton' => __DIR__ . DIRECTORY_SEPARATOR . 'templates',
    ];

    protected array $_translations = [
        'burton' => [
            'class' => \craft\i18n\PhpMessageSource::class,
            'sourceLanguage' => 'en-US',
            'basePath' => '@simplicateca/burton/translations',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ],
    ];

    protected array $_cpAssetBundles = [
        \simplicateca\burton\assetbundles\burton\BurtonAsset::class,
    ];
}
