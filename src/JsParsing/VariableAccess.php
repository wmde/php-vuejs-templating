<?php

namespace WMDE\VueJsTemplating\JsParsing;

use RuntimeException;

class VariableAccess implements ParsedExpression {

	/**
	 * @var ParsedExpression[]
	 */
	private $pathParts;

	public function __construct( array $pathParts ) {
		$this->pathParts = $pathParts;
	}

	/**
	 * @param array $data
	 *
	 * @throws RuntimeException when a path element cannot be found in the array
	 * @return mixed
	 */
	public function evaluate( array $data ) {
		$value = $data;
		foreach ( $this->pathParts as $key ) {
			$keyValue = $key->evaluate( $data );
			if ( !array_key_exists( $keyValue, $value ) ) {
				$expression = implode( '.', array_map(
					static fn ( $part ) => $part->evaluate( $data ), $this->pathParts
				) );
				throw new RuntimeException( "Undefined variable '{$expression}'" );
			}
			$value = $value[$keyValue];
		}
		return $value;
	}

}
