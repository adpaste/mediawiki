<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */
namespace MediaWiki\Tracer;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Utility object to keep an OTEL span alive (and optionally activated) within a specific scope.
 * @since 1.43
 */
class ScopedSpan {
	/** @var SpanInterface */
	private $span;
	/** @var ScopeInterface|null */
	private $scope;

	private function __construct( SpanInterface $span, ?ScopeInterface $scope = null ) {
		$this->span = $span;
		$this->scope = $scope;
	}

	/**
	 * Track the given span, without making it the active span.
	 * The span will be ended once the returned wrapper object goes out of scope.
	 *
	 * @param SpanInterface $span The span to start
	 * @return self RAII wrapper controlling the lifetime of the wrapped span
	 */
	public static function new( SpanInterface $span ): self {
		return new self( $span );
	}

	/**
	 * Track the given span and make it the active span.
	 * The span will be deactivated and ended once the returned wrapper object goes out of scope.
	 *
	 * @param SpanInterface $span The span to start and activate
	 * @return self RAII wrapper controlling the lifetime of the wrapped span
	 */
	public static function newActive( SpanInterface $span ): self {
		return new self(
			$span,
			$span->activate()
		);
	}

	public function __destruct() {
		$this->span->end();
		if ( $this->scope !== null ) {
			$this->scope->detach();
		}
	}
}
