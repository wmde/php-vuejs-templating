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

	/** @var callable[] */
	private $callableComponents = [];

	private $componentSetups = [];

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
	 * @param callable|null $setup An optional setup function.
	 * If set, the callable is called with the array of data used to render the component,
	 * and whichever array it returns is then used to actually render the template.
	 * This can be used, for example, to add additional data (the equivalent of `computed` in JS).
	 * @return void
	 */
	public function registerComponentTemplate( string $name, $template, ?callable $setup = null ): void {
		$this->components[$name] = $template;
		if ( $setup !== null ) {
			$this->componentSetups[$name] = $setup;
		}
	}

	public function registerComponentCallable( string $name, callable $callable ): void {
		$this->callableComponents[$name] = $callable;
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
		$setup = $this->componentSetups[$componentName] ?? null;
		if ( $setup !== null ) {
			$data = $setup( $data );
		}

		$callableComponent = $this->callableComponents[$componentName] ?? null;
		if ( $callableComponent !== null ) {
			$html = $callableComponent( $data );
			$document = $this->htmlParser->parseHtml( $html );
			return $this->htmlParser->getRootNode( $document );
		}

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
