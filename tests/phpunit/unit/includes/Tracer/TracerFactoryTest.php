<?php
namespace MediaWiki\Tracer;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Tracer\TracerFactory
 */
class TracerFactoryTest extends MediaWikiUnitTestCase {
	private MockHandler $mockHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->mockHandler = new MockHandler();
	}

	private function getTracerFactory( array $configOverrides = [] ): TracerFactory {
		$defaultConfig = [
			'samplingProbability' => 0.0,
			'serviceName' => 'mediawiki-test',
			'endpoint' => 'http://jaeger-test/v1/traces'
		];
		return new TracerFactory(
			new Client( [ 'handler' => $this->mockHandler ] ),
			new ServiceOptions( TracerFactory::CONSTRUCTOR_OPTIONS, [
				MainConfigNames::OpenTelemetryConfig => $configOverrides + $defaultConfig
			] )
		);
	}

	public function testShouldExportNoSpanDataWhenNotSampled(): void {
		$tracerFactory = $this->getTracerFactory();
		$tracer = $tracerFactory->getTracer();

		for ( $i = 1; $i <= 100; $i++ ) {
			$span = $tracer->spanBuilder( "test span #$i" )->startSpan();
			$span->end();
		}

		$tracerFactory->shutdown();

		$this->assertNull( $this->mockHandler->getLastRequest() );
	}

	public function testShouldExportSpanDataWhenSampled(): void {
		$this->mockHandler->append( new Response( 200, [], '' ) );
		$tracerFactory = $this->getTracerFactory( [ 'samplingProbability' => 1.0 ] );
		$tracer = $tracerFactory->getTracer();

		$spanNames = [];

		for ( $i = 1; $i <= 100; $i++ ) {
			$name = "test span #$i";
			$span = $tracer->spanBuilder( $name )->startSpan();
			$spanNames[] = $name;
			$span->end();
		}

		$tracerFactory->shutdown();

		$request = $this->mockHandler->getLastRequest();

		$this->assertNotNull( $request );
		$this->assertSame( 'http://jaeger-test/v1/traces', (string)$request->getUri() );

		$exported = (string)$request->getBody();
		foreach ( $spanNames as $name ) {
			$this->assertStringContainsString( $name, $exported );
		}
	}
}
