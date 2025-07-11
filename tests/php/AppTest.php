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

	public function testNestedComponents(): void {
		$app = new App( [] );
		$app->registerComponentTemplate( 'root', '<div><x-a :a="rootVar"></x-a></div>' );
		$app->registerComponentTemplate( 'x-a', '<p><x-b :b="a"></x-b></p>' );
		$app->registerComponentTemplate( 'x-b', '<span>{{ b }}</span>' );

		$result = $app->renderComponent( 'root', [ 'rootVar' => 'text' ] );

		$this->assertSame( '<div><p><span>text</span></p></div>', $result );
	}

	public function testNestedComponentObjectProp(): void {
		$app = new App( [] );
		$app->registerComponentTemplate( 'root', '<div><x-a :obj="rootObj"></x-a></div>' );
		$app->registerComponentTemplate( 'x-a', '<p>obj = { a: {{ obj.a }}, b: {{ obj.b }} }</p>' );

		$result = $app->renderComponent( 'root', [
			'rootObj' => [ 'a' => 'A', 'b' => 'B' ],
		] );

		$this->assertSame( '<div><p>obj = { a: A, b: B }</p></div>', $result );
	}

	public function testComponentSubstitutionPreservesOrder(): void {
		$app = new App( [] );
		$app->registerComponentTemplate( 'root', '<div><x-a></x-a><div><p>Following Text</p></div>' );
		$app->registerComponentTemplate( 'x-a', '<p>obj = { a: 1, b: 2 }</p>' );

		$result = $app->renderComponent( 'root', [] );

		$this->assertSame( '<div><p>obj = { a: 1, b: 2 }</p><div><p>Following Text</p></div></div>', $result );
	}

	public function testComponentPropKebabCase(): void {
		$app = new App( [] );
		$app->registerComponentTemplate(
			'root',
			'<div><x-a some-long-prop="A B C"></x-a><x-a :some-long-prop="someLongVar"></x-a></div>'
		);
		$app->registerComponentTemplate( 'x-a', '<p>{{ someLongProp }}</p>' );

		$result = $app->renderComponent( 'root', [ 'someLongVar' => 'X Y Z' ] );

		$this->assertSame( '<div><p>A B C</p><p>X Y Z</p></div>', $result );
	}

	public function testComputedProperties(): void {
		$app = new App( [] );

		$rootTemplate = <<< HTML
<template>
	<div>
		<x-property :property-id="propertyId"></x-property>
	</div>
</template>
<script>
// import ...
module.exports = exports = defineComponent( {
	// name, components, props, ...
	computed: {
		propertyId() {
			return statement.mainsnak.property;
		}
	}
} );
</script>
HTML;
		$app->registerComponentTemplate( 'root', $rootTemplate, function ( array $data ): array {
			$data['propertyId'] = $data['statement']['mainsnak']['property'];
			return $data;
		} );

		$propertyTemplate = <<< HTML
<template>
	<a :href="propertyUrl">{{ propertyLabel }}</a>
</template>
<script>
// import ...
module.exports = exports = defineComponent( {
	// name, props, ...
	computed: {
		propertyUrl() {
			return util.getPropertyUrl( this.propertyId );
		},
		propertyLabel() {
			return labelsStore.getLabel( this.propertyId );
		}
	}
} );
</script>
HTML;
		$app->registerComponentTemplate( 'x-property', $propertyTemplate, function ( array $data ): array {
			$propertyId = $data['propertyId'];
			$data['propertyUrl'] = "https://wiki.example/wiki/Property:$propertyId";
			$data['propertyLabel'] = "property $propertyId";
			return $data;
		} );

		$result = $app->renderComponent( 'root',
			[ 'statement' => [ 'mainsnak' => [ 'property' => 'P123' ] ] ] );

		$expected = <<< HTML
<div>
		<a href="https://wiki.example/wiki/Property:P123">property P123</a></div>
HTML;
		$this->assertSame( $expected, $result );
	}

}
