<?php 

namespace Support\JsonApi\Exceptions\Renderers;

use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Document\Error;
use Support\JsonApi\Exceptions\BaseRenderer;
use Support\JsonApi\Contracts\Http\ResponsesInterface;

class NoContentRenderer extends BaseRenderer
{
    /**
     * @param ResponsesInterface $responses
     * @param int                $statusCode
     */
    public function __construct(ResponsesInterface $responses)
    {
        parent::__construct($responses);
        $this->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * @inheritdoc
     */
    public function getError(Exception $exception)
    {
        return new Error();
    }
}
