<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating;

use DOMElement;
use Exception;
use WMDE\VueJsTemplating\JsParsing\BasicJsExpressionParser;
use WMDE\VueJsTemplating\JsParsing\CachingExpressionParser;
use WMDE\VueJsTemplating\JsParsing\JsExpressionParser;

class App {

	/** @var HtmlParser */
	private $htmlParser;

	/** @var JsExpressionParser */
	private $expressionParser;

	/** @var (Component|string|callable)[] */
	private $components = [];

	/**
	 * @param callable[] $methods The available methods.
	 * The key is the method name, the value is the corresponding callable.
	 */
	public function __construct( array $methods ) {
		$this->htmlParser = new HtmlParser();
		$this->expressionParser = new CachingExpressionParser( new BasicJsExpressionParser( $methods ) );
	}

	/**
	 * Register the template for a component.
	 *
	 * @param string $name The component name.
	 * @param string|callable $template Either the template HTML as a string,
	 * or a callable that will return the template HTML as a string when called with no arguments.
	 * @return void
	 */
	public function registerComponentTemplate( string $name, $template ): void {
		$this->components[$name] = $template;
	}

	public function evaluateExpression( string $expression, array $data ) {
		return $this->expressionParser->parse( $expression )
			->evaluate( $data );
	}

	public function renderComponent( string $componentName, array $data ): string {
		$rendered = $this->renderComponentToDOM( $componentName, $data );
		return $rendered->ownerDocument->saveHTML( $rendered );
	}

	public function renderComponentToDOM( string $componentName, array $data ): DOMElement {
		return $this->getComponent( $componentName )
			->render( $data );
	}

	private function getComponent( string $componentName ): Component {
		$component = $this->components[$componentName] ?? null;
		if ( $component === null ) {
			throw new Exception( "Unknown component: $componentName" );
		}

		if ( !( $component instanceof Component ) ) {
			if ( is_callable( $component ) ) {
				$html = $component();
			} else {
				$html = $component;
			}
			/** @var string $html */
			$document = $this->htmlParser->parseHtml( $html );
			$rootNode = $this->htmlParser->getRootNode( $document );
			$component = new Component( $rootNode, $this );
			$this->components[$componentName] = $component;
		}

		return $component;
	}

}
