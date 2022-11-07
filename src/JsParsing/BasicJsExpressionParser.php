<?php

namespace WMDE\VueJsTemplating\JsParsing;

use RuntimeException;

class BasicJsExpressionParser implements JsExpressionParser {

	private $methods;

	public function __construct( array $methods ) {
		$this->methods = $methods;
	}

	/**
	 * @param string $expression
	 *
	 * @return ParsedExpression
	 */
	public function parse( $expression ) {
		$expression = $this->normalizeExpression( $expression );
		if ( strncmp( $expression, '!', 1 ) === 0 ) {
			return new NegationOperator( $this->parse( substr( $expression, 1 ) ) );
		} elseif ( strncmp( $expression, "'", 1 ) === 0 ) {
			return new StringLiteral( substr( $expression, 1, -1 ) );
		} elseif ( preg_match( '/^(\w+)\((.*)\)$/', $expression, $matches ) ) {
			$methodName = $matches[1];
			if ( !array_key_exists( $methodName, $this->methods ) ) {
				throw new RuntimeException( "Method '{$methodName}' is undefined" );
			}
			$method = $this->methods[$methodName];
			$args = [ $this->parse( $matches[2] ) ];
			return new MethodCall( $method, $args );
		} else {
			$parts = explode( '.', $expression );
			return new VariableAccess( $parts );
		}
	}

	/**
	 * @param string $expression
	 *
	 * @return string
	 */
	protected function normalizeExpression( $expression ) {
		return trim( $expression );
	}

}
