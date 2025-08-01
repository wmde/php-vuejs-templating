<?php

namespace WMDE\VueJsTemplating\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\Templating;

/**
 * @covers \WMDE\VueJsTemplating\Component
 * @covers \WMDE\VueJsTemplating\HtmlParser
 * @covers \WMDE\VueJsTemplating\Templating
 */
class TemplatingTest extends TestCase {

	public function testJustASingleEmptyHtmlElement() {
		$result = $this->createAndRender( '<div></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testSingleFileComponent(): void {
		$template = <<< 'EOF'
<template>
	<!-- eslint-disable-next-line something -->
	<div></div>
</template>
<script setup>
const something = 'something';
</script>
<style scoped>
.some-class {
	font-weight: bold;
}
</style>
EOF;
		$result = $this->createAndRender( $template, [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasOnClickHandler_RemoveHandlerFromOutput() {
		$result = $this->createAndRender( '<div v-on:click="doStuff"></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasOnClickHandlerAndPreventDefault_RemoveHandlerFromOutput() {
		$result = $this->createAndRender( '<div v-on:click.prevent="doStuff"></div>', [] );

		$this->assertSame( '<div></div>', $result );
	}

	public function testTemplateHasOnClickHandlerInSomeChildNode_RemoveHandlerFromOutput() {
		$result = $this->createAndRender( '<p><a v-on:click="doStuff"></a></p>', [] );

		$this->assertSame( '<p><a></a></p>', $result );
	}

	public function testTemplateHasOnClickHandlerInGrandChildNode_RemoveHandlerFromOutput() {
		$result = $this->createAndRender( '<p><b><a v-on:click="doStuff"></a></b></p>', [] );

		$this->assertSame( '<p><b><a></a></b></p>', $result );
	}

	public function testTemplateHasOnClickHandlerWithShorthand_RemoveHandlerFromOutput(): void {
		$result = $this->createAndRender( '<p @click="x"></p>', [] );

		$this->assertSame( '<p></p>', $result );
	}

	public function testTemplateHasMultipleEventHandlers_RemoveAll(): void {
		$result = $this->createAndRender( '<p v-on:click="x" v-on:keypress="y"></p>', [] );

		$this->assertSame( '<p></p>', $result );
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

	public function testTemplateWithVhtmlVariableNestedData_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value.html"></div></div>',
			[ 'value' => [ 'html' => '<p>some value</p>' ] ]
		);

		$this->assertSame( '<div><div><p>some value</p></div></div>', $result );
	}

	public function testTemplateWithVhtmlVariableAndAttributeBinding_ReplacesBoth(): void {
		$result = $this->createAndRender(
			'<div><div :data-a="a" v-html="html"></div></div>',
			[ 'a' => 'A', 'html' => '<p>HTML</p>' ]
		);

		$this->assertSame( '<div><div data-a="A"><p>HTML</p></div></div>', $result );
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

	public function testTemplateWithElseIfBlockAndTruthfulCondition_IfAndElseRemoved() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else-if="other_variable">else if</a>' .
				'<a v-else>else</a></p>',
			[ 'variable' => false, 'other_variable' => true ]
		);

		$this->assertSame( '<p><a>else if</a></p>', $result );
	}

	public function testTemplateWithElseIfBlockAndTruthfulCondition_OnlyFirstElseIfShows() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else-if="other_variable">else if</a>' .
				'<a v-else-if="still_another">another else</a><a v-else>else</a></p>',
			[ 'variable' => false, 'other_variable' => true, 'still_another' => true ]
		);

		$this->assertSame( '<p><a>else if</a></p>', $result );
	}

	public function testTemplateWithElseIfBlockAndTruthfulIfAndElseIfCondition_IfElseRemoved() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else-if="other_variable">else if</a>' .
				'<a v-else>else</a></p>',
			[ 'variable' => true, 'other_variable' => true ]
		);

		$this->assertSame( '<p><a>if</a></p>', $result );
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

	public function testTemplateWithForLoopUsingTemplateElement_DropsTemplateTags() {
		$result = $this->createAndRender(
			'<p><template v-for="item in list">{{ item }}</template></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		$this->assertSame( '<p>12</p>', $result );
	}

	public function testTemplateWithNestedForLoopUsingTemplateElement_DropsTemplateTags() {
		$result = $this->createAndRender(
			'<p><template v-for="sublist in list">' .
			'<template v-for="item in sublist">{{item}}</template></template></p>',
			[ 'list' => [ [ 1, 2 ], [ 3, 4 ] ] ]
		);

		$this->assertSame( '<p>1234</p>', $result );
	}

	public function testTemplateWithForLoopUsingTemplateElement_RendersMultipleChildren() {
		$result = $this->createAndRender(
			'<dl><template v-for="item in list">' .
			'<dt>{{item.dt}}</dt><dd>{{item.dd}}</dd></template></dl>',
			[ 'list' => [
				[ 'dt' => 'cat', 'dd' => 'a feline animal' ],
				[ 'dt' => 'dog', 'dd' => 'a canine animal' ],
			] ]
		);

		$this->assertSame( '<dl><dt>cat</dt><dd>a feline animal</dd>' .
			'<dt>dog</dt><dd>a canine animal</dd></dl>', $result );
	}

	public function testTemplateWithForLoopAndMemberExpressionForData_RenderedOnce() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list.data"></a></p>',
			[ 'list' => [ 'data' => [ 1 ] ] ]
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

	public function testTemplateWithForLoopAndMultipleElementsInNestedArrayWithStringKeys_ResolvesVariables() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list[\'data-values\']"></a></p>',
			[ 'list' => [ 'data-values' => [ 1, 2 ] ] ]
		);

		$this->assertSame( '<p><a></a><a></a></p>', $result );
	}

