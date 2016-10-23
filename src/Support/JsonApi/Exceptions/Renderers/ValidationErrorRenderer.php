<?php 

namespace Support\JsonApi\Exceptions\Renderers;

use Exception;
use Neomerx\JsonApi\Document\Error;
use Support\JsonApi\Exceptions\BaseRenderer;
use Symfony\Component\HttpFoundation\Response;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use \Neomerx\JsonApi\Exceptions\ErrorCollection;

class ValidationErrorRenderer extends BaseRenderer
{

    /**
     * @param ResponsesInterface $responses
     * @param int                $statusCode
     */
    public function __construct(ResponsesInterface $responses)
    {
        parent::__construct($responses);
        $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @inheritdoc
     */
    public function getError(Exception $exception)
    {
        $errors = new ErrorCollection;
        $title = 'Validation fails';
        foreach ($exception->getErrors()->getMessages() as $field => $validationMessages) {
            foreach ($validationMessages as $message) {
                $errors->add(new Error(null, null, Response::HTTP_UNPROCESSABLE_ENTITY, null, $title, $message, [ 'pointer' => '/' . str_replace('.', '/', $field) ]));
            }
        }
        return $errors;
    }
}
