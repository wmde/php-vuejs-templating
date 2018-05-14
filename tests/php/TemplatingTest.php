<?php

namespace WMDE\VueJsTemplating\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use WMDE\VueJsTemplating\Templating;

class TemplatingTest extends TestCase {

	/**
	 * @test
	 */
	public function justASingleEmptyHtmlElement() {
		$result = $this->createAndRender( '<div></div>', [] );

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateHasTwoRootNodes_ThrowsAnException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p></p><p></p>', [] );
	}

	/**
	 * @test
	 */
	public function templateHasOnClickHandler_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click="doStuff"></div>', [] );

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateHasOnClickHandlerAndPreventDefault_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<div v-on:click.prevent="doStuff"></div>', [] );

		assertThat( $result, is( equalTo( '<div></div>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateHasOnClickHandlerInSomeChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><a v-on:click="doStuff"></a></p>', [] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateHasOnClickHandlerInGrandChildNode_RemoveHandlerFormOutput() {
		$result = $this->createAndRender( '<p><b><a v-on:click="doStuff"></a></b></p>', [] );

		assertThat( $result, is( equalTo( '<p><b><a></a></b></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithSingleMustacheVariable_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>{{value}}</p>', [ 'value' => 'some value' ] );

		assertThat( $result, is( equalTo( '<p>some value</p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithMustacheVariable_VariableIsUndefined_ThrowsException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p>{{value}}</p>', [] );
	}

	/**
	 * @test
	 */
	public function templateWithFilter_FilterIsUndefined_ThrowsException() {
		$this->setExpectedException( Exception::class );
		$this->createAndRender( '<p>{{value|nonexistentFilter}}</p>', [ 'value' => 'some value' ] );
	}

	/**
	 * @test
	 */
	public function templateWithMustacheVariableSurroundedByText_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>before {{value}} after</p>', [ 'value' => 'some value' ] );

		assertThat( $result, is( equalTo( '<p>before some value after</p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithMustacheHavingStringLiteral_JustPrintString() {
		$result = $this->createAndRender( "<p>before {{'string'}} after</p>", [] );

		assertThat( $result, is( equalTo( '<p>before string after</p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithMustacheFilter_ReplacesVariableWithGivenCallbackReturnValue() {
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

	/**
	 * @test
	 */
	public function templateWithTruthfulConditionInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => true ] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithUntruthfulConditionInIf_IsRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [ 'variable' => false ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithNegatedFalseInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="!variable"></a></p>', [ 'variable' => false ] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithIfElseBlockAndTruthfulCondition_ElseIsRemoved() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => true ]
		);

		assertThat( $result, is( equalTo( '<p><a>if</a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithIfElseBlockAndNontruthfulCondition_ElseIsDisplayed() {
		$result = $this->createAndRender(
			'<p><a v-if="variable">if</a><a v-else>else</a></p>',
			[ 'variable' => false ]
		);

		assertThat( $result, is( equalTo( '<p><a>else</a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopAndEmptyArrayToIterate_NotRendered() {
		$result = $this->createAndRender( '<p><a v-for="item in list"></a></p>', [ 'list' => [] ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopAndSingleElementInArrayToIterate_RenderedOnce() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopAndMultipleElementsInArrayToIterate_RenderedMultipleTimes() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list"></a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a></a><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopMustache_RendersCorrectValues() {
		$result = $this->createAndRender(
			'<p><a v-for="item in list">{{item}}</a></p>',
			[ 'list' => [ 1, 2 ] ]
		);

		assertThat( $result, is( equalTo( '<p><a>1</a><a>2</a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithAttributeBinding_ConditionIsFalse_AttributeIsNotRendered() {
		$result = $this->createAndRender( '<p :attr1="condition"></p>', [ 'condition' => false ] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithAttributeBinding_ConditionIsTrue_AttributeIsRendered() {
		$result = $this->createAndRender(
			'<p :disabled="condition"></p>',
			[ 'condition' => true ]
		);

		assertThat( $result, is( equalTo( '<p disabled></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithAttributeBinding_ConditionIsString_AttributeIsRenderedWithThatString() {
		//TODO Rename variable name
		$result = $this->createAndRender(
			'<p :attr1="condition"></p>',
			[ 'condition' => 'some string' ]
		);

		assertThat( $result, is( equalTo( '<p attr1="some string"></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithPropertyAccessInMustache_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p>{{var.property}}</p>',
			[ 'var' => [ 'property' => 'value' ] ]
		);

		assertThat( $result, is( equalTo( '<p>value</p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithFilterAccessInAttributeBinding_CorrectValueIsRendered() {
		$result = $this->createAndRender(
			'<p :attr1="var.property|strtoupper"></p>',
			[ 'var' => [ 'property' => 'value' ] ],
			[ 'strtoupper' => 'strtoupper' ]
		);

		assertThat( $result, is( equalTo( '<p attr1="VALUE"></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithFilterAccessInAttributeBindingInsideTheLoop_CorrectValueIsRendered() {
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
