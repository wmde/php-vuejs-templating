<?php

namespace WMDE\VueJsTemplating\JsParsing;

use RuntimeException;

class MethodCall implements ParsedExpression {

	/**
	 * @var callable
	 */
	private $method;

	/**
	 * @var ParsedExpression[]
	 */
	private $argumentExpressions;

	/**
	 * @param callable $method
	 * @param ParsedExpression[] $argumentExpressions
	 */
	public function __construct( callable $method, array $argumentExpressions ) {
		$this->method = $method;
		$this->argumentExpressions = $argumentExpressions;
	}

	/**
	 * @param array $data
	 *
	 * @throws RuntimeException
	 * @return mixed
	 */
	public function evaluate( array $data ) {
		$arguments = array_map(
			function ( ParsedExpression $e ) use ( $data ) {
				return $e->evaluate( $data );
			},
			$this->argumentExpressions
		);

		return call_user_func_array( $this->method, $arguments );
	}

}
