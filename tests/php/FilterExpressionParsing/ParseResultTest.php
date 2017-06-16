<?php

namespace WMDE\VueJsTemplating\Test\FilterExpressionParsing;

use WMDE\VueJsTemplating\FilterExpressionParsing\ParseResult;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;

class ParseResultTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function toExpression_SingleExpressionWithoutFilters_CreatesThisExpression(  ) {

		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult(["'a'"], []);

		$result = $parseResult->toExpression( $expressionParser, [] )->evaluate( [] );

		$this->assertEquals( 'a', $result );
	}

}
