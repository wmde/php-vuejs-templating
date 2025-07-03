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
			!( is_string( $rval ) || is_numeric( $rval ) || is_bool( $rval ) ) ||
			!( is_string( $lval ) || is_numeric( $lval ) || is_bool( $lval ) )
		) {
			throw new RuntimeException(
				'BooleanExpression must compare strings or numbers. Got ' .
					gettype( $lval ) . ' and ' . gettype( $rval )
			);
		}
		return match ( $this->operator ) {
			'===' => $lval === $rval,
			'==' => $lval == $rval,
			'>=' => $lval >= $rval,
			'>' => $lval > $rval,
			'<=' => $lval <= $rval,
			'<' => $lval < $rval,
			'!=' => $lval != $rval,
			default => throw new RuntimeException( 'Unknown operator in BooleanExpression: "' . $this->operator . '"' )
		};
	}

}
