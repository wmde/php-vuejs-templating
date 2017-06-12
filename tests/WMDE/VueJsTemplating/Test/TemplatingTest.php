<?php

namespace WMDE\VueJsTemplating\Test;

use WMDE\VueJsTemplating\Templating;

class TemplatingTest extends \PHPUnit_Framework_TestCase {

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
		$this->setExpectedException( \Exception::class );
		$result = $this->createAndRender( '<p></p><p></p>', [] );
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
	public function templateWithMustacheVariableSurroundedByText_ReplacesVariableWithGivenValue() {
		$result = $this->createAndRender( '<p>before {{value}} after</p>', [ 'value' => 'some value' ] );

		assertThat( $result, is( equalTo( '<p>before some value after</p>' ) ) );
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
		$result = $this->createAndRender( '<p><a v-if="variable">if</a><a v-else>else</a></p>', [ 'variable' => true ] );

		assertThat( $result, is( equalTo( '<p><a>if</a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithIfElseBlockAndNontruthfulCondition_ElseIsDisplayed() {
		$result = $this->createAndRender( '<p><a v-if="variable">if</a><a v-else>else</a></p>', [ 'variable' => false ] );

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
		$result = $this->createAndRender( '<p><a v-for="item in list"></a></p>', [ 'list' => [ 1 ] ] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopAndMultipleElementsInArrayToIterate_RenderedMultipleTimes() {
		$result = $this->createAndRender( '<p><a v-for="item in list"></a></p>', [ 'list' => [ 1, 2 ] ] );

		assertThat( $result, is( equalTo( '<p><a></a><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithForLoopMustache_RendersCorrectValues() {
		$result = $this->createAndRender( '<p><a v-for="item in list">{{item}}</a></p>', [ 'list' => [ 1, 2 ] ] );

		assertThat( $result, is( equalTo( '<p><a>1</a><a>2</a></p>' ) ) );
	}

	/**
	 * @param $template
	 * @param $data
	 * @return string
	 */
	private function createAndRender( $template, $data, $filters = [] ) {
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
	 * Invalid template [Error compiling template: v-else used on element <div> without corresponding v-if]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		<span>something</span>
	 * 		<div v-else>else body</div>
	 * </div>
	 *
	 * Invalid template [Error compiling template: v-else used on element <div> without corresponding v-if]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		<div>
	 * 			<div v-else>else body</div>
	 * 		</div>
	 * </div>
	 *
	 * Invalid template [Error compiling template: text "something" between v-if and v-else(-if) will be ignored.]:
	 * <div>
	 * 		<div v-if="condition">if body</div>
	 * 		something
	 * 		<div v-else>else body</div>
	 * </div>
	 *
	 */

}
