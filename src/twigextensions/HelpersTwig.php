<?php

namespace simplicateca\burton\twigextensions;

use Craft;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use yii\helpers\Inflector;

class HelpersTwig extends AbstractExtension
{

    public function getName(): string
    {
        return 'Helpers';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('jsonc_include', [$this, 'jsonc_include']),
        ];
    }

    public function getFilters(): array
    {
        return [
            // Yii Inflector Filters
            // https://www.yiiframework.com/doc/api/2.0/yii-helpers-inflector
            new TwigFilter('pluralize',    [$this, 'pluralize']),
            new TwigFilter('singularize',  [$this, 'singularize']),
            new TwigFilter('ordinalize',   [$this, 'ordinalize']),
            new TwigFilter('humanize',     [$this, 'humanize']),
            new TwigFilter('slugify',      [$this, 'slugify']),

            new TwigFilter('hex2rgb',      [$this, 'hex2rgb']),
            new TwigFilter('text4hex',     [$this, 'text4hex']),

            new TwigFilter('jsonc_decode', [$this, 'jsonc_decode']),

        ];
    }

    public function pluralize($string)
    {
        return Inflector::pluralize($string);
    }

    public function singularize($string)
    {
        return Inflector::singularize($string);
    }

    public function ordinalize($string)
    {
        return Inflector::ordinalize($string);
    }

    public function humanize($string)
    {
        return Inflector::humanize($string);
    }

    public function slugify($string)
    {
        return Inflector::slug($string);
    }

    public function jsonc_decode(string $input): mixed
    {
        $lines = preg_split('/\R/', $input);
        $filtered = array_filter($lines, function ($line) {
            return !preg_match('/^\s*\/\//', $line);
        });

        return json_decode(implode("\n", $filtered), true);
    }

    /**
     * Convert a hex color value to RGB.
     *
     * @param string $hex The hex color code to convert.
     * @return string|false The RGB representation (comma-separated) or false on failure.
     */
    public function hex2rgb($hex)
    {
        $colour = ltrim($hex, '#');

        // Full hex colors (i.e. FFFFFF, 000000, etc)
        if (strlen($colour) == 6) {
            list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);

            // Shorthand hex colors (i.e. FFF, 000, etc)
        } elseif (strlen($colour) == 3) {
            list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);

            // Invalid Hex
        } else {
            return false;
        }

        return hexdec($r) . ',' . hexdec($g) . ',' . hexdec($b);
    }


    /**
     * Determine the most appropriate text color (black or white) for a given background hex value.
     *
     * @param string $hex The background hex color code.
     * @return string The recommended text color (black or white).
     */
    public function text4hex($hex)
    {

        // Convert hex code to an array of RGB values
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Convert RGB values to decimal
        $decimal_r = intval($r, 16);
        $decimal_g = intval($g, 16);
        $decimal_b = intval($b, 16);

        // Apply formula to determine text color
        $luminance = $decimal_r * 0.299 + $decimal_g * 0.587 + $decimal_b * 0.114;

        // Use #000000 for text color if luminance is greater than 186
        return ($luminance > 156) ? '#000000' : '#ffffff';
    }


    public function jsonc_include($files)
    {
        $files = is_iterable($files) ? $files : [$files];

        foreach ($files as $file) {
            $trimmed = trim($file);
            if (!preg_match('/\.jsonc?$/', $trimmed)) {
                $trimmed .= '.jsonc';
            }
        }

        // // Try to include the first file that exists
        // foreach ($processedFiles as $file) {
        //     try {
        //         return $env->load($file)->render();
        //     } catch (LoaderError $e) {
        //         // Skip if file doesn't exist
        //         continue;
        //     }
        // }

        return Craft::$app->getView()->getTemplatesPath();

        // // If none found, maybe return null or throw
        // Craft::warning('jsonc_include: None of the provided files could be included.', __METHOD__);
        // return null;
    }
}
