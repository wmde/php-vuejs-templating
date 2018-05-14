<?php

namespace WMDE\VueJsTemplating\FilterExpressionParsing;

use RuntimeException;
use WMDE\VueJsTemplating\JsParsing\FilterApplication;
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
	 * @return string[]
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
	 * @param callable[] $filters Indexed by name
	 *
	 * @throws RuntimeException when one of the requested filters is not known
	 * @return ParsedExpression
	 */
	public function toExpression( JsExpressionParser $expressionParser, array $filters ) {
		if ( $this->filterCalls === [] ) {
			return $expressionParser->parse( $this->expressions[0] );
		}

		$nextFilterArguments = $this->parseExpressions( $expressionParser, $this->expressions );

		$result = null;
		foreach ( $this->filterCalls as $filterCall ) {
			if ( !array_key_exists( $filterCall->filterName(), $filters ) ) {
				throw new RuntimeException( "Filter '{$filterCall->filterName()}' is undefined" );
			}
			$filter = $filters[$filterCall->filterName()];
			$filerArguments = array_merge(
				$nextFilterArguments,
				$this->parseExpressions( $expressionParser, $filterCall->arguments() )
			);

			$result = new FilterApplication( $filter, $filerArguments );
			$nextFilterArguments = [ $result ];
		}

		return $result;
	}

	/**
	 * @param JsExpressionParser $expressionParser
	 * @param string[] $expressions
	 *
	 * @return ParsedExpression[]
	 */
	private function parseExpressions( JsExpressionParser $expressionParser, array $expressions ) {
		return array_map(
			function ( $exp ) use ( $expressionParser ) {
				return $expressionParser->parse( $exp );
			},
			$expressions
		);
	}

}
