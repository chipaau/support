<?php 

namespace Support\JsonApi\Http;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Responses as JsonApiResponses;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use Support\JsonApi\Contracts\Core\PagedDataInterface;

class Responses extends JsonApiResponses implements ResponsesInterface
{
    /**
     * @var EncodingParametersInterface
     */
    private $parameters;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var MediaTypeInterface
     */
    private $outputMediaType;

    /**
     * @var SupportedExtensionsInterface
     */
    private $extensions;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var null|string
     */
    private $urlPrefix;

    /**
     * Responses constructor.
     *
     * @param EncodingParametersInterface  $parameters
     * @param MediaTypeInterface           $outputMediaType
     * @param SupportedExtensionsInterface $extensions
     * @param EncoderInterface             $encoder
     * @param ContainerInterface           $container
     * @param string|null                  $urlPrefix
     */
    public function __construct(
        EncodingParametersInterface $parameters,
        MediaTypeInterface $outputMediaType,
        SupportedExtensionsInterface $extensions,
        EncoderInterface $encoder,
        ContainerInterface $container,
        $urlPrefix = null
    ) {
        $this->parameters      = $parameters;
        $this->extensions      = $extensions;
        $this->encoder         = $encoder;
        $this->outputMediaType = $outputMediaType;
        $this->container       = $container;
        $this->urlPrefix       = $urlPrefix;
    }

    /**
     * @inheritdoc
     */
    public function getContentResponse(
        $data,
        $statusCode = JsonApiResponses::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ) {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        return parent::getContentResponse($data, $statusCode, $links, $meta);
    }

    /**
     * @inheritdoc
     */
    public function getPagedDataResponse(PagedDataInterface $data, $statusCode = self::HTTP_OK)
    {
        return $this->getContentResponse($data->getData(), $statusCode, $data->getLinks(), $data->getMeta());
    }

    /**
     * @inheritdoc
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return new Response($content, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @inheritdoc
     */
    protected function getEncodingParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemaContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedExtensions()
    {
        return $this->extensions;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType()
    {
        return $this->outputMediaType;
    }
}
