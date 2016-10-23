<?php 

namespace Support\JsonApi\Contracts\Exceptions;

use Exception;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;

interface RendererInterface
{
    /**
     * @return int
     */
    public function getStatusCode();

    /**
     * @return $array
     */
    public function getHeaders();


    /**
     * @param Exception $exception
     *
     * @return mixed
     */
    public function render(Exception $exception);
}
