<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

class BooleanLiteral implements ParsedExpression {

	/**
	 * @var bool value
	 */
	private $value;

	public function __construct( bool $value ) {
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
