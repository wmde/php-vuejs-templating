<?php

namespace WMDE\VueJsTemplating;

class Templating {

	/**
	 * @param string $template
	 * @param array $data
	 * @param callable[] $methods
	 *
	 * @return string
	 */
	public function render( $template, array $data, array $methods = [] ) {
		$component = new Component( $template, $methods );
		return $component->render( $data );
	}

}
