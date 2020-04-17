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

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	public function testTemplateHasTwoRootNodes_ThrowsAnException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p></p><p></p>', [] );
	}

	public function testTemplateHasOnClickHandler_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click="doStuff"></div>', [] );

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	public function testTemplateHasOnClickHandlerAndPreventDefault_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click.prevent="doStuff"></div>', [] );

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	public function testTemplateHasOnClickHandlerInSomeChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><a v-on:click="doStuff"></a></p>', [] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	public function testTemplateHasOnClickHandlerInGrandChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><b><a v-on:click="doStuff"></a></b></p>', [] );

		assertThat( $result, is( equalTo( '<p><b><a></a></b></p>' ) ) );
	}

	public function testTemplateWithSingleMustacheVariable_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => 'some value' ] );

		assertThat( $result, is( equalTo( '<p>some value</p>' ) ) );
	}

	public function testTemplateWithVariableAndDiacritcsInValue_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => 'inglés' ] );

		assertThat( $result, is( equalTo( '<p>inglés</p>' ) ) );
	}

	public function testTemplateWithVariableAndValueInKorean_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => '한국어' ] );

		assertThat( $result, is( equalTo( '<p>한국어</p>' ) ) );
	}

	public function testTemplateWithVhtmlVariable_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>some value</p>' ]
		);

		assertThat( $result, is( equalTo( '<div><div><p>some value</p></div></div>' ) ) );
	}

	public function testTemplateWithVhtmlAndDiacritcsInValue_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>inglés</p>' ]
		);

		assertThat( $result, is( equalTo( '<div><div><p>inglés</p></div></div>' ) ) );
	}

	public function testTemplateWithVhtmlAndValueInKorean_ReplacesVariableWithEncodedValue() {
		$result = $this->createAndRender(
			'<div><div v-html="value"></div></div>',
			[ 'value' => '<p>한국어</p>' ]
		);

		assertThat( $result, is( equalTo( '<div><div><p>한국어</p></div></div>' ) ) );
	}

	public function testTemplateWithMustacheVariable_VariableIsUndefined_ThrowsException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p>{{value}}</p>', [] );
	}

	public function testTemplateWithFilter_FilterIsUndefined_ThrowsException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p>{{value|nonexistentFilter}}</p>', [ 'value' => 'some value' ] );
	}

	public function testTemplateWithMustacheVariableSurroundedByText_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>before {{value}} after</p>', [ 'value' => 'some value' ] );

		assertThat( $result, is( equalTo( '<p>before some value after</p>' ) ) );
	}

	public function testTemplateWithMustacheHavingStringLiteral_JustPrintString() {
		$result = $this->createAndRender( "<p>before {{'string'}} after</p>", [] );

		assertThat( $result, is( equalTo( '<p>before string after</p>' ) ) );
	}

	public function testTemplateWithMustacheFilter_ReplacesVariableWithGivenCallbackReturnValue() {
		$result = $this->createAndRender(
			"<p>{{'ABC'|message}}</p>",
			[],
			[
				'message' => function ( $value ) {
					return "some $value message";
				}
			]
		);

		assertThat( $result, is( equalTo( '<p>some ABC message</p>' ) ) );
	}

	public function testTemplateWithTruthfulConditionInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => true ] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	public function testTemplateWithUntruthfulConditionInIf_IsRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => false ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	public function testTemplateWithNegatedFalseInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="!variable"></a></p>', [ 'variable' => false ] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	public function testTemplateWithIfElseBlockAndTruthfulCondition_ElseIsRemoved() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => true ]
		);

		assertThat( $result, is( equalTo( '<p><a>if</a></p>' ) ) );
	}

	public function testTemplateWithIfElseBlockAndNontruthfulCondition_ElseIsDisplayed() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => false ]
		);

		assertThat( $result, is( equalTo( '<p><a>else</a></p>' ) ) );
	}

	public function testTemplateWithForLoopAndEmptyArrayToIterate_NotRendered() {
		$result = $this->createAndRender( '<p><a v-for="item in list"></a></p>', [ 'list' => [] ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	public function testTemplateWithForLoopAndSingleElementInArrayToIterate_RenderedOnce() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	public function testTemplateWithForLoopAndMultipleElementsInArrayToIterate_RenderedMultipleTimes() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a></a><a></a></p>' ) ) );
	}

	public function testTemplateWithForLoopMustache_RendersCorrectValues() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list">{{item}}</a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a>1</a><a>2</a></p>' ) ) );
	}

	public function testTemplateWithAttributeBinding_ConditionIsFalse_AttributeIsNotRendered() {
		$result = $this->createAndRender( '<p :attr1="condition"></p>', [ 'condition' => false ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	public function testTemplateWithAttributeBinding_ConditionIsTrue_AttributeIsRendered() {
		$result = $this->createAndRender(
			'<p :disabled="condition"></p>',
			[ 'condition' => true ]
		);

		assertThat( $result, is( equalTo( '<p disabled></p>' ) ) );
	}

	// phpcs:ignore Generic.Files.LineLength.TooLong
	public function testTemplateWithAttributeBinding_ConditionIsString_AttributeIsRenderedWithThatString() {
		//TODO Rename variable name
		$result = $this->createAndRender(
			'<p :attr1="condition"></p>',
			[ 'condition' => 'some string' ]
		);

		assertThat( $result, is( equalTo( '<p attr1="some string"></p>' ) ) );
	}

	public function testTemplateWithPropertyAccessInMustache_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p>{{var.property}}</p>',
			[ 'var' => [ 'property' => 'value' ] ]
		);

		assertThat( $result, is( equalTo( '<p>value</p>' ) ) );
	}

	public function testTemplateWithFilterAccessInAttributeBinding_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p :attr1="var.property|strtoupper"></p>',
			[ 'var' => [ 'property' => 'value' ] ],
			[ 'strtoupper' => 'strtoupper' ]
		);

		assertThat( $result, is( equalTo( '<p attr1="VALUE"></p>' ) ) );
	}

	// phpcs:ignore Generic.Files.LineLength.TooLong
	public function testTemplateWithFilterAccessInAttributeBindingInsideTheLoop_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p><a v-for="item in items" :attr1="item.property|strtoupper"></a></p>',
			[ 'items' => [
				[ 'property' => 'value1' ],
				[ 'property' => 'value2' ],
			] ],
			[ 'strtoupper' => 'strtoupper' ]
		);

		assertThat( $result, is( equalTo( '<p><a attr1="VALUE1"></a><a attr1="VALUE2"></a></p>' ) ) );
	}

	/**
	 * @param string $template HTML
	 * @param array $data
	 * @param callable[] $filters
	 *
	 * @return string
	 */
	private function createAndRender( $template, array $data, array $filters = [] ) {
		$templating = new Templating();
		return $templating->render( $template, $data, $filters );
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
