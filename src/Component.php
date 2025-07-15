<?php

namespace WMDE\VueJsTemplating;

use DOMAttr;
use DOMCharacterData;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use RuntimeException;

class Component {

	/**
	 * @var DOMElement
	 */
	private $rootNode;

	/**
	 * @var DOMNode An arbitrary node to reparent cloned root nodes too,
	 * so that they can still have a parent node.
	 * (This is required for {@link self::isRemovedFromTheDom()}.)
	 */
	private $cloneOwner;

	/** @var App */
	private $app;

	public function __construct( DOMElement $rootNode, App $app ) {
		$this->rootNode = $rootNode;
		$this->app = $app;

		$this->cloneOwner = $rootNode->ownerDocument->documentElement;
	}

	public function render( array $data ): DOMElement {
		$rootNode = $this->rootNode->cloneNode( true );
		$this->cloneOwner->appendChild( $rootNode );
		$this->handleNode( $rootNode, $data );
		$this->cloneOwner->removeChild( $rootNode );
		return $rootNode;
	}

	/**
	 * @param DOMNode $node
	 * @param array $data
	 */
	private function handleNode( DOMNode $node, array $data ) {
		$this->replaceMustacheVariables( $node, $data );

		if ( !$this->isTextNode( $node ) ) {
			$this->stripEventHandlers( $node );
			$this->handleFor( $node, $data );
			$this->handleRawHtml( $node, $data );

			if ( !$this->isRemovedFromTheDom( $node ) ) {
				if ( !$this->handleComponent( $node, $data ) ) {
					$this->handleAttributeBinding( $node, $data );
					$this->handleConditionalNodes( $node->childNodes, $data );

					foreach ( iterator_to_array( $node->childNodes ) as $childNode ) {
						$this->handleNode( $childNode, $data );
					}
				}
			}
		}
	}

	private function stripEventHandlers( DOMNode $node ) {
		if ( $this->isTextNode( $node ) ) {
			return;
		}
		// Removing items while iterating breaks iteration, so defer attribute removal
		$attributesToRemove = [];
		/** @var DOMAttr $attribute */
		foreach ( $node->attributes as $attribute ) {
			if (
				str_starts_with( $attribute->name, 'v-on:' ) ||
				str_starts_with( $attribute->name, '@' )
			) {
				$attributesToRemove[] = $attribute;
			}
		}
		foreach ( $attributesToRemove as $attribute ) {
			$node->removeAttributeNode( $attribute );
		}
	}

