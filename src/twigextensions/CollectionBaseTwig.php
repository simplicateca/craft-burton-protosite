<?php
/**
 * Collection Processing Macro
 *
 * Used to lookup the contents of Collection entries, including static and RSS feeds.
 *
 * There's nothing in here that you can't do directly in Twig (except maybe the RSS feed?).
 * However, it's a lot cleaner and more reusable as an external Twig macro.
 *
 */

namespace simplicateca\burton\twigextensions;

use Craft;
use craft\elements\Entry;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

use Illuminate\Support\Collection;

class CollectionBaseTwig extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction( 'collection', [ $this, 'collection' ] ),
            new TwigFunction( 'collectionFeed', [ $this, 'collectionFeed' ] ),
            new TwigFunction( 'collectionBits', [ $this, 'collectionBits' ] ),
            new TwigFunction( 'collectionQuery', [ $this, 'collectionQuery' ] ),
            new TwigFunction( 'collectionMedia', [ $this, 'collectionMedia' ] ),
            new TwigFunction( 'collectionEntries', [ $this, 'collectionEntries' ] ),
            new TwigFunction( 'collectionImages', [ $this, 'collectionImages' ] ),
        ];
    }

    public function collection( $collection, $params = [] ): mixed
    {
        if( empty( $collection ) ) {
            return null;
        }

        $type = $collection['type'] ?? $collection->type->handle ?? '__NOHANDLE__';
        return match (strtolower($type)) {
            'collectionfeed'    => $this->collectionFeed(array_merge([ 'feedUrl' => $collection->feedUrl ?? null ], $params)),
            'collectionquery'   => $this->collectionQuery(array_merge($collection->query->settings ?? [], $params)),
            'collectionbits'    => $this->collectionBits($collection),
            'collectionmedia'   => $this->collectionMedia($collection),
            'collectionimages'  => $this->collectionImages($collection),
            'collectionentries' => $this->collectionEntries($collection, $params),
            default => throw new \InvalidArgumentException("Unknown Collection EntryType: {$type}"),
        };
    }


    public function collectionBits($collection): Collection
    {
        return $this->_elements($collection, ['bits']);
    }

    public function collectionEntries($collection): Collection
    {
        return $this->_elements($collection, ['entries']);
    }

    public function collectionImages($collection): Collection
    {
        return $this->_elements($collection, ['images']);
    }

    public function collectionMedia($collection): Collection
    {
        return $this->_elements($collection, ['medias', 'embeds']);
    }

    public function collectionFeed(array $cfg): Collection
    {
        $feedUrl = (string) $cfg['feedUrl'] ?? null;
        if (!$feedUrl) {
            throw new \InvalidArgumentException('No feed URL provided for RSS collection.');
        }

        $client = Craft::createGuzzleClient([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'CraftCMS RSS Reader'
            ],
        ]);

        try {
            $response = $client->get($feedUrl);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Non-200 response: " . $response->getStatusCode());
            }

            $body = (string) $response->getBody();
            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml) {
                throw new \Exception("Failed to parse XML.");
            }

            // Detect feed type
            $isRSS  = isset($xml->channel);
            $isAtom = $xml->getName() === 'feed';

            if (!$isRSS && !$isAtom) {
                throw new \Exception("Unknown or unsupported feed format.");
            }

            $items = [];
            if ($isAtom) {
                $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $items = $xml->xpath('/atom:feed/atom:entry');
            } else {
                $items = $xml->xpath('/rss/channel/item');
            }

            // Convert to array if necessary
            $items = is_iterable($items) ? iterator_to_array($items) : [$items];

            return collect($items)->map(function ($item) use ($isAtom) {
                return [
                    'title'       => (string) ($item->title ?? ''),
                    'description' => (string) ($isAtom ? ($item->summary ?: $item->content ?: '') : ($item->description ?: '')),
                    'link'        => $isAtom
                                        ? (isset($item->link['href']) ? (string) $item->link['href'] : '')
                                        : (string) $item->link,
                    'pubDate'     => $isAtom
                                        ? new \DateTime((string) ($item->updated ?? $item->published ?? 'now'))
                                        : (isset($item->pubDate) ? new \DateTime((string) $item->pubDate) : null),
                    'guid'        => (string) ($item->guid ?? $item->id ?? ''),
                ];
            });

        } catch (\Throwable $e) {
            Craft::error('RSS Feed Error: ' . $e->getMessage(), __METHOD__);
            return collect([]);
        }
    }


    public function collectionQuery(array $cfg): object
    {
        // 1 — Resolve which element type to query
        $class = $cfg['element'] ?? Entry::class;

        if (!class_exists($class) || !method_exists($class, 'find')) {
            throw new \InvalidArgumentException("Invalid element type: {$class}");
        }

        /** @var \craft\elements\db\ElementQueryInterface $q */
        $q = $class::find();

        // 2 — Look up every public method on the query object
        $availableMethods = array_map('strtolower', get_class_methods($q));

        // 3 — Iterate over every key/value in the config
        foreach ($cfg as $key => $value) {

            // Skip the key we already consumed
            if ($key === 'element') {
                continue;
            }

            // Treat literal 'null', empty string, or actual null as “not set”
            if ($value === null || $value === '' || $value === 'null') {
                continue;
            }

            // ---- Special‑case: limit -----------------------------------------
            if ($key === 'limit') {
                // Treat '0' as a layman's '-1' (unlimited)
                if ($value === '0' || $value === 0) {
                    $value = '-1';
                }
                // Keep Craft’s convention: −1 (string or int) = unlimited
                if ($value !== -1 && $value !== '-1') {
                    $q->limit((int)$value);
                }
                continue;
            }

            // ---- Prefer method calls if the query exposes one -----------------
            if (in_array(strtolower($key), $availableMethods, true)) {
                // e.g. $q->section('news')
                $q->{$key}($value);
                continue;
            }

            // ---- Fallback: set public property directly if it exists ----------
            if (property_exists($q, $key)) {
                $q->{$key} = $value;
            }

            // (Silently ignore unknown keys; alternatively throw/log here)
        }

        return $q;
    }

    private function _elements($entry, $fields = ['entries']): mixed
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        if( count($fields) === 1 && isset($entry->{$fields[0]}) ) {
            return $entry->{$fields[0]};
        } else {
            $results = collect();
            foreach ($fields as $field) {
                if (isset($entry->{$field}) && method_exists($entry->{$field}, 'all')) {
                $results = $results->merge($entry->{$field}->all());
                }
            }

            return $results;
        }

    }
}
