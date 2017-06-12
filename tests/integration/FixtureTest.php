<?php

namespace WMDE\VueJsTemplating\IntegrationTest;

use WMDE\VueJsTemplating\Templating;

class FixtureTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 * @dataProvider provideFixtures
	 */
	public function fixtureTest($template, $data, $expectedResult) {
		$templating = new Templating();

		$result = $templating->render( $template, $data );

		assertThat( $result, is( equalTo( $expectedResult ) ) );
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
		return $DOMDocument->saveHTML( $DOMDocument->getElementById( $elementId )->childNodes->item( 0 ) );
	}
}
