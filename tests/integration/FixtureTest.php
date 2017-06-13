<?php

namespace WMDE\VueJsTemplating\IntegrationTest;

use WMDE\VueJsTemplating\Templating;

class FixtureTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 * @dataProvider provideFixtures
	 */
	public function phpRenderingEqualsVueJsRendering($template, $data, $expectedResult) {
		$templating = new Templating();
		$filters = [
			'message' => 'strval'
		];

		$result = $templating->render( $template, $data, $filters );

		$this->assertEqualHtml( $expectedResult, $result );
	}

	public function provideFixtures() {
		$fixtureDir = __DIR__ . '/fixture';

		$cases = [];

		/** @var \DirectoryIterator $fileInfo */
		foreach ( new \DirectoryIterator( $fixtureDir ) as $fileInfo ) {
			if ( $fileInfo->isDot() ) {
				continue;
			}

			$DOMDocument = new \DOMDocument();
			@$DOMDocument->loadHTMLFile( $fileInfo->getPathname() );

			$template = $this->getContents( $DOMDocument, 'template' );
			$data = json_decode($this->getContents( $DOMDocument, 'data' ), true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new \RuntimeException(
					'JSON parse error: ' . json_last_error_msg() . ' in "#data" block in file ' . $fileInfo->getFilename()
				);
			}

			$result = $this->getContents( $DOMDocument, 'result' );
			$cases[$fileInfo->getFilename()] = [
				$template,
				$data,
				$result,
			];
		}

		return $cases;
	}

	/**
	 * @param $DOMDocument
	 * @param $elementId
	 * @return mixed
	 */
	private function getContents( $DOMDocument, $elementId ) {
		return $this->getInnerHtml( $DOMDocument->getElementById( $elementId ) );
	}

	private function getInnerHtml( \DOMNode $element ) {
		$innerHTML = "";
		$children = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $element->ownerDocument->saveHTML( $child );
		}

		return $innerHTML;
	}

	/**
	 * @param $expectedResult
	 * @param $result
	 */
	private function assertEqualHtml( $expectedResult, $result ) {
		$expectedResult = $this->normalizeHtml( $expectedResult );
		$result = $this->normalizeHtml( $result );


		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @param $html
	 */
	private function normalizeHtml( $html ) {
		$html = preg_replace( '/<!--.*?-->/', '', $html );
		$html = preg_replace( '/\s+/', ' ', $html );
		$html = str_replace( '> ', ">\n", $html );
		$html = trim( $html );
		return $html;
	}
}
