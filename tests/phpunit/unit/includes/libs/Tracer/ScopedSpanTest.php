<?php
namespace MediaWiki\Tracer;

use MediaWikiUnitTestCase;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * @covers \MediaWiki\Tracer\ScopedSpan
 */
class ScopedSpanTest extends MediaWikiUnitTestCase {
	private InMemoryExporter $spanExporter;
	private TracerInterface $tracer;

	protected function setUp(): void {
		parent::setUp();

		$this->spanExporter = new InMemoryExporter();

		$tracerProvider = new TracerProvider(
			new SimpleSpanProcessor( $this->spanExporter ),
			new AlwaysOnSampler()
		);

		$this->tracer = $tracerProvider->getTracer( 'test' );
	}

	public function testShouldManageSpanWhileWrappingObjectLives(): void {
		$span = $this->tracer->spanBuilder( 'test' )->startSpan();

		$spanScope = ScopedSpan::new( $span );
		$activeSpan = Span::fromContext( Context::getCurrent() );

		$this->assertCount( 0, $this->spanExporter->getSpans(), 'The span should not have been ended yet' );
		$this->assertFalse( $activeSpan->getContext()->isValid(), 'The span should not have been activated' );

		$spanScope = null;

		$this->assertCount( 1, $this->spanExporter->getSpans(), 'The span should have been ended' );
		$this->assertSame(
			$span->getContext()->getSpanId(),
			$this->spanExporter->getSpans()[0]->getContext()->getSpanId(),
			'The span should have been ended'
		);
	}

	public function testShouldManageAndActivateSpanWhileWrappingObjectLives(): void {
		$span = $this->tracer->spanBuilder( 'test' )->startSpan();

		$spanScope = ScopedSpan::newActive( $span );

		$this->assertCount( 0, $this->spanExporter->getSpans(), 'The span should not have been ended yet' );
		$this->assertTrue( self::getActiveSpan()->getContext()->isValid(), 'The span should have been activated' );
		$this->assertSame(
			$span->getContext()->getSpanId(),
			self::getActiveSpan()->getContext()->getSpanId(),
			'The span should have been activated'
		);

		$spanScope = null;

		$this->assertCount( 1, $this->spanExporter->getSpans(), 'The span should have been ended' );
		$this->assertSame(
			$span->getContext()->getSpanId(),
			$this->spanExporter->getSpans()[0]->getContext()->getSpanId(),
			'The span should have been ended'
		);
		$this->assertFalse( self::getActiveSpan()->getContext()->isValid(), 'The span should have been deactivated' );
	}

	/**
	 * Convenience function to get the current OTEL span.
	 * @return SpanInterface
	 */
	private static function getActiveSpan(): SpanInterface {
		return Span::fromContext( Context::getCurrent() );
	}
}
