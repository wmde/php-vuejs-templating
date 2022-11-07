<?php

namespace WMDE\VueJsTemplating\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\Templating;

/**
 * @covers \WMDE\VueJsTemplating\Templating
 */
class TemplatingTest extends TestCase {

	public function testJustASingleEmptyHtmlElement() {
		$result = $this->createAndRender( '<div></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasTwoRootNodes_ThrowsAnException() {
		$this->expectException( Exception::class );
		$this->createAndRender( '<p></p><p></p>', [] );
	}

	public function testTemplateHasOnClickHandler_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click="doStuff"></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasOnClickHandlerAndPreventDefault_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click.prevent="doStuff"></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasOnClickHandlerInSomeChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><a v-on:click="doStuff"></a></p>', [] );

		$this->assertSame( '<p><a></a></p>', $result );
	}

	public function testTemplateHasOnClickHandlerInGrandChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><b><a v-on:click="doStuff"></a></b></p>', [] );

		$this->assertSame( '<p><b><a></a></b></p>', $result );
	}

	public function testTemplateWithSingleMustacheVariable_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => 'some value' ] );

		$this->assertSame( '<p>some value</p>', $result );
	}

	public function testTemplateWithVariableAndDiacritcsInValue_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => 'inglés' ] );

		$this->assertSame( '<p>inglés</p>', $result );
	}

	public function testTemplateWithVariableAndValueInKorean_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => '한국어' ] );

		$this->assertSame( '<p>한국어</p>', $result );
	}

	public function testTemplateWithVhtmlVariable_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>some value</p>' ]
		);

		$this->assertSame( '<div><div><p>some value</p></div></div>', $result );
	}

	public function testTemplateWithVhtmlAndDiacritcsInValue_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>inglés</p>' ]
		);

		$this->assertSame( '<div><div><p>inglés</p></div></div>', $result );
	}

	public function testTemplateWithVhtmlAndValueInKorean_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>한국어</p>' ]
		);

		$this->assertSame( '<div><div><p>한국어</p></div></div>', $result );
	}

	public function testTemplateWithMustacheVariable_VariableIsUndefined_ThrowsException() {
		$this->expectException( Exception::class );
		$this->createAndRender( '<p>{{value}}</p>', [] );
	}

	public function testTemplateWithMethod_MethodIsUndefined_ThrowsException() {
		$this->expectException( Exception::class );
		$this->createAndRender( '<p>{{nonexistentMethod(value)}}</p>', [ 'value' => 'some value' ] );
	}

	public function testTemplateWithMustacheVariableSurroundedByText_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>before {{value}} after</p>', [ 'value' => 'some value' ] );

		$this->assertSame( '<p>before some value after</p>', $result );
	}

	public function testTemplateWithMustacheHavingStringLiteral_JustPrintString() {
		$result = $this->createAndRender( "<p>before {{'string'}} after</p>", [] );

		$this->assertSame( '<p>before string after</p>', $result );
	}

	public function testTemplateWithMustacheMethod_ReplacesVariableWithGivenCallbackReturnValue() {
		$result = $this->createAndRender(
			"<p>{{message('ABC')}}</p>",
			[],
			[
				'message' => function ( $value ) {
					return "some $value message";
				}
			]
		);

		$this->assertSame( '<p>some ABC message</p>', $result );
	}

	public function testTemplateWithTruthfulConditionInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => true ] );

		$this->assertSame( '<p><a></a></p>', $result );
	}

	public function testTemplateWithUntruthfulConditionInIf_IsRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => false ] );

		$this->assertSame( '<p></p>', $result );
	}

	public function testTemplateWithNegatedFalseInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="!variable"></a></p>', [ 'variable' => false ] );

		$this->assertSame( '<p><a></a></p>', $result );
	}

	public function testTemplateWithIfElseBlockAndTruthfulCondition_ElseIsRemoved() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => true ]
		);

		$this->assertSame( '<p><a>if</a></p>', $result );
	}

	public function testTemplateWithIfElseBlockAndNontruthfulCondition_ElseIsDisplayed() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => false ]
		);

		$this->assertSame( '<p><a>else</a></p>', $result );
	}

	public function testTemplateWithForLoopAndEmptyArrayToIterate_NotRendered() {
		$result = $this->createAndRender( '<p><a v-for="item in list"></a></p>', [ 'list' => [] ] );

		$this->assertSame( '<p></p>', $result );
	}

	public function testTemplateWithForLoopAndSingleElementInArrayToIterate_RenderedOnce() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1 ] ]
		);

		$this->assertSame( '<p><a></a></p>', $result );
	}

	public function testTemplateWithForLoopAndMultipleElementsInArrayToIterate_RenderedMultipleTimes() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		$this->assertSame( '<p><a></a><a></a></p>', $result );
	}

	public function testTemplateWithForLoopMustache_RendersCorrectValues() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list">{{item}}</a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		$this->assertSame( '<p><a>1</a><a>2</a></p>', $result );
	}

	public function testTemplateWithAttributeBinding_ConditionIsFalse_AttributeIsNotRendered() {
		$result = $this->createAndRender( '<p :attr1="condition"></p>', [ 'condition' => false ] );

		$this->assertSame( '<p></p>', $result );
	}

	public function testTemplateWithAttributeBinding_ConditionIsTrue_AttributeIsRendered() {
		$result = $this->createAndRender(
			'<p :disabled="condition"></p>',
			[ 'condition' => true ]
		);

		$this->assertSame( '<p disabled></p>', $result );
	}

	// phpcs:ignore Generic.Files.LineLength.TooLong
	public function testTemplateWithAttributeBinding_ConditionIsString_AttributeIsRenderedWithThatString() {
		//TODO Rename variable name
		$result = $this->createAndRender(
			'<p :attr1="condition"></p>',
			[ 'condition' => 'some string' ]
		);

		$this->assertSame( '<p attr1="some string"></p>', $result );
	}

	public function testTemplateWithPropertyAccessInMustache_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p>{{var.property}}</p>',
			[ 'var' => [ 'property' => 'value' ] ]
		);

		$this->assertSame( '<p>value</p>', $result );
	}

	public function testTemplateWithMethodAccessInAttributeBinding_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p :attr1="strtoupper(var.property)"></p>',
			[ 'var' => [ 'property' => 'value' ] ],
			[ 'strtoupper' => 'strtoupper' ]
		);

		$this->assertSame( '<p attr1="VALUE"></p>', $result );
	}

	// phpcs:ignore Generic.Files.LineLength.TooLong
	public function testTemplateWithMethodAccessInAttributeBindingInsideTheLoop_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p><a v-for="item in items" :attr1="strtoupper(item.property)"></a></p>',
			[ 'items' => [
				[ 'property' => 'value1' ],
				[ 'property' => 'value2' ],
			] ],
			[ 'strtoupper' => 'strtoupper' ]
		);

		$this->assertSame( '<p><a attr1="VALUE1"></a><a attr1="VALUE2"></a></p>', $result );
	}

	/**
	 * @param string $template HTML
	 * @param array $data
	 * @param callable[] $methods
	 *
	 * @return string
	 */
	private function createAndRender( $template, array $data, array $methods = [] ) {
		$templating = new Templating();
		return $templating->render( $template, $data, $methods );
	}

	/**
	 * @todo Cover following cases
	 *
	 * Valid template:
	 * <div>
	 * 		<div v-if="condition == 1">if body</div>
	 * 		<div v-else-if="condition == 2">v-else-if body</div>
	 * 		<div v-else>else body</div>
	 * </div>
	 *
	 * Invalid template [Error compiling template: v-else used on element <div> without
	 * corresponding v-if]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		<span>something</span>
	 * 		<div v-else>else body</div>
	 * </div>
	 *
	 * Invalid template [Error compiling template: v-else used on element <div> without
	 * corresponding v-if]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		<div>
	 * 			<div v-else>else body</div>
	 * 		</div>
	 * </div>
	 *
	 * Invalid template [Error compiling template: text "something" between v-if and v-else(-if)
	 * will be ignored.]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		something
	 * 		<div v-else>else body</div>
	 * </div>
	 *
	 */

}
