<?php

namespace WMDE\VueJsTemplating\Test\FilterExpressionParsing;

use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\FilterExpressionParsing\FilterCall;
use WMDE\VueJsTemplating\FilterExpressionParsing\ParseResult;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;

/**
 * @covers \WMDE\VueJsTemplating\FilterExpressionParsing\ParseResult
 */
class ParseResultTest extends TestCase {

	private $defaultFilters;

	protected function setUp(): void {
		$this->defaultFilters = [
			'duplicate' => function ( $str ) {
				return $str . $str;
			},
			'concat' => function () {
				$result = '';
				foreach ( func_get_args() as $arg ) {
					$result .= $arg;
				}
				return $result;
			}
		];

		parent::setUp();
	}

	public function testToExpression_SingleExpressionWithoutFilters_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult( [ "'a'" ], [] );

		$result = $parseResult->toExpression( $expressionParser, [] )->evaluate( [] );

		$this->assertEquals( 'a', $result );
	}

	public function testToExpression_SingleExpressionWithFilter_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult( [ "'a'" ], [ new FilterCall( 'duplicate', [] ) ] );

		$result = $parseResult->toExpression( $expressionParser, $this->defaultFilters )->evaluate( [] );

		$this->assertEquals( 'aa', $result );
	}

	public function testToExpression_SingleExpressionWithTwoFilters_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult(
			[ "'a'" ],
			[
				new FilterCall( 'duplicate', [] ),
				new FilterCall( 'duplicate', [] )
			]
		);

		$result = $parseResult->toExpression( $expressionParser, $this->defaultFilters )->evaluate( [] );

		$this->assertEquals( 'aaaa', $result );
	}

	public function testToExpression_SingleExpressionWithFiltersHavingArgs_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult(
			[ "'a'" ],
			[
				new FilterCall( 'concat', [ "'b'" ] ),
				new FilterCall( 'concat', [ "'c'" ] )
			]
		);

		$result = $parseResult->toExpression( $expressionParser, $this->defaultFilters )->evaluate( [] );

		$this->assertEquals( 'abc', $result );
	}

	public function testToExpression_TwoExpressionWithFilter_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult(
			[ "'a'", "'b'" ],
			[
				new FilterCall( 'concat', [] ),
			]
		);

		$result = $parseResult->toExpression( $expressionParser, $this->defaultFilters )->evaluate( [] );

		$this->assertEquals( 'ab', $result );
	}

}
