<?php

namespace simplicateca\burton\twigextensions;

use Craft;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class MacroChainTwig extends AbstractExtension
{

    public function getName(): string
    {
        return 'MacroChain';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderMacroIfExists', [$this, 'renderMacroIfExists']),
        ];
    }


    public function renderMacroIfExists(string|array $templatePaths, string|array $macroNames, $data = null, $args = null)
    {
        $templatePaths = (array) $templatePaths;
        $macroNames = (array) $macroNames;
        $args = is_array($args) ? $args : [$args];
        $args['template'] = $templatePaths;

        $view = Craft::$app->getView();

        foreach ($macroNames as $macroName) {

            foreach ($templatePaths as $templateName) {
                try {
                    $resolvedPath = $view->resolveTemplate($templateName);

                    if (!$resolvedPath || !is_file($resolvedPath)) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    Craft::info("Skipping template '{$templateName}' due to resolve error: {$e->getMessage()}", __METHOD__);
                    continue;
                }

                try {
                    $result = $view->renderString(
                        "{%- from '$templateName' import $macroName -%}{{- $macroName is defined ? $macroName(data, ...args) : 'error' -}}",
                        ['data' => $data, 'args' => $args]
                    );

                    if (empty($result) || $result === 'error') {
                        Craft::info("Error rendering macro '{$macroName}' from '{$templateName}'", __METHOD__);
                    } else {
                        return $result;
                    }
                } catch (\Throwable $e) {

                    if (strpos($e->getMessage(), "Call to undefined method Twig\\TemplateWrapper::hasMacro()") !== false) {
                        Craft::info("Macro '{$macroName}' does not exist in '{$templateName}' due to missing hasMacro() method.", __METHOD__);
                        continue;
                    } else {
                        throw new \Twig\Error\RuntimeError(
                            "Macro '{$macroName}' failed in '{$templateName}' on line {$e->getLine()}: {$e->getMessage()}"
                        );
                    }
                }
            }
        }

        return null;
    }
}
