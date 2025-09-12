<?php

namespace simplicateca\burton;

use Craft;
use craft\web\View;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;
use simplicateca\burton\base\BurtonThemeBase;

use yii\base\Event;

class Burton extends BurtonThemeBase
{
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


    public function init() : void
    {
        parent::init();

        $activeTheme = collect(Craft::$app->plugins->allPluginInfo)
            ->filter(function ($value, $key) {
                return strstr( $value["packageName"], "burton" ) && strstr( $value["packageName"], "theme" ); })
            ->where( "isInstalled", true )
            ->one();

        $themePath = array_values( $activeTheme["aliases"] ?? [] )[0] ?? null;

        $this->_siteTemplatePath = [
            '_burton' => array_filter( [
                $themePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '_burton',
                $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '_burton',
            ])
        ];

        $this->siteTemplateRoots();

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                $variable = $event->sender;
                $variable->set('burton', \simplicateca\burton\variables\BurtonVariable::class);
            }
        );
    }

    private function siteTemplateRoots(): void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                foreach ($this->_siteTemplatePath as $handle => $path) {
                    $event->roots[$handle] = $path;
                }
            }
        );
    }
}
