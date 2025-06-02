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
		$htmlElement = $this->getHtmlElement( $document );
		$headOrBody = $this->getSoleHeadOrBody( $htmlElement );
		$rootNodeParent = $this->getTemplateElement( $headOrBody ) ?? $headOrBody;
		$rootNode = $this->getOnlySubstantialChild( $rootNodeParent );
		return $rootNode;
	}

	/**
	 * Get the `<html>` element of the given document.
	 */
	public function getHtmlElement( DOMDocument $document ): DOMElement {
		$documentElement = $document->documentElement;
		if ( $documentElement === null ) {
			throw new Exception( 'Empty document' );
		}
		if ( $documentElement->tagName !== 'html' ) {
			throw new Exception( "Expected <html>, got <{$documentElement->tagName}>" );
		}
		return $documentElement;
	}

	/**
	 * Get the `<body>` element of the given document.
	 */
	public function getBodyElement( DOMDocument $document ): DOMElement {
		$htmlElement = $this->getHtmlElement( $document );
		$bodyElement = $htmlElement->childNodes[0];
		if ( $bodyElement->tagName !== 'body' ) {
			throw new Exception( "Expected <body>, got <{$bodyElement->tagName}>" );
		}
		return $bodyElement;
	}

	/**
	 * Get the `<head>` or `<body>` element of the given document,
	 * asserting that it is the only child (cannot have both nor any other children).
	 */
	private function getSoleHeadOrBody( DOMElement $htmlElement ): DOMElement {
		$length = $htmlElement->childNodes->length;
		if ( $length !== 1 ) {
			throw new Exception( "Expected exactly 1 <html> child, got $length" );
		}

		$child = $htmlElement->childNodes[0];
		$tagName = $child->tagName;
		if ( $tagName !== 'head' && $tagName !== 'body' ) {
			throw new Exception( "Expected <head> or <body>, got <$tagName>" );
		}

		return $child;
	}

	/**
	 * Get the `<template>` element of the given `<head>` or `<body>` element,
	 * discarding any adjacent `<script>` or `<style>` elements
	 * if the input is in Single-File Component (SFC) syntax.
	 */
	private function getTemplateElement( DOMElement $rootElement ): ?DOMElement {
		$onlyTemplateElement = null;
		foreach ( $rootElement->childNodes as $node ) {
			if ( $node->nodeType === XML_COMMENT_NODE ) {
				// comment node, ignore
				continue;
			} elseif ( $node->nodeType === XML_TEXT_NODE ) {
				if ( trim( $node->textContent ) === '' ) {
					// whitespace-only text node, ignore
					continue;
				} else {
					// not SFC
					$onlyTemplateElement = null;
					break;
				}
			}
			if ( $node->tagName === 'template' ) {
				if ( $onlyTemplateElement === null ) {
					$onlyTemplateElement = $node;
				} else {
					// more than one <template>, handle as non-SFC and throw error below
					$onlyTemplateElement = null;
					break;
				}
			} elseif ( $node->tagName !== 'script' && $node->tagName !== 'style' ) {
				// top-level tag other than <template>, <script> or <style> => not SFC
				$onlyTemplateElement = null;
				break;
			}
		}
		return $onlyTemplateElement;
	}

	/**
	 * Get the only “substantial” child of the given element.
	 * Ignore any adjacent comments or whitespace-only text nodes
	 * (such as line breaks or indentation).
	 */
	private function getOnlySubstantialChild( DOMElement $element ): DOMElement {
		$onlySubstantialChild = null;
		foreach ( $element->childNodes as $node ) {
			if ( $node->nodeType === XML_COMMENT_NODE ) {
				// comment node, ignore
				continue;
			} elseif ( $node->nodeType === XML_TEXT_NODE && trim( $node->textContent ) === '' ) {
				// whitespace-only text node, ignore
				continue;
			}
			if ( $onlySubstantialChild === null ) {
				$onlySubstantialChild = $node;
			} else {
				throw new Exception( 'Template should only have one root node' );
			}
		}

		if ( $onlySubstantialChild !== null ) {
			return $onlySubstantialChild;
		} else {
			throw new Exception( 'Template contained no root node' );
		}
	}

}