	public function testTemplateWithForLoopAndMultipleElementsInNestedIndexedArray_ResolvesVariables() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list[1]"></a></p>',
			[ 'list' => [ [ 3, 4, 5 ], [ 1, 2 ] ] ]
		);

		$this->assertSame( '<p><a></a><a></a></p>', $result );
	}

	public function testForVariableIsAvailableForNestedExpressions() {
		$result = $this->createAndRender(
			'<div><div v-for="index in indexKeys">' .
			'<p>{{ data[index] }}</p>' .
			'</div></div>',
			[ 'indexKeys' => [ 'index1', 'index2' ],
			  'data' => [ 'index1' => 1, 'index2' => 2 ] ]
		);
		$this->assertSame( '<div><div><p>1</p></div><div><p>2</p></div></div>', $result );
	}

	public function testForVariableIsAvailableForNestedExpressions_NestedDataAccess() {
		$result = $this->createAndRender(
			'<div><div v-for="index in data">' .
			'<p>{{ indexKeys[index.key] }}</p>' .
			'</div></div>',
			[ 'indexKeys' => [ 'value1', 'value2' ],
			  'data' => [ 'index1' => [ 'key' => 0 ], 'index2' => [ 'key' => 1 ] ] ]
		);
		$this->assertSame( '<div><div><p>value1</p></div><div><p>value2</p></div></div>', $result );
	}

	public function testTemplateWithForLoopMustache_RendersCorrectValues() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list">{{item}}</a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		$this->assertSame( '<p><a>1</a><a>2</a></p>', $result );
	}

	public function testTemplateWithForLoopAndKey_DropsKeyAttributeFromOutput() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list" :key="item">{{item}}</a></p>',
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
			'<p>{{variable.property}}</p>',
			[ 'variable' => [ 'property' => 'value' ] ]
		);

		$this->assertSame( '<p>value</p>', $result );
	}

	public function testTemplateWithMethodAccessInAttributeBinding_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p :attr1="strtoupper(variable.property)"></p>',
			[ 'variable' => [ 'property' => 'value' ] ],
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

	public function testMustacheAfterVIf(): void {
		$result = $this->createAndRender(
			'<p>a: {{ a }} <span v-if="b">b: {{ b }} </span>c: {{c }}</p>',
			[ 'a' => 'A', 'b' => false, 'c' => 'C' ]
		);

		$this->assertSame( '<p>a: A c: C</p>', $result );
	}

	public function testTemplateWithArrayValuedClassAttribute() {
		$result = $this->createAndRender(
			'<p><a :class="list">Link</a></p>',
			[ 'list' => [ 'one_class', 'another_class' ] ]
		);

		$this->assertSame( '<p><a class="one_class another_class">Link</a></p>', $result );
	}

	public function testTemplateWithObjectValuedClassAttribute() {
		$result = $this->createAndRender(
			'<p><a :class="list">Link</a></p>',
			[ 'list' => [ 'one_class' => true, 'another_class' => false ] ]
		);

		$this->assertSame( '<p><a class="one_class">Link</a></p>', $result );
	}

	public function testTemplateWithObjectValuedClassAttribute_UnionWithExistingAttributes() {
		$result = $this->createAndRender(
			'<p><a class="another_class" :class="list">Link</a></p>',
			[ 'list' => [ 'one_class' => true, 'another_class' => false ] ]
		);

		$this->assertSame( '<p><a class="one_class another_class">Link</a></p>', $result );
	}

	public function testTemplateWithObjectValuedNonClassAttribute_ThrowsError() {
		$this->expectException( Exception::class );
		$this->createAndRender(
			'<p><a :my-attr="list">Link</a></p>',
			[ 'list' => [ 'one_class' => true, 'another_class' => false ] ]
		);
	}

	public function testMoustacheVariableWithArrayListTypeSubstitution() {
		$result = $this->createAndRender(
			'<p>{{ my.data.variable }}</p>',
			[ 'my' => [ 'data' => [ 'variable' => [ 1, 2, 3 ] ] ] ]
		);
		$this->assertSame( '<p>[1,2,3]</p>', $result );
	}

	public function testMoustacheVariableWithArrayObjectTypeSubstitution() {
		$result = $this->createAndRender(
			'<p>{{ my.data.variable }}</p>',
			[ 'my' => [ 'data' => [ 'variable' => [ "a" => "b", "c" => "d" ] ] ] ]
		);
		$this->assertSame( '<p>{"a":"b","c":"d"}</p>', $result );
	}

	public function testTemplateWithBooleanExpression() {
		$result = $this->createAndRender(
			'<div><div v-if="myvar === \'myvalue\'"><p>Paragraph</p></div></div>',
			[ 'myvar' => 'myvalue' ]
		);

		$this->assertSame( '<div><div><p>Paragraph</p></div></div>', $result );
	}

	public function testTemplateWithBooleanValueInIf() {
		$result = $this->createAndRender( '<p><a v-if="false"></a></p>', [ 'variable' => true ] );

		$this->assertSame( '<p></p>', $result );
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
