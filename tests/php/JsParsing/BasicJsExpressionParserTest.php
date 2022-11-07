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

	public function testCanParseMethodCall_builtin_variable() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => 'strtoupper',
		] );

		$parsedExpression = $jsExpressionEvaluator->parse( 'strtoupper(var)' );
		$result = $parsedExpression->evaluate( [ 'var' => 'abc' ] );

		$this->assertSame( 'ABC', $result );
	}

	public function testCanParseMethodCall_closure_string() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => static function ( string $arg ) {
				return strtoupper( $arg );
			},
		] );

		$parsedExpression = $jsExpressionEvaluator->parse( "strtoupper('abc')" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertSame( 'ABC', $result );
	}

	public function testCanParseMethodCall_whitespace_nested() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [
			'strtoupper' => 'strtoupper',
			'strrev' => 'strrev',
		] );

		$parsedExpression = $jsExpressionEvaluator->parse(
			' strrev( strtoupper( var ) ) '
		);
		$result = $parsedExpression->evaluate( [ 'var' => 'abc' ] );

		$this->assertSame( 'CBA', $result );
	}

	public function testIgnoresTrailingAndLeadingSpaces() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( " 'some string' " );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

}
