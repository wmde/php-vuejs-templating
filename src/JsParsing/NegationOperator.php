<?php

namespace WMDE\VueJsTemplating\JsParsing;

class NegationOperator implements ParsedExpression {

	/**
	 * @var ParsedExpression
	 */
	private $expression;

	public function __construct( ParsedExpression $expression ) {
		$this->expression = $expression;
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function evaluate( array $data ) {
		return !$this->expression->evaluate( $data );
	}

}
