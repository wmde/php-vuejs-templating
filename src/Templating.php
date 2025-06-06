<?php

namespace WMDE\VueJsTemplating;

class Templating {

	private array $components;

	public function __construct( array $componentTemplates = [] ) {
		$this->components = $componentTemplates;
	}

	/**
	 * @param string $template
	 * @param array $data
	 * @param callable[] $methods
	 *
	 * @return string
	 */
	public function render( $template, array $data, array $methods = [] ) {
		$htmlParser = new HtmlParser( $this->components );
		$document = $htmlParser->parseHtml( $template );
		$rootNode = $htmlParser->getRootNode( $document );
		$component = new Component( $rootNode, $methods );
		return $component->render( $data );
	}

}
