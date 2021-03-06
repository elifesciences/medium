<?php

namespace eLife\Medium\Response;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Version resolved.
 *
 * Usage:
 *
 * $versions = new VersionResolver();
 * $versions->accept('application/vnd.elife.medium-article-list+json;version=1', function($articles) {
 *      return new MediumArticleListResponse(...$articles);
 * });
 * $versions->accept('application/vnd.elife.medium-article-list+json;version=2', function($articles) {
 *      return new MediumArticleListResponseV2($articles);
 * });
 *
 * Then in the controller:
 *
 * return $versions->resolve($acceptHeader)($articles);
 */
final class VersionResolver
{
    private $version;

    private $default;

    public function accept(string $contentType, callable $fn, $isDefault = false)
    {
        $this->version[$contentType] = $fn;
        if ($isDefault) {
            $this->default = $fn;
        }
    }

    public function resolve(string $acceptType, ...$args)
    {
        if ('application/json' === strtolower($acceptType)) {
            if (null === $this->default) {
                // 406 exception if no default set.
                throw new NotAcceptableHttpException('No default response type set');
            }
            // Is generic application/json return default type.
            $resolver = $this->default;
        } elseif (isset($this->version[$acceptType])) {
            // If a version exists.
            $resolver = $this->version[$acceptType];
        } else {
            // 406 exception if not valid.
            throw new NotAcceptableHttpException("Accept: $acceptType cannot be satisfied");
        }

        return $resolver(...$args);
    }
}
