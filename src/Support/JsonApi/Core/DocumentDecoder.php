<?php 

namespace Support\JsonApi\Core;

use Neomerx\JsonApi\Contracts\Decoder\DecoderInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Support\JsonApi\Errors\ErrorCollection;
use Support\JsonApi\Core\Decoder\DocumentObject;
use Support\JsonApi\Core\Decoder\RelationshipsObject;
use Support\JsonApi\Core\Decoder\ResourceIdentifierObject;
use Support\JsonApi\Core\Decoder\ResourceObject;

class DocumentDecoder implements DecoderInterface
{
    /**
     * @var ErrorCollection
     */
    private $errors;

    /**
     * @param string $content
     *
     * @return DocumentObject|null
     */
    public function decode($content)
    {
        $result       = null;
        $this->errors = new ErrorCollection();

        $documentAsArray = json_decode($content, true);
        if ($documentAsArray !== null) {
            $result = $this->parseDocument($documentAsArray);

            return $this->errors->count() <= 0 ? $result : null;
        } else {
            return null;
        }
    }

    /**
     * @return ErrorCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $data
     *
     * @return DocumentObject|null
     */
    private function parseDocument(array $data)
    {
        $result = null;

        $dataSegment = $this->getArrayValue($data, DocumentInterface::KEYWORD_DATA, null);
        if (empty($dataSegment) === false && is_array($dataSegment) === true) {
            $isProbablySingle = $this->isProbablySingleIdentity($dataSegment);
            $parsed           = $isProbablySingle === true ?
                $this->parseSinglePrimaryData($dataSegment) :
                $this->parseArrayOfPrimaryData($dataSegment);

            if ($parsed !== null) {
                $result = new DocumentObject($parsed);
            }
        } elseif ($dataSegment !== null) {
            $this->errors->addDataError('Invalid element.');
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return ResourceObject|null
     */
    protected function parseSinglePrimaryData(array $data)
    {
        $result = null;

        list ($type, $idx) = $this->parseTypeAndId($data);
        if (empty($type) === true || is_string($type) === false) {
            $this->errors->addDataTypeError('Element should be non empty string.');
        }

        if ($idx !== null && is_string($idx) === false) {
            $this->errors->addDataIdError('Optional element should be non empty string.');
        }

        $attributes = $this->getArrayValue($data, DocumentInterface::KEYWORD_ATTRIBUTES, []);

        $relationshipsData = $this->getArrayValue($data, DocumentInterface::KEYWORD_RELATIONSHIPS, []);
        $relationships     = $this->parseRelationships($relationshipsData);

        if ($this->errors->count() <= 0) {
            $result = new ResourceObject($type, $idx, $attributes, $relationships);
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return ResourceObject[]|null
     */
    protected function parseArrayOfPrimaryData(array $data)
    {
        $isValid = true;
        $result  = null;
        foreach ($data as $primaryData) {
            $parsed = $this->parseSinglePrimaryData($primaryData);
            if ($parsed === null) {
                $isValid = false;
            } else {
                $result[] = $parsed;
            }
        }

        return $isValid === true ? $result : null;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function parseTypeAndId(array $data)
    {
        $idx  = $this->getArrayValue($data, DocumentInterface::KEYWORD_ID, null);
        $type = $this->getArrayValue($data, DocumentInterface::KEYWORD_TYPE, null);

        return [$type, $idx];
    }

    /**
     * @param array $data
     *
     * @return RelationshipsObject[]|null
     */
    protected function parseRelationships(array $data)
    {
        $result = [];
        foreach ($data as $name => $relationshipData) {
            $dataSegment  = $relationshipData !== null ?
                $this->getArrayValue($relationshipData, DocumentInterface::KEYWORD_DATA, null) : null;

            if ($dataSegment === null || (empty($dataSegment) === true && is_array($dataSegment) === true)) {
                $result[$name] = new RelationshipsObject($dataSegment);
                continue;
            }

            if (is_array($dataSegment) === false) {
                $this->errors->addRelationshipError($name, 'Invalid element.');
                continue;
            }

            if ($this->isProbablySingleIdentity($dataSegment) === true) {
                $parsed = $this->parseSingleIdentityInRelationship($name, $dataSegment);
            } else {
                $parsed = $this->parseArrayOfIdentitiesInRelationship($name, $dataSegment);
            }

            if ($parsed !== null) {
                $result[$name] = new RelationshipsObject($parsed);
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return ResourceIdentifierObject|null
     */
    protected function parseSingleIdentityInRelationship($name, array $data)
    {
        list ($type, $idx) = $this->parseTypeAndId($data);

        $isValid = true;
        if (empty($type) === true || is_string($type) === false) {
            $isValid  = false;
            $this->errors->addRelationshipTypeError($name, 'Element should be non empty string.');
        }
        if ($idx === null || is_string($idx) === false) {
            $isValid = false;
            $this->errors->addRelationshipIdError($name, 'Element should be non empty string.');
        }

        $result = $isValid === true ? new ResourceIdentifierObject($type, $idx) : null;

        return $result;
    }

    /**
     * @param string $currentPath
     * @param array  $data
     *
     * @return ResourceIdentifierObject[]|null
     */
    protected function parseArrayOfIdentitiesInRelationship($currentPath, array $data)
    {
        $result  = [];
        $isValid = true;

        foreach ($data as $typeAndIdPair) {
            $parsed = $this->parseSingleIdentityInRelationship($currentPath, $typeAndIdPair);
            if ($parsed === null) {
                $isValid = false;
            } else {
                $result[] = $parsed;
            }
        }

        return $isValid === true ? $result : null;
    }

    /**
     * @param array      $array
     * @param string|int $key
     * @param mixed      $default
     *
     * @return mixed
     */
    private function getArrayValue(array $array, $key, $default)
    {
        return array_key_exists($key, $array) === true ? $array[$key] : $default;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isProbablySingleIdentity(array $data)
    {
        $isProbablySingle = array_key_exists(DocumentInterface::KEYWORD_TYPE, $data) ||
            array_key_exists(DocumentInterface::KEYWORD_ID, $data);

        return $isProbablySingle;
    }
}
