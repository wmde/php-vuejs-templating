<?php

namespace WMDE\VueJsTemplating\JsParsing;

class StringLiteral implements ParsedExpression {
	/**
	 * @var string
	 */
	private $string;

	public function __construct($string) {
		$this->string = $string;
	}

	/**
	 * @param array $data
	 * @return mixed
	 */
	public function evaluate( array $data ) {
		return $this->string;
	}
}
