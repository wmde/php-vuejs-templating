<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

class NumericLiteral implements ParsedExpression {

	/**
	 * @var int|float
	 */
	private $value;

	/**
	 * @param int|float $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * @param array $data ignored
	 *
	 * @return value as provided on construction time
	 */
	public function evaluate( array $data ) {
		return $this->value;
	}

}
