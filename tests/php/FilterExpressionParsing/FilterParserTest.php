<?php

namespace WMDE\VueJsTemplating\Test\FilterExpressionParsing;

use WMDE\VueJsTemplating\FilterExpressionParsing\FilterCall;
use WMDE\VueJsTemplating\FilterExpressionParsing\FilterParser;
use WMDE\VueJsTemplating\FilterExpressionParsing\ParseResult;

class FilterParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function singleVariable() {
		$filterParser = new FilterParser();

		$result = $filterParser->parse( 'var1|filter' );

		$this->assertEquals( new ParseResult( [ 'var1' ], [new FilterCall('filter', [])] ), $result );
	}

	/**
	 * @test
	 * @dataProvider provideParseCases
	 */
	public function parseTest($expression, $expectedResult) {
		$filterParser = new FilterParser();

		$result = $filterParser->parse( $expression );

		$this->assertEquals( $expectedResult, $result );
	}

	public function provideParseCases() {
		return [
			'single variable' => [
				'variable',
				new ParseResult( [ 'variable' ], [] )
			],
			'single string' => [
				'"string"',
				new ParseResult( [ '"string"' ], [] )
			],
			'single number' => [
				'1',
				new ParseResult( [ '1' ], [] )
			],
			'negative number' => [
				'-1',
				new ParseResult( [ '-1' ], [] )
			],
			'negative float' => [
				'-1.23',
				new ParseResult( [ '-1.23' ], [] )
			],
			'true' => [
				'true',
				new ParseResult( [ 'true' ], [] )
			],
			'false' => [
				'false',
				new ParseResult( [ 'false' ], [] )
			],
			'null' => [
				'null',
				new ParseResult( [ 'null' ], [] )
			],
			'array' => [
				'[var1, var2]',
				new ParseResult( [ '[var1, var2]' ], [] )
			],
			'parenthesis' => [
				'(var1 + var2)',
				new ParseResult( [ '(var1 + var2)' ], [] )
			],
			'object' => [
				'{prop1:var1, prop2:var2}',
				new ParseResult( [ '{prop1:var1, prop2:var2}' ], [] )
			],
			'variable with filter having no arguments' => [
				'var1|filter',
				new ParseResult( [ 'var1' ], [new FilterCall('filter', [])] )
			],
			'variable with filter having variable argument' => [
				'var1|filter(var2)',
				new ParseResult( [ 'var1' ], [new FilterCall('filter', ['var2'])] )
			],
			'variable with filter having string argument' => [
				'var1|filter("string")',
				new ParseResult( [ 'var1' ], [new FilterCall('filter', ['"string"'])] )
			]
		];
	}

}
