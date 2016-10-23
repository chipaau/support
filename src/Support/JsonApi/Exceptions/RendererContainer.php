<?php

namespace Support\JsonApi\Exceptions;

use Exception;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use Support\JsonApi\Contracts\Exceptions\RendererInterface;
use Support\JsonApi\Contracts\Exceptions\RendererContainerInterface;


class RendererContainer implements RendererContainerInterface
{
    /**
     * @var array
     */
    private $renderers = [];
    protected $responses;

    /**
     * @var RendererInterface
     */
    private $defaultRenderer;

    /**
     * @param RendererInterface  $defaultRenderer
     */
    public function __construct(RendererInterface $defaultRenderer)
    {
        $this->defaultRenderer = $defaultRenderer;
    }

    /**
     * @inheritdoc
     */
    public function registerRenderer($exceptionClass, RendererInterface $renderer)
    {
        $this->renderers[$exceptionClass] = $renderer;
    }

    /**
     * @inheritdoc
     */
    public function registerRenderers(array $renderers = array())
    {
        if (!empty($renderers)) {
            foreach ($renderers as $exceptionClass => $renderer) {
                $this->registerRenderer($exceptionClass, $renderer);
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRenderer($exception)
    {
        $class = get_class($exception);
        $hasRenderer = isset($this->renderers[$class]) === true;
        if (!$hasRenderer && env('APP_DEBUG')) {
            throw $exception;
            
        }
        $renderer    = $hasRenderer ? $this->renderers[$class] : $this->defaultRenderer;
        return $renderer;
    }
}
