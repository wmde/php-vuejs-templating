<?php

namespace WMDE\VueJsTemplating\FilterExpressionParsing;

use WMDE\VueJsTemplating\JsParsing\JsExpressionParser;
use WMDE\VueJsTemplating\JsParsing\ParsedExpression;

class ParseResult {

	/**
	 * @var string[]
	 */
	private $expressions;

	/**
	 * @var FilterCall[]
	 */
	private $filterCalls = [];

	/**
	 * @param string[] $expressions
	 * @param FilterCall[] $filterCalls
	 */
	public function __construct( array $expressions, array $filterCalls ) {
		$this->expressions = $expressions;
		$this->filterCalls = $filterCalls;
	}

	/**
	 * @return \string[]
	 */
	public function expressions() {
		return $this->expressions;
	}

	/**
	 * @return FilterCall[]
	 */
	public function filterCalls() {
		return $this->filterCalls;
	}

	/**
	 * @param JsExpressionParser $expressionParser
	 * @param array $filters
	 * @return ParsedExpression
	 */
	public function toExpression( JsExpressionParser $expressionParser, array $filters ) {
		return $expressionParser->parse( $this->expressions[0] );
	}

}
