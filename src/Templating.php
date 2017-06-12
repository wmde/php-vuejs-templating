<?php

namespace WMDE\VueJsTemplating;

class Templating {

	public function render( $template, array $data, array $filters = [] ) {
		$document = $this->parseHtml( $template );

		$rootNode = $this->getRootNode( $document );
		$this->stripEventHandlers( $rootNode );
		$this->replaceMustacheVariables( $rootNode, $data );
		$this->replaceMustacheFilters( $rootNode, $filters );
		$this->handleIf( $rootNode->childNodes, $data );

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
		/** @var \DOMElement $rootNode */
		$rootNode = $rootNodes[0];
		return $rootNode;
	}

	/**
	 * @param $node
	 */
	private function stripEventHandlers( \DOMNode $node ) {
		if ( $node instanceof \DOMCharacterData ) {
			return;
		}
		/** @var \DOMAttr $attribute */
		foreach ( $node->attributes as $attribute ) {
			if ( strpos( $attribute->name, 'v-on:' ) === 0 ) {
				$node->removeAttribute( $attribute->name );
			}
		}

		foreach ( $node->childNodes as $childNode ) {
			$this->stripEventHandlers( $childNode );
		}
	}

	/**
	 * @param $node
	 * @param $data
	 */
	private function replaceMustacheVariables( \DOMNode $node, array $data ) {
		if ( $node instanceof \DOMText ) {
			$text = $node->wholeText;
			foreach ( $data as $key => $value ) {
				$text = str_replace( '{{' . $key . '}}', $value, $text );
			}
			$newNode = $node->ownerDocument->createTextNode( $text );
			$node->parentNode->replaceChild( $newNode, $node );
		}

		if ( $node instanceof \DOMCharacterData ) {
			return;
		}

		foreach ( $node->childNodes as $childNode ) {
			$this->replaceMustacheVariables( $childNode, $data );
		}
	}

	/**
	 * @param $node
	 * @param $filters
	 */
	private function replaceMustacheFilters( \DOMNode $node, array $filters ) {
		$regex = '/\{\{\'([^\']*)\'\|(\w+)\}\}/';

		if ( $node instanceof \DOMText ) {
			$text = $node->wholeText;
			if ( preg_match_all( $regex, $node->wholeText, $matches ) > 0 ) {
				foreach ( $matches[2] as $index => $filterName ) {
					$value = $matches[1][$index];
					$textToReplace = $matches[0][$index];
					$text = str_replace( $textToReplace, $filters[$filterName]( $value ), $text );
				}
			}

			$newNode = $node->ownerDocument->createTextNode( $text );
			$node->parentNode->replaceChild( $newNode, $node );
		}

		if ( $node instanceof \DOMCharacterData ) {
			return;
		}

		foreach ( $node->childNodes as $childNode ) {
			$this->replaceMustacheFilters( $childNode, $filters );
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
			if ( $node instanceof \DOMCharacterData ) {
				continue;
			}
			/** @var \DOMElement $node */
			if ( $node->hasAttribute( 'v-if' ) ) {
				$conditionString = $node->getAttribute('v-if');
				$node->removeAttribute( 'v-if' );
				$condition = $this->evaluateCondition( $conditionString, $data );

				if ( !$condition ) {
					$nodesToRemove[] = $node;
				} else {
					$this->handleIf( $node->childNodes, $data );
				}

				$previousIfCondition = $condition;

			} elseif ( $node->hasAttribute( 'v-else' ) ) {
				$node->removeAttribute( 'v-else' );
				if ( $previousIfCondition ) {
					$nodesToRemove[] = $node;
				} else {
					$this->handleIf( $node->childNodes, $data );
				}
			}
		}

		foreach ( $nodesToRemove as $node ) {
			$this->removeNode( $node );
		}

	}

	/**
	 * @param string $conditionString
	 * @param array $data
	 * @return bool
	 */
	private function evaluateCondition( $conditionString, array $data ) {
		if ( strpos( $conditionString, '!' ) === 0 ) {
			$conditionString = substr( $conditionString, 1 );
			$condition = !$data[$conditionString];
		} else {
			$condition = $data[$conditionString];
		}

		return (bool)$condition;
	}

	private function removeNode( \DOMElement $node ) {
		$node->parentNode->removeChild( $node );
	}

}
