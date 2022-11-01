<?php

namespace WMDE\VueJsTemplating;

class Templating {

	/**
	 * @param string $template
	 * @param array $data
	 * @param callable[] $filtersAndMethods
	 *
	 * @return string
	 */
	public function render( $template, array $data, array $filtersAndMethods = [] ) {
		$component = new Component( $template, $filtersAndMethods );
		return $component->render( $data );
	}

}
