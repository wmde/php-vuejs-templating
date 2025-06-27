<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

use Peast\Peast;

class BasicJsExpressionParser implements JsExpressionParser {

	private PeastExpressionConverter $expressionConverter;

	public function __construct( array $methods ) {
		$this->expressionConverter = new PeastExpressionConverter( $methods );
	}

	/** @inheritDoc */
	public function parse( $expression ) {
		$pexp = Peast::ES2017( "($expression)" )->parse();
		$body = $pexp->getBody();

		return $this->expressionConverter->convertExpression( $body[0]->getExpression()->getExpression() );
	}

}
