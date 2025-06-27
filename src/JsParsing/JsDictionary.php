<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

class JsDictionary implements ParsedExpression {

	private array $parsedExpressionMap;

	public function __construct( $data ) {
		$this->parsedExpressionMap = $data;
	}

	/**
	 * @param array $data the data to be passed into the expression evaluations
	 *
	 * @return array the dictionary with expressions replaced with their evaluated values
	 */
	public function evaluate( array $data ) {
		$result = [];
		foreach ( $this->parsedExpressionMap as $key => $value ) {
			$result[ $key ] = $value->evaluate( $data );
		}
		return $result;
	}

}
