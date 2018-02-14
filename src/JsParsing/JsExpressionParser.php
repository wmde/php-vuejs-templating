<?php

namespace WMDE\VueJsTemplating\JsParsing;

interface JsExpressionParser {

	/**
	 * @param string $expression
	 *
	 * @return ParsedExpression
	 */
	public function parse( $expression );

}
