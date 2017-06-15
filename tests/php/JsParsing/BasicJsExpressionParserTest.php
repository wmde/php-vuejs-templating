<?php

namespace WMDE\VueJsTemplating\Test\JsParsing;

use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;

class BasicJsExpressionParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function canParseString() {
		$jsExpressionEvaluator = new BasicJsExpressionParser();

		$parsedExpression = $jsExpressionEvaluator->parse( "'some string'" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

	/**
	 * @test
	 */
	public function canParsePropertyAccess() {
		$jsExpressionEvaluator = new BasicJsExpressionParser();

		$parsedExpression = $jsExpressionEvaluator->parse( "variable.property" );
		$result = $parsedExpression->evaluate( [ 'variable' => [ 'property' => 'some value' ] ] );

		$this->assertEquals( 'some value', $result );
	}

	/**
	 * @test
	 */
	public function canParseNegationOperator() {
		$jsExpressionEvaluator = new BasicJsExpressionParser();

		$negation = $jsExpressionEvaluator->parse( "!variable" );

		$this->assertEquals( true, $negation->evaluate( [ 'variable' => false ] ) );
		$this->assertEquals( false, $negation->evaluate( [ 'variable' => true ] ) );
	}

}
