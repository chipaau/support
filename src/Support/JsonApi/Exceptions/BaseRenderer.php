<?php

namespace Support\JsonApi\Exceptions;

use Exception;
use Neomerx\JsonApi\Factories\Exceptions;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Support\JsonApi\Contracts\Exceptions\RendererInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;

abstract class BaseRenderer implements RendererInterface
{
    protected $statusCode = 500;

    protected $headers = array();

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * @param ResponsesInterface $responses
     */
    public function __construct(ResponsesInterface $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @param Exception $exception
     *
     * @return mixed
     */
    abstract public function getError(Exception $exception);

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function render(Exception $exception, $message = null, array $headers = array())
    {
        return $this->responses->getErrorResponse($this->getError($exception), $this->getStatusCode(), $this->getHeaders());
    }
}
