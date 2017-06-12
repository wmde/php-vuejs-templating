<?php

namespace WMDE\VueJsTemplating;

class Templating {

	public function render( $template, array $data, array $filters = [] ) {
		$component = new Component( $template, $filters );
		return $component->render( $data );
	}


}
