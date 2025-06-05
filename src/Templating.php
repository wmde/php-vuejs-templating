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
		$htmlParser = new HtmlParser();
		$document = $htmlParser->parseHtml( $template );
		$rootNode = $htmlParser->getRootNode( $document );
		$component = new Component( $rootNode, $methods );
		return $component->render( $data );
	}

}
