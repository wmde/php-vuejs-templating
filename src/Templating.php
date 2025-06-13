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
		$app = new App( $methods );
		$app->registerComponentTemplate( 'root', $template );
		return $app->renderComponent( 'root', $data );
	}

}
