<?php

namespace WMDE\VueJsTemplating\FilterExpressionParsing;

/**
 * This represents both calls to a filter and calls to a method.
 */
class FilterCall {

	/**
	 * @var string
	 */
	private $filterName;

	/**
	 * @var string[]
	 */
	private $arguments = [];

	/**
	 * @param string $filterName
	 * @param string[] $arguments
	 */
	public function __construct( $filterName, array $arguments ) {
		$this->filterName = $filterName;
		$this->arguments = $arguments;
	}

	/**
	 * @return string
	 */
	public function filterName() {
		return $this->filterName;
	}

	/**
	 * @return string[]
	 */
	public function arguments() {
		return $this->arguments;
	}

}
