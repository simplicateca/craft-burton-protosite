<?php
/**
 * Card Layer Twig Functions
 *
 * Like its cousins (TextBase, ImageBase, BuilderBase, etc), the use of PHP to create
 *   this macro instead of defining it within Twig is a bit of a necessasry evil.
 *
 *   Twig lacks a way to return variables from inline macros, which makes it very hard
 *   to define a consistent data structure for complex elements. Without this, there
 *   would be too much code duplication and inconsistency.
 *
 *   If Twig had a more robust way to return a variable from an inline {%- macro %}
 *   exectution (without rendering it), `CardBase` could easily exist as Twig code.
 *
 *   Maintains path and inheritence consistency when generating content cards.
 *
 * This extension is not *required*, but it provides a convenient way to maintain consistency
 * in the card object across templates and avoid unnecessary code duplication in Twig templates.
 *
 * If Twig allowed objects to be returned from inline {%- macro %} functions, this extension
 * would not be necessary.
 *
 * Creates a base card object from a given entry.
 *
 * This extension is not required, but it provides a convenient way to maintain consistency
 * in the card object across templates and avoid unnecessary object creation in Twig templates.
 *
 */

namespace simplicateca\burton\twigextensions;

use Craft;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use mmikkel\retcon\Retcon;

class CardBaseTwig extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction( 'CardBase', [ $this, 'CardBase' ] ),
        ];
    }


    public function CardBase( $content ) : mixed
    {
        /**
         * Figure out the type of content we're creating a card for
         *
         * If a `section` is provided, we'll use that, typically we're dealing with
         * Entries Elements, which all have a section property.
         *
         * -> https://craftcms.com/docs/5.x/reference/element-types/entries.html
         *
         * If a section isn't provided, but the content entry has an `id` property, we
         * can assume it's another element type (like an Asset or SuperTable row).
         *
         * !! TODO: Include all Craft Element Types + Commerce Products + Verbb Events
         * https://craftcms.com/docs/5.x/system/elements.html#element-types
         *
         *
         */

        $content  = is_array( $content ) ? (object) $content : $content;

        return [
            'headline'  => self::cardHeadline( $content ),
            'summary'   => self::cardSummary( $content ),
            'images'    => self::cardImages( $content ),
            'label'     => self::cardLabel( $content ),
            'url'       => self::cardUrl( $content ),
        ];
    }

    private static function cardHeadline( $object ) : string|null
    {
        if( !empty( $object->headline ) ) {
            return $object->headline;
        }

        // TODO: Remove references to $object->summary
        if( !empty( $object->card->summary ) || !empty( $object->summary ) ) {

            $headline = $object->card->summary ?? $object->summary;
            $headline = Retcon::getInstance()->retcon->only( $headline, 'h3' );
            $headline = Retcon::getInstance()->retcon->change( $headline, ['h3'], false );
            $headline = Retcon::getInstance()->retcon->change( $headline, ['a', 'button'], 'span' );
            $headline = Retcon::getInstance()->retcon->removeEmpty( $headline );

            if( !empty( trim( strip_tags($headline) ) ) ) {
                return $headline;
            }
        }

        if( !empty( $object->title ) ) {
            return $object->title;
        }

        if( !empty( $object->name ) ) {
            return $object->name;
        }

        return null;
    }


    private static function cardSummary( $object )
    {
        if( !empty( $object->card->summary ) ) {
            $summary = $object->card->summary;
        } elseif( !empty( $object->summary ) ) {
            $summary = $object->summary;
        } elseif( !empty( $object->description ) ) {
            $summary = $object->description;
        } elseif( !empty( $object->text ) ) {
            $summary = $object->text;
        } else {
            return null;
        }

        $summary = Retcon::getInstance()->retcon->remove( $summary, ['h1', 'h3', '.eyebrow', 'a'] );
        $summary = Retcon::getInstance()->retcon->remove( $summary, ['img', 'figure', 'iframe'] );
        $summary = Retcon::getInstance()->retcon->removeEmpty( $summary );

        if( empty( trim( strip_tags($summary) ) ) ) { return null; }

        $summary = Retcon::getInstance()->retcon->change( $summary, ['h2', 'h3', 'h4', 'h5', 'h6', 'ol', 'li', 'div', 'p', 'a', 'button', 'table', 'td', 'tr', 'tbody', 'tfoot'], 'span' );
        return self::_truncate( $summary, 250 );

    }


    private static function cardImages( $object ): mixed
    {
        if( !empty($object->card->images) ) {
            return $object->card->images;
        }

        if( !empty($object->images) ) {
            return $object->images;
        }

        if( !empty( $object->card->summary ) ) {
            $source = $object->card->summary;
        } elseif( !empty( $object->summary ) ) {
            $source = $object->summary;
        } elseif( !empty( $object->description ) ) {
            $source = $object->description;
        } elseif( !empty( $object->text ) ) {
            $source = $object->text;
        } else {
            return [];
        }

        $image = Retcon::getInstance()->retcon->only($source, ['img'], true);
        if( !empty($image) ) {
            $attr = Craft::$app->getView()->getTwig()->getFilter('parseAttr')->getCallable()($image);
            if( !empty( $attr['src'] ) ) {
                return[$attr['src']];
            }
        }

        return [];
    }


    private static function cardLabel( $object ) {
        if( !empty( $object->label ) ) {
            $label = $object->label;
        } elseif( !empty( $object->headline ) ) {
            $label = $object->headline;
        } elseif( !empty( $object->title ) ) {
            $label = $object->title;
        } else {
            return null;
        }

        return $label;
    }


    private static function cardUrl( $object ) {
        if( !empty( $object->url ) ) {
            $url = $object->url;
        } elseif( !empty( $object->link ) ) {
            $url = $object->link;
        } else {
            return null;
        }

        return $url;
    }


    private static function _truncate( $string, $length = 150 ) : string
    {
        // Ensure a space after natural breaks in text caused by removing HTML tags
        $string = preg_replace('/></', '> <', $string);
        $string = trim(strip_tags($string));

        if( strlen($string) > $length )
        {
            $wordwrap = wordwrap($string, $length);
            $string = trim( substr($wordwrap, 0, strpos($wordwrap, "\n")) );

            $reversedString = strrev($string);
            $punctuationPositions = [
                strpos($reversedString, '.'),
                strpos($reversedString, ','),
                strpos($reversedString, '"'),
                strpos($reversedString, ')'),
            ];

            // Filter positions to prioritize periods within the first 20% of the truncated length
            $filteredPositions = array_filter($punctuationPositions, fn($pos) => $pos !== false);
            $closestPeriod = strpos($reversedString, '.');
            $closestPunctuation = $closestPeriod !== false && $closestPeriod <= $length * 0.20
                ? $closestPeriod
                : (!empty($filteredPositions) ? min($filteredPositions) : false);

            if ($closestPunctuation !== false && $closestPunctuation <= $length * 0.20) {
                $string = strrev(substr($reversedString, $closestPunctuation));
            }

            if (substr($string, -1) != '.') {
                if (strlen(substr($wordwrap, strpos($wordwrap, "\n"), -1))) {
                    $string = $string . "…";
                }
            }
        }

        return $string;
    }

}