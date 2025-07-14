<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

class ComputedKey implements ParsedExpression {

	private ParsedExpression $expression;

	public function __construct( ParsedExpression $expression ) {
		$this->expression = $expression;
	}

	/**
	 * @param array $data
	 *
	 * @return expression as evaluated in the context of the data
	 */
	public function evaluate( array $data ) {
		return $this->expression->evaluate( $data );
	}

}
