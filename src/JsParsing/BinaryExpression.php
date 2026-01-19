<?php
declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

use RuntimeException;

class BinaryExpression implements ParsedExpression {

	private ParsedExpression $left;
	private ParsedExpression $right;
	private string $operator;

	public function __construct( ParsedExpression $left, ParsedExpression $right, string $operator ) {
		$this->left = $left;
		$this->right = $right;
		$this->operator = $operator;
	}

	/**
	 * @param array $data
	 *
	 * @throws RuntimeException
	 * @return bool
	 */
	public function evaluate( array $data ) {
		$lval = $this->left->evaluate( $data );
		$rval = $this->right->evaluate( $data );
		if (
			( is_string( $rval ) || is_numeric( $rval ) || is_bool( $rval ) ) &&
			( is_string( $lval ) || is_numeric( $lval ) || is_bool( $lval ) )
		) {
			return $this->compareScalars( $lval, $rval );
		}
		if ( $lval === null || $rval === null ) {
			return $this->compareNullable( $lval, $rval );
		}
		throw new RuntimeException(
			'BooleanExpression must compare strings, numbers, bools or null. Got ' .
			gettype( $lval ) . ' and ' . gettype( $rval )
		);
	}

	/**
	 * @param string|int|float|bool $lval
	 * @param string|int|float|bool $rval
	 */
	private function compareScalars( $lval, $rval ): bool {
		return match ( $this->operator ) {
			'===' => $lval === $rval,
			'==' => $lval == $rval,
			'>=' => $lval >= $rval,
			'>' => $lval > $rval,
			'<=' => $lval <= $rval,
			'<' => $lval < $rval,
			'!==' => $lval !== $rval,
			'!=' => $lval != $rval,
			default => throw new RuntimeException( 'Unknown operator in BooleanExpression: "' . $this->operator . '"' )
		};
	}

	private function compareNullable( $lval, $rval ): bool {
		return match( $this->operator ) {
			'===' => $lval === $rval,
			'!==' => $lval !== $rval,
			default => throw new RuntimeException( 'Unknown operator in BooleanExpression: "' . $this->operator . '"' )
		};
	}

}
