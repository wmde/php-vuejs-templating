<?php

namespace WMDE\VueJsTemplating\Test\JsParsing;

use Exception;
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

		$parsedExpression = $jsExpressionEvaluator->parse( 'strtoupper(somevar)' );
		$result = $parsedExpression->evaluate( [ 'somevar' => 'abc' ] );

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
			' strrev( strtoupper( somevar ) ) '
		);
		$result = $parsedExpression->evaluate( [ 'somevar' => 'abc' ] );

		$this->assertSame( 'CBA', $result );
	}

	public function testIgnoresTrailingAndLeadingSpaces() {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( " 'some string' " );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

	public function testCanParse_simple_dictionary(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "{ key: testProperty }" );
		$result = $parsedExpression->evaluate( [ 'testProperty' => 1 ] );

		$this->assertSame( [ "key" => 1 ], $result );
	}

	public function testCanParse_nested_values(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "{ key: testObject.testDelegate.testProperty }" );
		$result = $parsedExpression->evaluate( [ 'testObject' => [ 'testDelegate' => [ 'testProperty' => 1 ] ] ] );

		$this->assertSame( [ "key" => 1 ], $result );
	}

	public function testCanParse_dictionary_with_string_keys(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse(
			"{ 'wikibase-mex-icon-expand-x-small': !showReferences.P321, " .
			"'wikibase-mex-icon-collapse-x-small': showReferences.P321 }"
		);
		$result = $parsedExpression->evaluate( [ 'showReferences' => [ 'P321' => false ] ] );

		$this->assertSame( [
			"wikibase-mex-icon-expand-x-small" => true,
			"wikibase-mex-icon-collapse-x-small" => false
		], $result );
	}

	public function testCanParse_boolean_literal(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "false" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertFalse( $result );
	}

	public function testCanParseBinaryExpression_withBools(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "false != true" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertTrue( $result );
	}

	public function testCanParseBinaryExpression_withNumbers(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "3 < 4" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertTrue( $result );
	}

	public function testCanParseBinaryExpression_withStrings(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "'this' == 'that'" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertFalse( $result );
	}

	public function testParseBinaryExpressionWithComplexValues_throwsError(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );
		$this->expectException( Exception::class );

		$parsedExpression = $jsExpressionEvaluator->parse( "3 < myvar" );
		$parsedExpression->evaluate( [ 'myvar' => [ 1, 2, 3 ] ] );
	}

	public function testCanParseBinaryExpression_withNullLiteral(): void {
		$jsExpressionEvaluator = new BasicJsExpressionParser( [] );

		$parsedExpression = $jsExpressionEvaluator->parse( "x !== null" );
		$result = $parsedExpression->evaluate( [ 'x' => [] ] );

		$this->assertTrue( $result );
	}

}
