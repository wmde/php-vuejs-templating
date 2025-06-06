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
		$app = new App( [ 'root' => $template ], $methods );
		$rendered = $app->renderComponent( 'root', $data );
		return $rendered->ownerDocument->saveHTML( $rendered );
	}

}
