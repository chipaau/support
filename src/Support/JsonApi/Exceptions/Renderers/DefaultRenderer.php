<?php 

namespace Support\JsonApi\Exceptions\Renderers;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Neomerx\JsonApi\Document\Error;
use Support\JsonApi\Exceptions\BaseRenderer;
use Support\JsonApi\Contracts\Http\ResponsesInterface;

class DefaultRenderer extends BaseRenderer
{

    /**
     * @param ResponsesInterface $responses
     * @param int                $statusCode
     */
    public function __construct(ResponsesInterface $responses)
    {
        parent::__construct($responses);
        $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @inheritdoc
     */
    public function getError(Exception $exception)
    {
        $texts = Response::$statusTexts;
        if (method_exists($exception, 'getStatusCode')) {
            $title = isset($texts[$exception->getStatusCode()]) ? $texts[$exception->getStatusCode()]: $texts[Response::HTTP_INTERNAL_SERVER_ERROR];
            $this->setStatusCode($exception->getStatusCode());
        } else {
            $title = $texts[Response::HTTP_INTERNAL_SERVER_ERROR];
        }
        return new Error(
            null, 
            null, 
            null, 
            null,
            $title, 
            (env('APP_DEBUG') ? 
                $exception->getMessage() . '. Error on file: ' . $exception->getFile() . ' at line: ' . $exception->getLine() . PHP_EOL .
                $exception->getTraceAsString() : null)
        );
    }
}
