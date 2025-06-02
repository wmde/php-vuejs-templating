<?php

namespace WMDE\VueJsTemplating\Test;

use DOMElement;
use Exception;
use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\HtmlParser;

/**
 * @covers \WMDE\VueJsTemplating\HtmlParser
 */
class HtmlParserTest extends TestCase {

	private function parseAndGetRootNode( string $html ): DOMElement {
		$htmlParser = new HtmlParser();
		$document = $htmlParser->parseHtml( $html );
		return $htmlParser->getRootNode( $document );
	}

	private function assertIsDivTest( DOMElement $element ): void {
		$this->assertSame( 'div', $element->tagName );
		$this->assertSame( 'test', $element->getAttribute( 'class' ) );
	}

	public function testSingleRootNode(): void {
		$rootNode = $this->parseAndGetRootNode( '<div class="test"></div>' );
		$this->assertIsDivTest( $rootNode );
	}

	public function testEmptyDocument(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Empty document' );
		$this->parseAndGetRootNode( '' );
	}

	public function testTwoRootNodes() {
		$this->expectException( Exception::class );
		$this->parseAndGetRootNode( '<p></p><p></p>' );
	}

}
