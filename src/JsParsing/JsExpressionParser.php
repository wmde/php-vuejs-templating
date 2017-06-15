<?php

namespace WMDE\VueJsTemplating\JsParsing;

class JsExpressionParser {

	/**
	 * @param string $expression
	 * @return ParsedExpression
	 */
	public function parse( $expression ) {
		if ( strpos( $expression, '!' ) === 0 ) { // ! operator application
			return new NegationOperator( $this->parse( substr( $expression, 1 ) ) );
		} elseif ( strpos( $expression, "'" ) === 0 ) {
			return new StringLiteral( substr( $expression, 1, strlen( $expression ) - 2 ) );
		} else {
			$parts = explode( '.', $expression );
			return new VariableAccess( $parts );
		}
	}
}
