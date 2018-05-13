<?php

namespace WMDE\VueJsTemplating\IntegrationTest;

use DirectoryIterator;
use DOMDocument;
use DOMNode;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\VueJsTemplating\Templating;

class FixtureTest extends TestCase {

	/**
	 * @test
	 * @dataProvider provideFixtures
	 */
	public function phpRenderingEqualsVueJsRendering( $template, array $data, $expectedResult ) {
		$templating = new Templating();
		$filters = [
			'message' => 'strval',
			'directionality' => function () {
				return 'auto';
			}
		];

		$result = $templating->render( $template, $data, $filters );

		$this->assertEqualHtml( $expectedResult, $result );
	}

	public function provideFixtures() {
		$fixtureDir = __DIR__ . '/fixture';

		$cases = [];

		/** @var DirectoryIterator $fileInfo */
		foreach ( new DirectoryIterator( $fixtureDir ) as $fileInfo ) {
			if ( $fileInfo->isDot() ) {
				continue;
			}

			$document = new DOMDocument();
			// Ignore all warnings issued by DOMDocument when parsing
			// as soon as VueJs template is not actually a "valid" HTML
			/** @noinspection UsageOfSilenceOperatorInspection */
			// @codingStandardsIgnoreLine
			@$document->loadHTMLFile( $fileInfo->getPathname() );

			$template = $this->getContents( $document, 'template' );
			$data = json_decode( $this->getContents( $document, 'data' ), true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new RuntimeException( 'JSON parse error: ' . json_last_error_msg()
					. ' in "#data" block in file ' . $fileInfo->getFilename() );
			}

			$result = $this->getContents( $document, 'result' );
			$cases[$fileInfo->getFilename()] = [
				$template,
				$data,
				$result,
			];
		}

		return $cases;
	}

	/**
	 * @param DOMDocument $document
	 * @param string $elementId
	 *
	 * @return string HTML
	 */
	private function getContents( DOMDocument $document, $elementId ) {
		return $this->getInnerHtml( $document->getElementById( $elementId ) );
	}

	private function getInnerHtml( DOMNode $element ) {
		$innerHTML = '';
		$children = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $element->ownerDocument->saveHTML( $child );
		}

		return $innerHTML;
	}

	/**
	 * @param string $expectedResult
	 * @param string $result
	 */
	private function assertEqualHtml( $expectedResult, $result ) {
		$expectedResult = $this->normalizeHtml( $expectedResult );
		$result = $this->normalizeHtml( $result );

		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @param string $html
	 *
	 * @return string HTML
	 */
	private function normalizeHtml( $html ) {
		$html = preg_replace( '/<!--.*?-->/', '', $html );
		$html = preg_replace( '/\s+/', ' ', $html );
		// Trim node text
		$html = str_replace( '> ', ">", $html );
		$html = str_replace( ' <', "<", $html );
		// Each tag (open and close) on a new line
		$html = str_replace( '>', ">\n", $html );
		$html = str_replace( '<', "\n<", $html );
		// Remove duplicated new lines
		$html = str_replace( "\n\n", "\n", $html );

		$html = trim( $html );
		return $html;
	}

}
