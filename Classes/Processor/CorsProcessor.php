<?php

declare(strict_types=1);

namespace SourceBroker\T3api\Processor;

use Psr\Http\Message\ResponseInterface;
use SourceBroker\T3api\Service\CorsService;
use Symfony\Component\HttpFoundation\Request;

class CorsProcessor implements ProcessorInterface
{
    /**
     * @var CorsService
     */
    private $corsService;

    public function __construct(CorsService $corsService)
    {
        $this->corsService = $corsService;
    }

    public function process(Request $request, ResponseInterface $response): void
    {
        if (
            !$this->isCorsRequest($request)
            || $this->isPreflightRequest($request)
        ) {
            return;
        }
		  // @extensionScannerIgnoreLine
        $options = $this->corsService->getOptions();
        $requestOrigin = $request->headers->get('Origin');

        if (!$this->corsService->isAllowedOrigin($requestOrigin, $options)) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $response = $response->withoutHeader('Access-Control-Allow-Origin');
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $response = $response->withHeader(
            'Access-Control-Allow-Origin',
            $requestOrigin
        );

        if ($options->allowCredentials) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($options->exposeHeaders) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $response = $response->withHeader('Access-Control-Expose-Headers', strtolower(implode(', ', $options->exposeHeaders)));
        }
    }

    protected function isCorsRequest(Request $request): bool
    {
        return $request->headers->has('Origin')
            && $request->headers->get('Origin')
            !== $request->getSchemeAndHttpHost();
    }

    protected function isPreflightRequest(Request $request): bool
    {
        return $request->getMethod() === Request::METHOD_OPTIONS;
    }
}
