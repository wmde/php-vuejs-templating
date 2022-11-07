<?php

namespace WMDE\VueJsTemplating\Test\JsParsing;

use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;

/**
 * @covers \WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser
 */
class BasicJsExpressionParserTest extends TestCase {

	public function testCanParseString() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "'some string'" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

	public function testCanParsePropertyAccess() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "variable.property" );
		$result = $parsedExpression->evaluate( [ 'variable' => [ 'property' => 'some value' ] ] );

		$this->assertEquals( 'some value', $result );
	}

	public function testCanParseNegationOperator() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$negation = $jsExpressionEvaluator->parse( "!variable" );

		$this->assertTrue( $negation->evaluate( [ 'variable' => false ] ) );
		$this->assertFalse( $negation->evaluate( [ 'variable' => true ] ) );
	}

	public function testCanParseMethodCall_builtin() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => 'strtoupper',
		] );

		$parsedExpression = $jsExpressionEvaluator->parse( 'strtoupper(var)' );
		$result = $parsedExpression->evaluate( [ 'var' => 'abc' ] );

		$this->assertSame( 'ABC', $result );
	}

	public function testCanParseMethodCall_closure() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => static function ( string $arg ) {
				return strtoupper( $arg );
			},
		] );

		$parsedExpression = $jsExpressionEvaluator->parse( 'strtoupper(var)' );
		$result = $parsedExpression->evaluate( [ 'var' => 'abc' ] );

		$this->assertSame( 'ABC', $result );
	}

	public function testCanParseMethodCall_whitespace() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => 'strtoupper',
		] );

		$parsedExpression = $jsExpressionEvaluator->parse( ' strtoupper( var ) ' );
		$result = $parsedExpression->evaluate( [ 'var' => 'abc' ] );

		$this->assertSame( 'ABC', $result );
	}

	public function testIgnoresTrailingAndLeadingSpaces() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( " 'some string' " );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

}
