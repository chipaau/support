<?php 

namespace Support\JsonApi\Contracts\Exceptions;

interface RendererContainerInterface
{
    /**
     * Register exception render
     *
     * @param string            $exceptionClass
     * @param RendererInterface $renderer
     *
     * @return void
     */
    public function registerRenderer($exceptionClass, RendererInterface $renderer);

    /**
     * Get registered or default render for exception.
     *
     * @param string $exceptionClass
     *
     * @return RendererInterface
     */
    public function getRenderer($exceptionClass);
}