	private function convertDataValueToString( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		}
		return json_encode( $value );
	}

	private function safeModifyChildren( DOMNode $parent, DOMNode $oldNode, array $newNodes, bool $insert = false ) {
		// TODO To work around the double-free, we detach all the children of the parent node and
		// re-attach them in the correct sequence, replacing the target node with our newly-imported
		// node. Once `mwcli` has moved off this outdated version of PHP (T388411) we should be able
		// to remove this workaround. T398821
		$children = [];
		foreach ( iterator_to_array( $parent->childNodes ) as $child ) {
			if ( $child === $oldNode ) {
				$children = array_merge( $children, $newNodes );
			}
			if ( $insert || $child !== $oldNode ) {
				$children[] = $child;
			}
			$child->remove();
		}

		foreach ( $children as $child ) {
			$parent->appendChild( $child );
		}
	}

	private function safeReplaceNode( DOMNode $parent, DOMNode $oldNode, array $newNodes ) {
		$this->safeModifyChildren( $parent, $oldNode, $newNodes, false );
	}

	private function safeInsertBefore( DOMNode $parent, DOMNode $oldNode, array $newNodes ) {
		$this->safeModifyChildren( $parent, $oldNode, $newNodes, true );
	}

	private function replaceNodeWithChildren( DOMNode $node ) {
		$children = iterator_to_array( $node->childNodes );
		$this->safeReplaceNode( $node->parentNode, $node, $children );
	}

	/**
	 * @param DOMNode $node
	 * @param array $data
	 */
	private function replaceMustacheVariables( DOMNode $node, array $data ) {
		if ( $node instanceof DOMText ) {
			$text = $node->textContent;

			$regex = '/\{\{(?P<expression>.*?)\}\}/x';
			preg_match_all( $regex, $text, $matches );

			foreach ( $matches['expression'] as $index => $expression ) {
				$value = $this->app->evaluateExpression( $expression, $data );
				$text = str_replace( $matches[0][$index], $this->convertDataValueToString( $value ), $text );
			}

			if ( $text !== $node->textContent ) {
				$newNode = $node->ownerDocument->createTextNode( $text );
				$node->parentNode->replaceChild( $newNode, $node );
			}
		}
	}

	/** @return bool true if it was a component, false otherwise */
	private function handleComponent( DOMElement $node, array $data ): bool {
		if ( strpos( $node->tagName, '-' ) === false ) {
			return false;
		}
		$componentName = $node->tagName;

		$componentData = [];
		foreach ( $node->attributes as $attribute ) {
			if ( str_starts_with( $attribute->name, ':' ) ) { // TODO also v-bind: ?
				$name = substr( $attribute->name, 1 );
				$value = $this->app->evaluateExpression( $attribute->value, $data );
			} else {
				$name = $attribute->name;
				$value = $attribute->value;
			}
			// template kebab-case -> JS camelCase
			$name = preg_replace_callback( '/-(\w)/', fn ( $m ) => strtoupper( $m[1] ), $name );
			$componentData[$name] = $value;
		}
		$rendered = $this->app->renderComponentToDOM( $componentName, $componentData );
		// TODO use adoptNode() instead of importNode() in PHP 8.3+ (see php-src commit ed6df1f0ad)
		$importNode = $node->ownerDocument->importNode( $rendered, true );
		// TODO An issue in PHP 8.1.21's libxml integration causes a double-free if we replace a node
		// directly with itself. T398821
		if ( $node != $importNode ) {
			$node->replaceWith( $importNode );
		} else {
			$this->safeReplaceNode( $node->parentNode, $node, [ $importNode ] );
		}
		return true;
	}

	private function handleArrayAttributeBinding( DOMElement $node, string $name, array $value ) {
		if ( $name !== 'class' ) {
			throw new RuntimeException( 'Array-valued data invalid for "' . $name . '" attribute' );
		}
		$existingParts = [];
		if ( $node->getAttribute( $name ) ) {
			$existingParts = explode( " ", $node->getAttribute( $name ) );
		}
		if ( array_is_list( $value ) ) {
			$existingParts = array_merge( $existingParts, $value );
		} else {
			foreach ( $value as $key => $addKey ) {
				if ( $addKey ) {
					array_unshift( $existingParts, $key );
				}
			}
		}
		$node->setAttribute( $name, implode( " ", $existingParts ) );
	}

	private function handleAttributeBinding( DOMElement $node, array $data ) {
		/** @var DOMAttr $attribute */
		foreach ( iterator_to_array( $node->attributes ) as $attribute ) {
			if ( !str_starts_with( $attribute->name, ':' ) ) {
				continue;
			}

			$value = $this->app->evaluateExpression( $attribute->value, $data );

			$name = substr( $attribute->name, 1 );
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$node->setAttribute( $name, $name );
				}
			} elseif ( is_array( $value ) ) {
				$this->handleArrayAttributeBinding( $node, $name, $value );
			} else {
				$node->setAttribute( $name, $value );
			}
			$node->removeAttribute( $attribute->name );
		}
	}

	private function handleIf(
		DOMNode $node,
		array $data,
		bool $previousIfCondition,
		array &$nodesToRemove
	): bool {
		$conditionString = $node->getAttribute( 'v-if' );
		$node->removeAttribute( 'v-if' );
		$condition = $this->app->evaluateExpression( $conditionString, $data );

		if ( !$condition ) {
			$nodesToRemove[] = $node;
		}

		return $condition;
	}

	private function handleElseIf(
		DOMNode $node,
		array $data,
		bool $previousIfCondition,
		array &$nodesToRemove
	): bool {
		$conditionString = $node->getAttribute( 'v-else-if' );
		$node->removeAttribute( 'v-else-if' );
		if ( !$previousIfCondition ) {
			$condition = $this->app->evaluateExpression( $conditionString, $data );

			if ( !$condition ) {
				$nodesToRemove[] = $node;
			}
			return $condition;
		}
		$nodesToRemove[] = $node;
		return $previousIfCondition;
	}

	/**
	 * @param DOMNodeList $nodes
	 * @param array $data
	 */
	private function handleConditionalNodes( DOMNodeList $nodes, array $data ) {
		// Iteration of iterator breaks if we try to remove items while iterating, so defer node
		// removing until finished iterating.
		$nodesToRemove = [];
		$previousIfCondition = false;
		foreach ( $nodes as $node ) {
			if ( $this->isTextNode( $node ) ) {
				continue;
			}

			/** @var DOMElement $node */
			if ( $node->hasAttribute( 'v-if' ) ) {
				$previousIfCondition = $this->handleIf( $node, $data, $previousIfCondition, $nodesToRemove );
			} elseif ( $node->hasAttribute( 'v-else-if' ) ) {
				$previousIfCondition = $this->handleElseIf( $node, $data, $previousIfCondition, $nodesToRemove );
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

	private function handleFor( DOMNode $node, array $data ) {
		if ( $this->isTextNode( $node ) ) {
			return;
		}

		/** @var DOMElement $node */
		if ( $node->hasAttribute( 'v-for' ) ) {
			$parentNode = $node->parentNode;
			list( $itemName, $listName ) = explode( ' in ', $node->getAttribute( 'v-for' ) );
			$node->removeAttribute( 'v-for' );
			$node->removeAttribute( ':key' );

			foreach ( $this->app->evaluateExpression( $listName, $data ) as $item ) {
				$newNode = $node->cloneNode( true );
				$this->safeInsertBefore( $parentNode, $node, [ $newNode ] );
				$this->handleNode( $newNode, array_merge( $data, [ $itemName => $item ] ) );
				if ( $newNode->tagName === 'template' ) {
					$this->replaceNodeWithChildren( $newNode );
				}
			}

			$this->removeNode( $node );
		}
	}

	private function appendHTML( DOMNode $parent, $source ) {
		$htmlParser = new HtmlParser();
		$tmpDoc = $htmlParser->parseHtml( $source );
		foreach ( $htmlParser->getBodyElement( $tmpDoc )->childNodes as $node ) {
			$node = $parent->ownerDocument->importNode( $node, true );
			$parent->appendChild( $node );
		}
	}

	private function handleRawHtml( DOMNode $node, array $data ) {
		if ( $this->isTextNode( $node ) ) {
			return;
		}

		/** @var DOMElement $node */
		if ( $node->hasAttribute( 'v-html' ) ) {
			$htmlExpression = $node->getAttribute( 'v-html' );
			$node->removeAttribute( 'v-html' );

			$this->appendHTML( $node, $this->app->evaluateExpression( $htmlExpression, $data ) );
		}
	}

	private function removeNode( DOMElement $node ) {
		if ( $node->parentNode ) {
			$node->parentNode->removeChild( $node );
		}
	}

	/**
	 * @param DOMNode $node
	 *
	 * @return bool
	 */
	private function isTextNode( DOMNode $node ) {
		return $node instanceof DOMCharacterData;
	}

	private function isRemovedFromTheDom( DOMNode $node ) {
		return $node->parentNode === null;
	}

}
