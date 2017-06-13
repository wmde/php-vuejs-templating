<?php

namespace WMDE\VueJsTemplating;

class Component {

	/**
	 * @var string
	 */
	private $template;

	/**
	 * @var callable[]
	 */
	private $filters = [];

	/**
	 * @param string $template
	 * @param callable[] $filters
	 */
	public function __construct( $template, array $filters ) {
		$this->template = $template;
		$this->filters = $filters;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	public function render( array $data ) {
		$document = $this->parseHtml( $this->template );

		$rootNode = $this->getRootNode( $document );
		$this->handleNode( $rootNode, $data );

		return $document->saveHTML( $rootNode );
	}

	/**
	 * @param $template
	 * @return \DOMDocument
	 */
	private function parseHtml( $template ) {
		$internalErrors = libxml_use_internal_errors( true );
		$document = new \DOMDocument();

		//TODO Unicode characters in template will not work correctly. Fix.
		if ( !@$document->loadHTML( $template ) ) {
			//TODO Test failure
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors( $internalErrors );
		foreach ( $errors as $error ) {
			//TODO html5 tags can fail parsing
			//TODO Throw an exception
		}
		return $document;
	}

	/**
	 * @param $document
	 * @return \DOMElement
	 * @throws \Exception
	 */
	private function getRootNode( $document ) {
		$rootNodes = iterator_to_array( $document->documentElement->childNodes->item( 0 )->childNodes );
		if ( count( $rootNodes ) > 1 ) {
			throw new \Exception( 'Template should have only one root node' );
		}
		return $rootNodes[0];
	}

	/**
	 * @param $rootNode
	 * @param array $data
	 * @param array $filters
	 */
	private function handleNode( \DOMNode $node, array $data ) {
		$filters = $this->filters;
		$this->replaceMustacheVariables( $node, $data );
		if ( !$this->isTextNode( $node ) ) {
			$this->stripEventHandlers( $node );
			$this->handleAttributeBinding( $node, $data);
			$this->handleIf( $node->childNodes, $data );
			$this->handleFor( $node, $data, $filters );

			if ( !$this->isRemovedFromTheDom( $node ) ) {
				foreach ( $node->childNodes as $childNode ) {
					$this->handleNode( $childNode, $data );
				}
			}
		}
	}

	/**
	 * @param $node
	 */
	private function stripEventHandlers( \DOMNode $node ) {
		if ( $this->isTextNode( $node ) ) {
			return;
		}
		/** @var \DOMAttr $attribute */
		foreach ( $node->attributes as $attribute ) {
			if ( strpos( $attribute->name, 'v-on:' ) === 0 ) {
				$node->removeAttribute( $attribute->name );
			}
		}
	}

	/**
	 * @param $node
	 * @param $data
	 */
	private function replaceMustacheVariables( \DOMNode $node, array $data ) {
		if ( $node instanceof \DOMText ) {
			$text = $node->wholeText;

			$regex = '/\{\{
							(?P<expression> [^|]*?)# var name or string literal
							(?: \| (?P<filterName>\w+))?
						\}\}/x';
			preg_match_all( $regex, $text, $matches );

			foreach ( $matches['expression'] as $index => $expression ) {
				$value = $this->evaluateExpression( $expression, $data );

				$filterIsSet = !empty( $matches['filterName'][$index] );
				if ( $filterIsSet ) {
					$filterName = $matches['filterName'][$index];
					$filter = $this->filters[$filterName];
					$value = $filter( $value );
				}

				$text = str_replace( $matches[0][$index], $value, $text );
			}
			if ( $text !== $node->wholeText ) {
				$newNode = $node->ownerDocument->createTextNode( $text );
				$node->parentNode->replaceChild( $newNode, $node );
			}
		}
	}


	private function handleAttributeBinding( \DOMElement $node, array $data ) {
		/** @var \DOMAttr $attribute */
		foreach ( $node->attributes as $attribute ) {
			if ( !preg_match( '/^:[\-\_\w\d]+$/', $attribute->name ) ) {
				continue;
			}

			$expression = $this->evaluateExpression( $attribute->value, $data );
			$name = substr( $attribute->name, 1 );
			if ( is_bool( $expression ) ) {
				if ( $expression ) {
					$node->setAttribute( $name, $name );
				}
			} else {
				$node->setAttribute( $name, $expression );
			}

			$node->removeAttribute( $attribute->name );
		}
	}

	/**
	 * @param $node
	 * @param $data
	 */
	private function handleIf( \DOMNodeList $nodes, array $data ) {
		// Iteration of iterator breaks if we try to remove items while iterating, so defer node removing until finished iterating
		$nodesToRemove = [];
		foreach ( $nodes as $node ) {
			if ( $this->isTextNode( $node ) ) {
				continue;
			}
			/** @var \DOMElement $node */
			if ( $node->hasAttribute( 'v-if' ) ) {
				$conditionString = $node->getAttribute( 'v-if' );
				$node->removeAttribute( 'v-if' );
				$condition = $this->evaluateExpression( $conditionString, $data );

				if ( !$condition ) {
					$nodesToRemove[] = $node;
				}

				$previousIfCondition = $condition;

			} elseif ( $node->hasAttribute( 'v-else' ) ) {
				$node->removeAttribute( 'v-else' );
				if ( $previousIfCondition ) {
					$nodesToRemove[] = $node;
				}
			}
		}

		foreach ( $nodesToRemove as $node ) {
			$this->removeNode( $node );
		}

	}

	private function handleFor( \DOMNode $node, array $data, array $filters ) {
		if ( $this->isTextNode( $node ) ) {
			return;
		}

		/** @var \DOMElement $node */
		if ( $node->hasAttribute( 'v-for' ) ) {
			list( $itemName, $listName ) = explode( ' in ', $node->getAttribute( 'v-for' ) );
			$node->removeAttribute( 'v-for' );

			foreach ( $data[$listName] as $item ) {
				$newNode = $node->cloneNode( true );
				$node->parentNode->insertBefore( $newNode, $node );
				$this->handleNode( $newNode, array_merge( $data, [ $itemName => $item ] ), $filters );
			}

			$this->removeNode( $node );
		}
	}

	/**
	 * @param string $expression
	 * @param array $data
	 * @return bool
	 */
	private function evaluateExpression( $expression, array $data ) {
		if ( strpos( $expression, '!' ) === 0 ) { // ! operator application
			$expression = substr( $expression, 1 );
			$value = !$this->evaluateExpression( $expression, $data);
		} else if (strpos( $expression, "'") === 0) { // string evaluation
			$value = substr( $expression, 1, strlen( $expression ) - 2 );
		} else { // variable evaluation

			$parts = explode( '.', $expression );
			$value = $data;
			foreach ($parts as $key) {
				$value = $value[$key];
			}
		}

		return $value;
	}

	private function removeNode( \DOMElement $node ) {
		$node->parentNode->removeChild( $node );
	}

	/**
	 * @param $node
	 * @return bool
	 */
	private function isTextNode( $node ) {
		return $node instanceof \DOMCharacterData;
	}

	private function isRemovedFromTheDom( \DOMNode $node ) {
		return $node->parentNode === null;
	}

}
