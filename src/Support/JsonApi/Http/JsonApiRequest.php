<?php 

namespace Support\JsonApi\Http;

use Illuminate\Http\Request;
use Support\Repositories\Repository;
use Support\JsonApi\Errors\ErrorCollection;
use Support\JsonApi\Core\DocumentDecoder;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface as PPI;

abstract class JsonApiRequest extends Request implements ValidatesWhenResolved
{
    /** Related schema class */
    const SCHEMA = null;
    /** Rules index for parameters checker */
    const RULE_ALLOW_UNRECOGNIZED = 0;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_INCLUDE_PATHS = 1;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_FIELD_SET_TYPES = 2;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_SORT_FIELDS = 3;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_PAGING_PARAMS = 4;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_FILTERING_PARAMS = 5;

    /**
     * @var EncodingParametersInterface
     */
    private $requestParameters;

    /**
     * @var SchemaContainerInterface
     */
    private $schemaContainers;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var DocumentObject
     */
    private $jsonApiDocument;

    /**
     * @var bool
     */
    private $isParsed = false;

    /**
     * @var string
     */
    private $type = null;

    /**
     * @var string
     */
    private $idx = null;

    /**
     * @inheritdoc
     */
    protected function getParameterRules()
    {
        $rules = array();
        if (!empty($this->pagingParameters())) {
            $rules[self::RULE_ALLOWED_PAGING_PARAMS] = $this->pagingParameters();
        }

        if (!empty($this->unrecognizedParameters())) {
            $rules[self::RULE_ALLOW_UNRECOGNIZED] = $this->unrecognizedParameters();
        }

        if (!empty($this->includeParameters())) {
            $rules[self::RULE_ALLOWED_INCLUDE_PATHS] = $this->includeParameters();
        }

        if (!empty($this->fieldSetParameters())) {
            $rules[self::RULE_ALLOWED_FIELD_SET_TYPES] = $this->fieldSetParameters();
        }
        
        if (!empty($this->sortFieldParameters())) {
            $rules[self::RULE_ALLOWED_SORT_FIELDS] = $this->sortFieldParameters();
        }

        if (!empty($this->filteringParameters())) {
            $rules[self::RULE_ALLOWED_FILTERING_PARAMS] = $this->filteringParameters();
        }
        
        return $rules;
    }

    /**
     * @param EncodingParametersInterface $requestParameters
     */
    public function setQueryParameters($requestParameters)
    {
        $this->requestParameters = $requestParameters;
    }

    /**
     * @param SchemaContainersInterface $schemaContainer
     */
    public function setSchemaContainer($schemaContainer)
    {
        $this->schemaContainer = $schemaContainer;
    }

    /**
     * @param FactoryInterface $factory
     */
    public function setJsonApiFactory($factory)
    {
        $this->factory = $factory;
    }

    /**
     * @FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     */
    protected function validateParsed()
    {
    }

    /**
     * @return string
     */
    public function getType()
    {
        $this->ensureDocumentIsParsed();
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $errors = new ErrorCollection();
        $this->validateParameters($errors);

        if ($errors->count() > 0) {
            throw new JsonApiException($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @return EncodingParametersInterface
     */
    public function getParameters()
    {
        return $this->requestParameters;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $this->ensureDocumentIsParsed();

        return $this->idx;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $this->ensureDocumentIsParsed();

        return $this->resourceAttr;
    }

    /**
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateParameters(ErrorCollection $errors)
    {
        $rules  = $this->getParameterRules();
        $params = $this->getParameters();
        if ($rules === null && $params->isEmpty() === false) {
            $message = 'Parameters are not supported.';
            empty($params->getFieldSets()) ?: $errors->addQueryParameterError(PPI::PARAM_FIELDS, $message);
            empty($params->getIncludePaths()) ?: $errors->addQueryParameterError(PPI::PARAM_INCLUDE, $message);
            empty($params->getSortParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_SORT, $message);
            empty($params->getPaginationParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_PAGE, $message);
            empty($params->getFilteringParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_FILTER, $message);
        } elseif (is_array($rules) === true && empty($rules) === false) {
            $get = function (array $array, $key, $default) {
                return array_key_exists($key, $array) === true ? $array[$key] : $default;
            };

            $parametersChecker = $this->createParametersChecker(
                $get($rules, self::RULE_ALLOW_UNRECOGNIZED, false),
                $get($rules, self::RULE_ALLOWED_INCLUDE_PATHS, []),
                $get($rules, self::RULE_ALLOWED_FIELD_SET_TYPES, null),
                $get($rules, self::RULE_ALLOWED_SORT_FIELDS, []),
                $get($rules, self::RULE_ALLOWED_PAGING_PARAMS, []),
                $get($rules, self::RULE_ALLOWED_FILTERING_PARAMS, [])
            );
            
            $parametersChecker->checkQuery($params);
        }
    }


    /** @noinspection PhpTooManyParametersInspection
     *
     * @param bool $allowUnrecognized
     * @param array|null $includePaths
     * @param array|null $fieldSets
     * @param array|null $sortFields
     * @param array|null $pagingParameters
     * @param array|null $filteringParameters
     *
     * @return QueryCheckerInterface
     */
    protected function createParametersChecker(
        $allowUnrecognized = false,
        array $includePaths = null,
        array $fieldSets = null,
        array $sortFields = null,
        array $pagingParameters = null,
        array $filteringParameters = null
    ) {
        $parametersChecker = $this->factory->createQueryChecker(
            $allowUnrecognized,
            $includePaths,
            $fieldSets,
            $sortFields,
            $pagingParameters,
            $filteringParameters
        );

        return $parametersChecker;
    }

    /**
     * @return SchemaInterface
     */
    private function getSchema()
    {
        $result = $this->schemaContainer->getSchemaBySchemaClass(static::SCHEMA);
        return $result;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return Container::getInstance();
    }

    /**
     * Ensure document is parsed.
     */
    private function ensureDocumentIsParsed()
    {
        $this->isParsed === true ?: $this->parseDocument();
    }

    /**
     * Parse JSON API document
     */
    private function parseDocument()
    {
        $doc = $this->getDocument();
        if ($doc === null) {
            $this->isParsed = true;
            return;
        }

        $errors = new ErrorCollection();

        if (($data = $doc->getData()) === false) {
            // only single resource is supported in input
            $errors->addDataError("Invalid element");
            throw new JsonApiException($errors);
        }

        $schema        = $this->getSchema();
        $type          = $data->getType();
        $idx           = $data->getIdentifier();
        $attributes    =
            array_intersect_key($data->getAttributes(), $schema->getAttributesMap());

        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->type          = $type;
        $this->idx           = $idx;
        $this->resourceAttr  = $attributes;

        $this->validateParsed();

        $this->isParsed = true;
    }

    /**
     * @return DocumentObject
     */
    private function getDocument()
    {
        if ($this->jsonApiDocument === null) {
            $decoder               = new DocumentDecoder();
            $this->jsonApiDocument = $decoder->decode($this->getContent());
            if ($decoder->getErrors()->count() > 0) {
                throw new JsonApiException($decoder->getErrors());
            }
        }

        return $this->jsonApiDocument;
    }

    protected function pagingParameters()
    {
        return [
            Repository::PARAM_PAGING_SIZE,
            Repository::PARAM_PAGING_NUMBER
        ];
    }

    protected function unrecognizedParameters()
    {
        return array();
    }

    protected function includeParameters()
    {
        return array();
    }

    protected function fieldSetParameters()
    {
        return array();
    }

    protected function sortFieldParameters()
    {
        return array();
    }

    protected function filteringParameters()
    {
        return array();
    }
}
