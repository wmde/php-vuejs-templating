<?php

namespace WMDE\VueJsTemplating\Test\JsParsing;

use WMDE\VueJsTemplating\JsParsing\JsExpressionParser;

class JsExpressionParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function canParseString() {
		$jsExpressionEvaluator = new JsExpressionParser();

		$parsedExpression = $jsExpressionEvaluator->parse( "'some string'" );
		$result = $parsedExpression->evaluate( [] );

		$this->assertEquals( 'some string', $result );
	}

	/**
	 * @test
	 */
	public function canParsePropertyAccess() {
		$jsExpressionEvaluator = new JsExpressionParser();

		$parsedExpression = $jsExpressionEvaluator->parse( "variable.property" );
		$result = $parsedExpression->evaluate( [ 'variable' => [ 'property' => 'some value' ] ] );

		$this->assertEquals( 'some value', $result );
	}

	/**
	 * @test
	 */
	public function canParseNegationOperator() {
		$jsExpressionEvaluator = new JsExpressionParser();

		$negation = $jsExpressionEvaluator->parse( "!variable" );

		$this->assertEquals( true, $negation->evaluate( [ 'variable' => false ] ) );
		$this->assertEquals( false, $negation->evaluate( [ 'variable' => true ] ) );
	}

}
