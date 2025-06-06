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

	public function testSingleFileComponent_OnlyTemplate(): void {
		$rootNode = $this->parseAndGetRootNode( '<template><div class="test"></div></template>' );
		$this->assertIsDivTest( $rootNode );
	}

	public function testSingleFileComponent_TemplateAndScriptAndStyle(): void {
		$template = '<template><div class="test"></div></template><script></script><style></style>';
		$rootNode = $this->parseAndGetRootNode( $template );
		$this->assertIsDivTest( $rootNode );
	}

	public function testSingleFileComponent_ScriptAndTemplateAndStyle(): void {
		$template = '<script></script><template><div class="test"></div></template><style></style>';
		$rootNode = $this->parseAndGetRootNode( $template );
		$this->assertIsDivTest( $rootNode );
	}

	public function testEmptyDocument(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Empty document' );
		$this->parseAndGetRootNode( '' );
	}

	public function testHeadElement(): void {
		$html = '<html><head><title>Title</title></head><body>ABC</body></html>';
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Expected exactly 1 <html> child, got 2' );
		$this->parseAndGetRootNode( $html );
	}

	public function testTwoRootNodes() {
		$this->expectException( Exception::class );
		$this->parseAndGetRootNode( '<p></p><p></p>' );
	}

	public function testMalformedHtml(): void {
		$htmlParser = new HtmlParser();
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unexpected end tag' );
		$htmlParser->parseHtml( '</p>' );
	}

}
