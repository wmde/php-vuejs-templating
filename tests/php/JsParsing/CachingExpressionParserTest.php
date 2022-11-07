<?php

namespace WMDE\VueJsTemplating\Test\JsParsing;

use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;
use WMDE\VueJsTemplating\JsParsing\CachingExpressionParser;
use WMDE\VueJsTemplating\JsParsing\JsExpressionParser;
use WMDE\VueJsTemplating\JsParsing\StringLiteral;

/**
 * @covers \WMDE\VueJsTemplating\JsParsing\CachingExpressionParser
 */
class CachingExpressionParserTest extends TestCase {

	public function testParse_CallsInternalParserAndReturnsItsResult() {
		$expectedExpression = new StringLiteral( 'some string' );

		$internalParser = $this->createMock( JsExpressionParser::class );
		$internalParser->expects( $this->once() )
			->method( 'parse' )
			->with( "'some string'" )
			->willReturn( $expectedExpression );
		$cachingExpressionParser = new CachingExpressionParser( $internalParser );

		$result = $cachingExpressionParser->parse( "'some string'" );

		$this->assertSame( $expectedExpression, $result );
	}

	public function testParse_SameExpression_GetExactlySameObject() {
		$cachingExpressionParser = new CachingExpressionParser( new BasicJsExpressionParser( [] ) );

		$expression1 = $cachingExpressionParser->parse( "'some string'" );
		$expression2 = $cachingExpressionParser->parse( "'some string'" );

		$this->assertSame( $expression1, $expression2 );
	}

	public function testParse_IgnoresSurroundingSpaces_GetExactlySameObject() {
		$cachingExpressionParser = new CachingExpressionParser( new BasicJsExpressionParser( [] ) );

		$expression1 = $cachingExpressionParser->parse( "'some string'" );
		$expression2 = $cachingExpressionParser->parse( " 'some string' " );

		$this->assertSame( $expression1, $expression2 );
	}

}
