<?php


abstract class MediaWikiUnitTestCase extends PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;
	use PHPUnit4And6Compat;

	/**
	 * $called tracks whether the setUp and tearDown method has been called.
	 * class extending MediaWikiTestCase usually override setUp and tearDown
	 * but forget to call the parent.
	 *
	 * The array format takes a method name as key and anything as a value.
	 * By asserting the key exist, we know the child class has called the
	 * parent.
	 *
	 * This property must be private, we do not want child to override it,
	 * they should call the appropriate parent method instead.
	 */
	private $called = [];
/*
	private $realDbPassword = null;

	protected function setUp() {
		global $wgDBpassword;
		parent::setUp();
		$this->called['setUp'] = true;

		// Hack to make any database connection fail
		$this->realDbPassword = $wgDBpassword;
		$GLOBALS['wgDBpassword'] = 'NotAPassword';
	}

	public function __destruct() {
		// Complain if self::setUp() was called, but not self::tearDown()
		// $this->called['setUp'] will be checked by self::testMediaWikiTestCaseParentSetupCalled()
		if ( isset( $this->called['setUp'] ) && !isset( $this->called['tearDown'] ) ) {
			throw new MWException( static::class . "::tearDown() must call parent::tearDown()" );
		}
	}

	protected function tearDown() {
		global $wgDBpassword;
		$this->called['tearDown'] = true;

		$wgDBpassword = $this->realDbPassword;
		parent::tearDown();
	}*/
}
