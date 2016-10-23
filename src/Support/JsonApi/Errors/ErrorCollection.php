<?php 

namespace Support\JsonApi\Errors;

use Illuminate\Support\MessageBag;
use Neomerx\JsonApi\Exceptions\ErrorCollection as BaseErrorCollection;


class ErrorCollection extends BaseErrorCollection
{
    /**
     * @param MessageBag      $messages
     * @param array|null      $attributeMap
     * @param string|int|null $status
     *
     * @return $this
     */
    public function addAttributeErrorsFromMessageBag(MessageBag $messages, array $attributeMap = null, $status = null)
    {
        foreach ($messages->getMessages() as $attribute => $attrMessages) {
            $name = $attributeMap === null ? $attribute : $attributeMap[$attribute];
            foreach ($attrMessages as $message) {
                $this->addDataAttributeError($name, $message, null, $status);
            }
        }

        return $this;
    }
}
