<?php

namespace WMDE\VueJsTemplating;

class Templating {

	public function render( $template, array $data, array $filters = [] ) {
		$document = $this->parseHtml( $template );

		$rootNode = $this->getRootNode( $document );
		$this->stripEventHandlers( $rootNode );
		$this->replaceMustacheVariables( $rootNode, $data );
		$this->replaceMustacheFilters( $rootNode, $filters );
		$this->handleIf( $rootNode, $data );

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
		if( $node instanceof \DOMText ) {
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

		if( $node instanceof \DOMText ) {
			$text = $node->wholeText;
			if( preg_match_all( $regex, $node->wholeText, $matches ) > 0 ) {
				foreach ( $matches[2] as $index => $filterName ) {
					$value = $matches[1][$index];
					$textToReplace = $matches[0][$index];
					$text = str_replace( $textToReplace, $filters[$filterName]($value), $text );
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
	private function handleIf( \DOMNode $node, array $data ) {
		if ( $node instanceof \DOMCharacterData ) {
			return;
		}

		/** @var \DOMAttr $attribute */
		/** @var \DOMElement $node */
		foreach ( $node->attributes as $attribute ) {
			if ( $attribute->name === 'v-if'  ) {
				$node->removeAttribute( $attribute->name );
				$variableName = $attribute->value;
				if (strpos( $variableName, '!') === 0) {
					$variableName = substr( $variableName, 1 );
					$condition = !$data[$variableName];
				} else {
					$condition = $data[$variableName];
				}

				if ( !$condition ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		foreach ( $node->childNodes as $childNode ) {
			$this->handleIf( $childNode, $data );
		}
	}

}
