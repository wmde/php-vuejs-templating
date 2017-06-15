<?php

namespace WMDE\VueJsTemplating;

class Templating {

	/**
	 * @param string $template
	 * @param array $data
	 * @param callable[] $filters
	 *
	 * @return string
	 */
	public function render( $template, array $data, array $filters = [] ) {
		$component = new Component( $template, $filters );
		return $component->render( $data );
	}

}
