<?php

namespace WMDE\VueJsTemplating\Test\FilterExpressionParsing;

use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\FilterExpressionParsing\FilterCall;
use WMDE\VueJsTemplating\FilterExpressionParsing\ParseResult;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;

class ParseResultTest extends TestCase {

	private $defaultFilters;

	protected function setUp() {
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

	/**
	 * @test
	 */
	public function toExpression_SingleExpressionWithoutFilters_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult( [ "'a'" ], [] );

		$result = $parseResult->toExpression( $expressionParser, [] )->evaluate( [] );

		$this->assertEquals( 'a', $result );
	}

	/**
	 * @test
	 */
	public function toExpression_SingleExpressionWithFilter_CreatesThisExpression() {
		$expressionParser = new BasicJsExpressionParser();
		$parseResult = new ParseResult( [ "'a'" ], [ new FilterCall( 'duplicate', [] ) ] );

		$result = $parseResult->toExpression( $expressionParser, $this->defaultFilters )->evaluate( [] );

		$this->assertEquals( 'aa', $result );
	}

	/**
	 * @test
	 */
	public function toExpression_SingleExpressionWithTwoFilters_CreatesThisExpression() {
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

	/**
	 * @test
	 */
	public function toExpression_SingleExpressionWithFiltersHavingArguments_CreatesThisExpression() {
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

	/**
	 * @test
	 */
	public function toExpression_TwoExpressionWithFilter_CreatesThisExpression() {
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
