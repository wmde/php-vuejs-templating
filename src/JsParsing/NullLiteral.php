<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\JsParsing;

class NullLiteral implements ParsedExpression {

	/**
	 * @param array $data ignored
	 *
	 * @return null
	 */
	public function evaluate( array $data ) {
		return null;
	}

}
