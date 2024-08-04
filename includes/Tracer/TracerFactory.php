<?php
namespace MediaWiki\Tracer;

use GuzzleHttp\Psr7\HttpFactory;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use Psr\Http\Client\ClientInterface;

/**
 * Factory class for obtaining OTEL tracer instances.
 * @since 1.43
 */
class TracerFactory {
	public const CONSTRUCTOR_OPTIONS = [ MainConfigNames::OpenTelemetryConfig ];

	/**
	 * PSR HTTP client instance used to export span data.
	 * @var ClientInterface
	 */
	private ClientInterface $httpClient;
	private ServiceOptions $serviceOptions;
	private ?TracerProvider $tracerProvider = null;

	public function __construct( ClientInterface $httpClient, ServiceOptions $serviceOptions ) {
		$serviceOptions->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->httpClient = $httpClient;
		$this->serviceOptions = $serviceOptions;
	}

	/**
	 * Get an OTEL tracer instance initialized according to local OTEL configuration.
	 * @return TracerInterface
	 */
	public function getTracer(): TracerInterface {
		$otelConfig = $this->serviceOptions->get( MainConfigNames::OpenTelemetryConfig );
		if ( ( $otelConfig['samplingProbability'] ?? 0.0 ) === 0.0 ) {
			return new NoopTracer();
		}

		if ( $this->tracerProvider === null ) {
			$this->tracerProvider = $this->initTracerProvider();
		}

		return $this->tracerProvider->getTracer( 'io.opentelemetry.contrib.php' );
	}

	/**
	 * Shut down associated tracer instances.
	 */
	public function shutdown(): void {
		if ( $this->tracerProvider !== null ) {
			$this->tracerProvider->shutdown();
		}
	}

	/**
	 * Initialize an OTEL TracerProvider based on local OTEL configuration.
	 * @return TracerProvider
	 */
	private function initTracerProvider(): TracerProvider {
		$otelConfig = $this->serviceOptions->get( MainConfigNames::OpenTelemetryConfig );

		// Use a specialized transport factory to avoid triggering expensive PSR autodiscovery logic on every request.
		$psrHttpFactory = new HttpFactory();
		$httpTransportFactory = new PsrTransportFactory(
			$this->httpClient,
			$psrHttpFactory,
			$psrHttpFactory
		);

		// Note: JSON encoding does not work as of opentelemetry-php 0.0.17 (collector rejects it)
		$transport = $httpTransportFactory->create( $otelConfig['endpoint'], 'application/x-protobuf' );
		$exporter = new SpanExporter( $transport );

		$clock = ClockFactory::getDefault();

		// Respect any sampling decision taken by samplers earlier in the trace (if any),
		// and fall back to configurable probabilistic sampling otherwise.
		$sampler = new ParentBased( new TraceIdRatioBasedSampler( $otelConfig['samplingProbability'] ) );

		$attributes = Attributes::create( array_filter( [
			ResourceAttributes::SERVICE_NAME => $otelConfig['serviceName'],
			ResourceAttributes::HOST_NAME => gethostname(),
			"server.socket.address" => $_SERVER['SERVER_ADDR'] ?? null,
		] ) );
		$resource = ResourceInfo::create( $attributes, ResourceAttributes::SCHEMA_URL );

		return new TracerProvider( new BatchSpanProcessor( $exporter, $clock ), $sampler, $resource );
	}
}
