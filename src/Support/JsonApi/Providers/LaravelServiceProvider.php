<?php namespace Support\JsonApi\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcherInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeadersCheckerInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Http\Request as RequestWrapper;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use Support\JsonApi\Contracts\Core\FactoryInterface;
use Support\JsonApi\Contracts\Core\SchemaContainerInterface;
use Support\JsonApi\Errors\JsonApiExceptionHandler;
use Support\JsonApi\Http\JsonApiRequest;
use Support\JsonApi\Http\Responses;
use Support\JsonApi\Core\DocumentDecoder;
use Support\JsonApi\Core\Factory;
use Support\JsonApi\Settings\Settings as S;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use Psr\Log\LoggerInterface;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LaravelServiceProvider extends ServiceProvider
{

    /** Config file name without extension */
    const CONFIG_FILE_NAME_WO_EXT = 'jsonapi';

    /**
     * A key for setting JSON API supported extensions in routing groups.
     */
    const SUPPORTED_EXTENSIONS = 'json-api-ext';

    /**
     * @inheritdoc
     */
    protected $defer = false;

    /**
     * @var bool|array
     */
    private $config = false;

    /**
     * @var bool|Request
     */
    private $request = false;

    /**
     * @var bool|RequestWrapper
     */
    private $requestWrapper = false;

    /**
     * @var bool|Factory
     */
    private $factory = false;

    /**
     * @var bool|SchemaContainerInterface
     */
    private $schemaContainer = false;

    /**
     * @var bool|EncoderOptions
     */
    private $encoderOptions = false;

    /**
     * @var bool|CodecMatcherInterface
     */
    private $codecMatcher = false;

    /**
     * @var bool|HeadersCheckerInterface
     */
    private $headersChecker = false;

    /**
     * @var bool|EncodingParametersInterface
     */
    private $queryParameters = false;

    /**
     * @var bool|HeaderParametersInterface
     */
    private $headerParameters = false;

    /**
     * @var bool|SupportedExtensionsInterface
     */
    private $supportedExtensions = false;

    /**
     * @var bool|ResponsesInterface
     */
    private $responses = false;

    /**
     * @var bool|LoggerInterface
     */
    private $logger = false;

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->configureLimoncello();
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR .'config.php' => config_path('jsonapi.php'),
        ]);

        $this->configureJsonApiRequests();
        $this->registerExceptionHandler();
    }

    /**
     * @return void
     */
    protected function configureLimoncello()
    {
        $this->app->singleton(FactoryInterface::class, function () {
            return $this->getFactory();
        });

        $this->app->singleton(SchemaContainerInterface::class, function () {
            return $this->getSchemaContainer();
        });
        $this->app->singleton(CodecMatcherInterface::class, function () {
            return $this->getCodecMatcher();
        });

        $this->app->singleton(HeadersCheckerInterface::class, function () {
            return $this->getHeadersChecker();
        });

        $this->app->singleton(EncodingParametersInterface::class, function () {
            return $this->getQueryParameters();
        });

        $this->app->singleton(HeaderParametersInterface::class, function () {
            return $this->getHeaderParameters();
        });

        $this->app->singleton(ResponsesInterface::class, function () {
            return $this->getResponses();
        });
    }

    /**
     * @return void
     */
    protected function configureJsonApiRequests()
    {
        /** @var EventsDispatcherInterface $events */
        $events = $this->app->make(EventsDispatcherInterface::class);
        $this->app->resolving(JsonApiRequest::class, function ($request, $app) {
            // do not replace with $this->getRequest()
            // in tests when more than 1 request it will be executed more than once.
            // if replaced tests will fail
            $currentRequest = $app->make(Request::class);
            $files          = $currentRequest->files->all();
            $files          = is_array($files) === true ? array_filter($files) : $files;

            $request->initialize(
                $currentRequest->query->all(),
                $currentRequest->request->all(),
                $currentRequest->attributes->all(),
                $currentRequest->cookies->all(),
                $files,
                $currentRequest->server->all(),
                $currentRequest->getContent()
            );

            $request->setUserResolver($currentRequest->getUserResolver());
            $request->setRouteResolver($currentRequest->getRouteResolver());
            $currentRequest->getSession() === null ?: $request->setSession($currentRequest->getSession());
            $request->setJsonApiFactory($this->getFactory());
            $request->setQueryParameters($this->getQueryParameters());
            $request->setSchemaContainer($this->getSchemaContainer());
        });
    }

    /**
     * Register exception handler.
     */
    protected function registerExceptionHandler()
    {
        $previousHandler = null;
        if ($this->app->bound(ExceptionHandlerInterface::class) === true) {
            $previousHandler = $this->app->make(ExceptionHandlerInterface::class);
        }

        $this->app->singleton(ExceptionHandlerInterface::class, function () use ($previousHandler) {
            return new JsonApiExceptionHandler($this->app, $previousHandler);
        });
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if ($this->config === false) {
            $this->config = $this->app['config']->get(static::CONFIG_FILE_NAME_WO_EXT, []);
        }
        return $this->config;
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        if ($this->request === false) {
            $this->request = $this->app->make(Request::class);
        }

        return $this->request;
    }

    /**
     * @return RequestWrapper
     */
    protected function getRequestWrapper()
    {
        if ($this->requestWrapper === false) {
            $getMethod = function () {
                $method = $this->getRequest()->getMethod();
                return $method;
            };
            $getHeader = function ($name) {
                $header = $this->getRequest()->headers->get($name, null, false);
                return $header;
            };
            $getQueryParams = function () {
                $queryParams = $this->getRequest()->query->all();
                return $queryParams;
            };

            $this->requestWrapper = new RequestWrapper($getMethod, $getHeader, $getQueryParams);
        }

        return $this->requestWrapper;
    }

    /**
     * @return Factory
     */
    protected function getFactory()
    {
        if ($this->factory === false) {
            $this->factory = new Factory();
        }

        return $this->factory;
    }

    /**
     * @return SchemaContainerInterface
     */
    protected function getSchemaContainer()
    {
        if ($this->schemaContainer === false) {
            $schemes = $this->getSchemes();
            $this->schemaContainer = $this->getFactory()->createContainer($schemes);
        }

        return $this->schemaContainer;
    }

    /**
     * @return EncoderOptions
     */
    protected function getEncoderOptions()
    {
        if ($this->encoderOptions === false) {
            $config        = $this->getConfig();
            $schemaAndHost = $this->getRequest()->getSchemeAndHttpHost();
            $options       = $this->getValue($config, S::JSON, S::JSON_OPTIONS, S::JSON_OPTIONS_DEFAULT);
            $depth         = $this->getValue($config, S::JSON, S::JSON_DEPTH, S::JSON_DEPTH_DEFAULT);
            $prefix =   ($this->getRequest()->route() && $this->getRequest()->route()->getPrefix()) ? '/' . $this->getRequest()->route()->getPrefix() : '';
            $urlPrefix     = $schemaAndHost . $this->getValue($config, S::JSON, S::JSON_URL_PREFIX, null) . $prefix; 
            $this->encoderOptions = new EncoderOptions($options, $urlPrefix, $depth);
        }

        return $this->encoderOptions;
    }

    /**
     * @return CodecMatcherInterface
     */
    protected function getCodecMatcher()
    {
        if ($this->codecMatcher === false) {
            $config    = $this->getConfig();
            $container = $this->getSchemaContainer();
            $factory   = $this->getFactory();
            $matcher   = $factory->createCodecMatcher();

            $decoderClosure  = function () {
                return new DocumentDecoder();
            };
            $encoderClosure  = function () use ($factory, $container, $config) {
                $showVerDefault = S::JSON_IS_SHOW_VERSION_DEFAULT;
                $isShowVer      = $this->getValue($config, S::JSON, S::JSON_IS_SHOW_VERSION, $showVerDefault);
                $versionMeta    = $this->getValue($config, S::JSON, S::JSON_VERSION_META, null);
                $encoderOptions = $this->getEncoderOptions();
                $encoder        = $factory->createEncoder($container, $encoderOptions);

                $isShowVer === false ?: $encoder->withJsonApiVersion($versionMeta);

                return $encoder;
            };
            $jsonApiType     = $factory->createMediaType(
                MediaTypeInterface::JSON_API_TYPE,
                MediaTypeInterface::JSON_API_SUB_TYPE
            );
            $jsonApiTypeUtf8 = $factory->createMediaType(
                MediaTypeInterface::JSON_API_TYPE,
                MediaTypeInterface::JSON_API_SUB_TYPE,
                ['charset' => 'UTF-8']
            );
            $matcher->registerEncoder($jsonApiType, $encoderClosure);
            $matcher->registerDecoder($jsonApiType, $decoderClosure);
            $matcher->registerEncoder($jsonApiTypeUtf8, $encoderClosure);
            $matcher->registerDecoder($jsonApiTypeUtf8, $decoderClosure);

            $this->codecMatcher = $matcher;
        }

        return $this->codecMatcher;
    }

    /**
     * @return HeadersCheckerInterface
     */
    protected function getHeadersChecker()
    {
        if ($this->headersChecker === false) {
            $this->headersChecker = $this->getFactory()->createHeadersChecker($this->getCodecMatcher());
        }

        return $this->headersChecker;
    }

    /**
     * @return EncodingParametersInterface
     */
    protected function getQueryParameters()
    {
        if ($this->queryParameters === false) {
            $this->queryParameters = $this->getFactory()
                ->createQueryParametersParser()->parse($this->getRequestWrapper());
        }

        return $this->queryParameters;
    }

    /**
     * @return HeaderParametersInterface
     */
    protected function getHeaderParameters()
    {
        if ($this->headerParameters === false) {
            $this->headerParameters = $this->getFactory()
                ->createHeaderParametersParser()->parse($this->getRequestWrapper());
        }

        return $this->headerParameters;
    }

    /**
     * @return SupportedExtensionsInterface
     */
    protected function getSupportedExtensions()
    {
        if ($this->supportedExtensions === false) {
            $supportedExtStr = MediaTypeInterface::NO_EXT;
            if ($this->app->bound(Router::class) === true) {
                /** @var Router $router */
                $router = $this->app->make(Router::class);
                if ($router->current() !== null) {
                    $action = $router->current()->getAction();
                    if (array_key_exists(self::SUPPORTED_EXTENSIONS, $action) === true &&
                        is_string($value = $action[self::SUPPORTED_EXTENSIONS])
                    ) {
                        $supportedExtStr = $value;
                    }
                }
            }
            $this->supportedExtensions = $this->getFactory()->createSupportedExtensions($supportedExtStr);
        }

        return $this->supportedExtensions;
    }

    /**
     * @return ResponsesInterface
     */
    protected function getResponses()
    {
        if ($this->responses === false) {
            $matcher = $this->getCodecMatcher();
            $matcher->matchEncoder($this->getHeaderParameters()->getAcceptHeader());

            $this->responses = new Responses(
                $this->getQueryParameters(),
                $matcher->getEncoderRegisteredMatchedType(),
                $this->getSupportedExtensions(),
                $matcher->getEncoder(),
                $this->getSchemaContainer(),
                $this->getEncoderOptions()->getUrlPrefix()
            );
        }
        return $this->responses;
    }

    /**
     * @return LoggerInterface
     */
    private function getLog()
    {
        if ($this->logger === false) {
            $this->logger = $this->app->make(LoggerInterface::class);
        }

        return $this->logger;
    }

    /**
     * @param array  $array
     * @param string $key1
     * @param string $key2
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getValue(array $array, $key1, $key2, $default)
    {
        return isset($array[$key1][$key2]) === true ? $array[$key1][$key2] : $default;
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getValue1d(array $array, $key, $default)
    {
        return isset($array[$key]) === true ? $array[$key] : $default;
    }

    private function getSchemes()
    {
        $schemes = array();
        $config = $this->getConfig();
        $schemas = $this->getValue1d($config, S::SCHEMAS_PATH, []);
        //get default path schemas
        $finder = new Finder();
        if (is_dir($schemas['path'])) {
            $finder->files()->in($schemas['path'])->name('*.php');
            foreach ($finder as $file) {
                $schemes[] = $this->getSchemaClass($file, $schemas['namespace']);
            }
        }

       //get extra schemas
        
       $extra = $schemas['extra'];
       $finder = new Finder();

       if (is_dir($extra['path'])) {
           $finder->files()->in($extra['path'] . '\/*\/' . $extra['schemas']);
           foreach ($finder as $file) {
                $path = $file->getPathInfo()->getPathName();
                $namespace = substr($path, (strlen($extra['path']) + 1));
                $namespace = $extra['namespace'] . substr($namespace, 0, strpos($namespace, '/' . $extra['schemas'])) . '\\' . $extra['schemas'] . '\\';
               $schemes[] = $this->getSchemaClass($file, $namespace);
           }
       }

       return $schemes;
    }

    private function getSchemaClass(SplFileInfo $file, $namespace)
    {
        return $namespace . basename($file->getRelativePathname(), '.php');
    }
}
