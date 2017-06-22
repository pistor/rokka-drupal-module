<?php

namespace Drupal\rokka\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite image styles URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * This processor handles Rokka image style callback:
 * - In order to allow the webserver to serve these files with dynamic args
 *   the route is registered under /s3/files/styles prefix and change internally
 *   to pass validation and move the file to query parameter. This file will be
 *   processed in S3fsImageStyleDownloadController::deliver().
 *
 * Private files use the normal private file workflow.
 *
 * @see \Drupal\rokka\Controller\S3fsImageStyleDownloadController::deliver()
 * @see \Drupal\image\Controller\ImageStyleDownloadController::deliver()
 * @see \Drupal\image\PathProcessor\PathProcessorImageStyles::processInbound()
 */
class RokkaPathProcessorImageStyles implements InboundPathProcessorInterface {

    const IMAGE_STYLE_PATH_PREFIX = '/rokka/files/styles/';

    /**
     * {@inheritdoc}
     */
    public function processInbound($path, Request $request) {
        if ($this->isImageStylePath($path)) {
            // Strip out path prefix.
            $rest = preg_replace('|^' . preg_quote(static::IMAGE_STYLE_PATH_PREFIX, '|') . '|', '', $path);

            // Get the image style, scheme and path.
            if (substr_count($rest, '/') >= 2) {
                list($image_style, $scheme, $file) = explode('/', $rest, 3);

                if ($this->isValidScheme($scheme)) {
                    // Set the file as query parameter.
                    $request->query->set('file', $file);
                    $path = static::IMAGE_STYLE_PATH_PREFIX . $image_style . '/' . $scheme;
                }
            }
        }

        return $path;
    }

    /**
     * Check if the path is a Rokka image style path.
     *
     * @param $path
     * @return bool
     */
    private function isImageStylePath($path) {
        return strpos($path, static::IMAGE_STYLE_PATH_PREFIX) === 0;
    }

    /**
     * Check if scheme is a Rokka image style.
     *
     * @param $scheme
     * @return bool
     */
    private function isValidScheme($scheme)
    {
        return 'rokka' === $scheme;
    }
}
