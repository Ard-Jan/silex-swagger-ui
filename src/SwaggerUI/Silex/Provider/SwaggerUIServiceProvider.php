<?php

namespace SwaggerUI\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * The SwaggerUIServiceProvider adds views for swagger UI to a silex app, making
 * it possible to view swagger docs.
 */
class SwaggerUIServiceProvider implements ServiceProviderInterface
{
    const FONT_MIME_TYPE = 'font/opentype';

    const JS_MIME_TYPE = 'text/javascript';

    const CSS_MIME_TYPE = 'text/css';

    const IMAGE_MIME_TYPE = 'text/css';

    const INDEX_FILE = 'index.html';

    const HTML_MIME_TYPE = 'text/html';

    const DEFAULT_DOCUMENT_URL = "http://petstore.swagger.io/v2/swagger.json";

    /**
     * @var string
     */
    private $rootDir;

    /**
     * File types
     */
    private $assetMimeTypes = [
        'eot' => self::FONT_MIME_TYPE,
        'svg' => self::FONT_MIME_TYPE,
        'ttf' => self::FONT_MIME_TYPE,
        'woff' => self::FONT_MIME_TYPE,
        'woff2' => self::FONT_MIME_TYPE,
        'js' => self::JS_MIME_TYPE,
        'css' => self::CSS_MIME_TYPE,
        'png' => self::IMAGE_MIME_TYPE,
        'gif' => self::IMAGE_MIME_TYPE,
        'html' => self::HTML_MIME_TYPE,
    ];

    /**
     * Add routes to the swagger UI documentation browser
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        preg_match('/(\/.*)\/.*|(\/)/', $app['swaggerui.path'], $matches);
        $assetsPath = (count($matches)) ? array_pop($matches) : $app['swaggerui.path'];

        // Index route
        $app->get($app['swaggerui.path'], array($this, 'getIndex'));
        // Asset in the root directory
        $app->get($assetsPath . '/{asset}', array($this, 'getAsset'));
        // Assets in a folder
        $app->get($assetsPath . '/{directory}/{asset}', array($this, 'getAssetFromDirectory'));
    }

    /**
     * Registers the swagger UI service
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        $this->rootDir = __DIR__ . '/../../../../public/';
    }

    /**
     * @param string $path Path to the file
     *
     * @return Response
     */
    public function getFile($path)
    {
        if (is_file($path)) {
            $file = new File($path);
            $fileContent = file_get_contents($path);
            $response = new Response($fileContent);
            $response->headers->set('Content-Type', $this->getContentType($file->getExtension()));
            $response->setCharset('UTF-8');
            return $response;
        }

        return new Response('File not found', 404);
    }

    /**
     * @param string $fileExtension
     *
     * @return mixed
     * @throws \Exception
     */
    private function getContentType($fileExtension)
    {
        if (!isset($this->assetMimeTypes[$fileExtension])) {
            throw new \Exception(sprintf('No mime type for file extension %s was found', $fileExtension));
        }

        return $this->assetMimeTypes[$fileExtension];
    }

    /**
     * @param Request     $request
     * @param Application $application
     *
     * @return Response
     */
    public function getIndex(Request $request, Application $application)
    {
        $file = $this->getFile(
            $this->rootDir . static::INDEX_FILE
        );

        $content = str_replace(
            static::DEFAULT_DOCUMENT_URL,
            $request->getBasePath() . $application['swaggerui.docs'],
            $file->getContent()
        );

        $file->setContent($content);

        return $file;
    }

    /**
     * @param string $asset
     *
     * @return Response
     */
    public function getAsset($asset)
    {
        return $this->getFile(
            $this->rootDir . $asset
        );
    }

    /**
     * @param string $asset
     * @param string $directory
     *
     * @return Response
     */
    public function getAssetFromDirectory($directory, $asset)
    {
        return $this->getFile(
            $this->rootDir . $directory . DIRECTORY_SEPARATOR . $asset
        );
    }
}
