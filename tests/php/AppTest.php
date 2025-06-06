<?php

declare( strict_types = 1 );

namespace WMDE\VueJsTemplating\Test;

use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\App;

/**
 * @covers \WMDE\VueJsTemplating\App
 * @covers \WMDE\VueJsTemplating\Component
 */
class AppTest extends TestCase {

	public function testAppRenderedTwice(): void {
		$app = new App( [] );
		$app->registerComponentTemplate( 'root', '<p>{{ text }}</p>' );

		$result1 = $app->renderComponent( 'root', [ 'text' => 'text 1' ] );
		$this->assertSame( '<p>text 1</p>', $result1 );

		$result2 = $app->renderComponent( 'root', [ 'text' => 'text 2' ] );
		$this->assertSame( '<p>text 2</p>', $result2 );
	}

	public function testAppInitsComponentLazily(): void {
		$app = new App( [] );
		$called = false;
		$app->registerComponentTemplate( 'root', function () use ( &$called ) {
			$called = true;
			return '<p>{{ text }}</p>';
		} );

		$this->assertFalse( $called );
		$result = $app->renderComponent( 'root', [ 'text' => 'TEXT' ] );
		$this->assertSame( '<p>TEXT</p>', $result );
		$this->assertTrue( $called );
	}

}
