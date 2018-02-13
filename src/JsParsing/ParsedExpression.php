<?php

namespace WMDE\VueJsTemplating\JsParsing;

interface ParsedExpression {

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function evaluate( array $data );

}
