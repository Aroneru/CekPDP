<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads the sample policies listed in config (cekpdp.samples) from the
 * evaluation dataset. Only listed slugs can be loaded.
 */
final class SamplePolicies
{
    /** @var array<string, SamplePolicy|null> */
    private array $loaded = [];

    /**
     * @param  list<string>  $slugs
     */
    public function __construct(
        private readonly string $directory,
        private readonly array $slugs,
    ) {}

    /**
     * @return list<SamplePolicy>
     */
    public function all(): array
    {
        return array_values(array_filter(array_map($this->find(...), $this->slugs)));
    }

    public function find(string $slug): ?SamplePolicy
    {
        if (! in_array($slug, $this->slugs, true)) {
            return null;
        }

        return array_key_exists($slug, $this->loaded) ? $this->loaded[$slug] : $this->loaded[$slug] = $this->load($slug);
    }

    private function load(string $slug): ?SamplePolicy
    {
        $fixture = "{$this->directory}/fixtures/{$slug}.md";
        $expected = "{$this->directory}/expected/{$slug}.yaml";

        if (! is_file($fixture) || ! is_file($expected)) {
            return null;
        }

        try {
            $meta = Yaml::parseFile($expected);
        } catch (ParseException) {
            return null;
        }

        $text = file_get_contents($fixture);

        if (! is_array($meta) || ! is_string($text) || trim($text) === '') {
            return null;
        }

        $context = [];

        foreach (is_array($meta['context'] ?? null) ? $meta['context'] : [] as $key => $answer) {
            if (is_string($key) && is_string($answer)) {
                $context[$key] = $answer;
            }
        }

        return new SamplePolicy(
            slug: $slug,
            title: is_string($meta['title'] ?? null) ? $meta['title'] : $slug,
            language: is_string($meta['language'] ?? null) ? $meta['language'] : 'id',
            context: $context,
            text: $text,
        );
    }
}
