<?php

namespace simplicateca\burton\base;

use Craft;
use craft\web\UrlManager;
use craft\web\View;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;
use yii\base\Module;
use yii\base\InvalidConfigException;

class BurtonThemeBase extends Module
{
    protected array $_listeners = [];
    protected array $_components = [];
    protected array $_extensions = [];
    protected array $_siteTemplatePath = [];
    protected array $_siteUrlRules = [];
    protected array $_translations = [];
    protected array $_cpAssetBundles = [];

    protected ?string $_moduleAlias = null;
    protected ?string $_consoleNamespace = null;

    public function init(): void
    {
        parent::init();

        // Register module alias (if set)
        if ($this->_moduleAlias) {
            Craft::setAlias($this->_moduleAlias, $this->getBasePath());
        }

        $this->setComponents($this->_components);
        $this->twigExtensions();
        $this->siteTemplateRoots();
        $this->siteUrlRules();
        $this->eventListeners();
        $this->translations();
        $this->cpAssetBundles();

        // Console namespace (if in console context)
        if ($this->_consoleNamespace && Craft::$app instanceof \craft\console\Application) {
            $this->controllerNamespace = $this->_consoleNamespace;
        }
    }

    private function eventListeners(): void
    {
        foreach ($this->_listeners as $listener) {
            $listener::listeners();
        }
    }

    private function twigExtensions(): void
    {
        foreach ($this->_extensions as $extension) {
            Craft::$app->view->registerTwigExtension(new $extension());
        }
    }

    private function siteTemplateRoots(): void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                foreach ($this->_siteTemplatePath as $alias => $path) {
                    $event->roots[$alias] = $path;
                }
            }
        );
    }

    private function siteUrlRules(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                foreach ($this->_siteUrlRules as $pattern => $route) {
                    $event->rules[$pattern] = $route;
                }
            }
        );
    }

    private function translations(): void
    {
        $i18n = Craft::$app->getI18n();

        foreach ($this->_translations as $category => $config) {
            if (!isset($i18n->translations[$category]) && !isset($i18n->translations[$category.'*'])) {
                $i18n->translations[$category] = $config;

                Craft::info(
                    Craft::t(strtolower($category), '{name} loaded', ['name' => $category]),
                    __METHOD__
                );
            }
        }
    }

    private function cpAssetBundles(): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest() && !empty($this->_cpAssetBundles)) {
            Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (\craft\events\TemplateEvent $event) {
                foreach ($this->_cpAssetBundles as $bundle) {
                    try {
                        Craft::$app->getView()->registerAssetBundle($bundle);
                    } catch (InvalidConfigException $e) {
                        Craft::error('Error registering AssetBundle - ' . $e->getMessage(), __METHOD__);
                    }
                }
            });
        }
    }
}
