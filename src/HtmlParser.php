<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating;

use DOMDocument;
use DOMElement;
use Exception;
use LibXMLError;

/**
 * Methods for parsing HTML strings and extracting elements from them.
 */
class HtmlParser {

	/**
	 * Parse the given HTML string into a DOM document.
	 */
	public function parseHtml( string $html ): DOMDocument {
		if ( LIBXML_VERSION < 20900 ) {
			$entityLoaderDisabled = libxml_disable_entity_loader( true );
		}
		$internalErrors = libxml_use_internal_errors( true );
		$document = new DOMDocument( '1.0', 'UTF-8' );

		// Ensure $html is treated as UTF-8, see https://stackoverflow.com/a/8218649
		// LIBXML_NOBLANKS Constant excludes "ghost nodes" to avoid violating
		// vue's single root node constraint
		if ( !$document->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS ) ) {
			//TODO Test failure
		}

		/** @var LibXMLError[] $errors */
		$errors = libxml_get_errors();
		libxml_clear_errors();

		// Restore previous state
		libxml_use_internal_errors( $internalErrors );
		if ( LIBXML_VERSION < 20900 ) {
			libxml_disable_entity_loader( $entityLoaderDisabled );
		}

		foreach ( $errors as $error ) {
			//TODO html5 tags can fail parsing
			//TODO Throw an exception
		}

		return $document;
	}

	/**
	 * Get the root node of the template represented by the given document.
	 */
	public function getRootNode( DOMDocument $document ): DOMElement {
		$rootNodes = $document->documentElement->childNodes->item( 0 )->childNodes;

		if ( $rootNodes->length > 1 ) {
			throw new Exception( 'Template should have only one root node' );
		}

		return $rootNodes->item( 0 );
	}

}
