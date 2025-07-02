<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

use Peast\Syntax\Node\BinaryExpression as PeastBinaryExpression;
use Peast\Syntax\Node\BooleanLiteral as PeastBooleanLiteral;
use Peast\Syntax\Node\CallExpression;
use Peast\Syntax\Node\Expression;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\MemberExpression;
use Peast\Syntax\Node\NumericLiteral as PeastNumericLiteral;
use Peast\Syntax\Node\ObjectExpression;
use Peast\Syntax\Node\StringLiteral as PeastStringLiteral;
use Peast\Syntax\Node\UnaryExpression;

use RuntimeException;

class PeastExpressionConverter {

	/** @var array a map of method names to their implementations in PHP */
	protected array $methods;

	public function __construct( array $methods ) {
		$this->methods = $methods;
	}

	protected function convertUnaryExpression( UnaryExpression $expression ) {
		if ( $expression->getOperator() !== '!' ) {
			throw new RuntimeException( 'Unable to parse unary operator "' . $expression->getOperator() . '"' );
		}
		return new NegationOperator( $this->convertExpression( $expression->getArgument() ) );
	}

	protected function convertCallExpression( CallExpression $expression ) {
		$methodName = $expression->getCallee()->getName();
		if ( !array_key_exists( $methodName, $this->methods ) ) {
			throw new RuntimeException( "Method '{$methodName}' is undefined" );
		}
		$method = $this->methods[$methodName];

		return new MethodCall(
			$method,
			array_map( fn ( $exp ) => $this->convertExpression( $exp ), $expression->getArguments() )
		);
	}

	protected function convertMemberExpression( MemberExpression $expression ) {
		$parts = [];
		while ( $expression !== null ) {
			if ( get_class( $expression ) === MemberExpression::class ) {
				$property = $expression->getProperty()->getName();
				array_unshift( $parts, $property );
				$expression = $expression->getObject();
			} elseif ( get_class( $expression ) === Identifier::class ) {
				array_unshift( $parts, $expression->getName() );
				$expression = null;
			} else {
				throw new RuntimeException(
					'Unable to parse member expression with nodes of type ' . get_class( $expression )
				);
			}
		}
		return new VariableAccess( $parts );
	}

	protected function convertObjectExpression( ObjectExpression $expression ) {
		$parsedExpressionMap = [];
		foreach ( $expression->getProperties() as $property ) {
			$parsedExpressionMap[ $this->convertKeyToLiteral( $property->getKey() ) ] =
				$this->convertExpression( $property->getValue() );
		}
		return new JsDictionary( $parsedExpressionMap );
	}

	protected function convertBinaryExpression( PeastBinaryExpression $expression ) {
		$lexp = $this->convertExpression( $expression->getLeft() );
		$rexp = $this->convertExpression( $expression->getRight() );
		return new BinaryExpression( $lexp, $rexp, $expression->getOperator() );
	}

	public function convertExpression( Expression $expression ) {
		return match( get_class( $expression ) ) {
			UnaryExpression::class => $this->convertUnaryExpression( $expression ),
			MemberExpression::class => $this->convertMemberExpression( $expression ),
			PeastStringLiteral::class => new StringLiteral( $expression->getValue() ),
			Identifier::class => new VariableAccess( [ $expression->getName() ] ),
			CallExpression::class => $this->convertCallExpression( $expression ),
			ObjectExpression::class => $this->convertObjectExpression( $expression ),
			PeastBooleanLiteral::class => new BooleanLiteral( $expression->getValue() ),
			PeastNumericLiteral::class => new NumericLiteral( $expression->getValue() ),
			PeastBinaryExpression::class => $this->convertBinaryExpression( $expression ),
			default => throw new RuntimeException(
				'Unable to parse complex expression of type ' . get_class( $expression )
			)
		};
	}

	protected function convertKeyToLiteral( $key ) {
		return match( get_class( $key ) ) {
			PeastStringLiteral::class => $key->getValue(),
			Identifier::class => $key->getName(),
			default => throw new RuntimeException(
				'Unable to extract name from dictionary key of type ' . get_class( $key )
			)
		};
	}

}
