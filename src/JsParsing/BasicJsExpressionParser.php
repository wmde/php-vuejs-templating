<?php

namespace WMDE\VueJsTemplating\JsParsing;

class BasicJsExpressionParser implements JsExpressionParser {

	/**
	 * @param string $expression
	 *
	 * @return ParsedExpression
	 */
	public function parse( $expression ) {
		$expression = $this->normalizeExpression( $expression );
		if ( strpos( $expression, '!' ) === 0 ) { // ! operator application
			return new NegationOperator( $this->parse( substr( $expression, 1 ) ) );
		} elseif ( strpos( $expression, "'" ) === 0 ) {
			return new StringLiteral( substr( $expression, 1, strlen( $expression ) - 2 ) );
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
