<?php 

namespace Support\JsonApi\Exceptions\Renderers;

use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Document\Error;
use Support\JsonApi\Exceptions\BaseRenderer;
use Support\JsonApi\Contracts\Http\ResponsesInterface;

class NotFoundRenderer extends BaseRenderer
{

    /**
     * @param ResponsesInterface $responses
     * @param int                $statusCode
     */
    public function __construct(ResponsesInterface $responses)
    {
        parent::__construct($responses);
        $this->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    /**
     * @inheritdoc
     */
    public function getError(Exception $exception)
    {
        $resource = strtolower(class_basename($exception->getModel()));
        return new Error(null, null, null, null, ( $resource ? $resource. ' can not be found!' : 'Requested resource can not be found!'));
    }
}
