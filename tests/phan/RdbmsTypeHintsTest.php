<?php

// phpcs:disable

/*
 * Some RDBMS interfaces have methods with rather complicated Phan annotations. This test ensures
 * that future versions of Phan still handle them correctly, and that future developers don't mess
 * them up.
 *
 * If Phan reports new issues or unused suppressions in this file, DO NOT just fix the errors,
 * and instead make sure that your patch is not breaking the annotations.
 */

die( 'This file should never be loaded' );

class RdbmsTypeHintsTest {
	function testExpr( \Wikimedia\Rdbms\IDatabase $db ) {
		$conds = [];

		$expr = $db->expr( 'a', '=', 1 );
		// Return value of ->and() etc. must be used
		// @phan-suppress-next-line PhanPluginUseReturnValueKnown
		$expr->and( 'a', '=', 1 );

		// We accept lots of types, but not all
		// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal
		$conds[] = $db->expr( 'a', '=', new \stdClass );

		// Empty arrays are not allowed
		// @phan-suppress-next-line PhanTypeMismatchArgument
		$conds[] = $db->expr( 'a', '=', [] );

		// Null in arrays is not allowed (unlike in the `$qb->where( 'a' => [ ... ] )` syntax)
		// @phan-suppress-next-line PhanTypeMismatchArgument
		$conds[] = $db->expr( 'a', '=', [ 1, null, 2 ] );
	}
}
