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
		$result = $this->createAndRender( "<p>{{'ABC'|message}}</p>", [], [ 'message' => function(  $value ){ return "some $value message"; } ] );

		assertThat( $result, is( equalTo( '<p>some ABC message</p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithTruthfulConditionInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [  'variable' => true] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithUntruthfulConditionInIf_IsRemoved() {
		$result = $this->createAndRender( '<p><a v-if="variable"></a></p>', [  'variable' => false] );

		assertThat( $result, is( equalTo( '<p></p>' ) ) );
	}

	/**
	 * @test
	 */
	public function templateWithNegatedFalseInIf_IsNotRemoved() {
		$result = $this->createAndRender( '<p><a v-if="!variable"></a></p>', [  'variable' => false] );

		assertThat( $result, is( equalTo( '<p><a></a></p>' ) ) );
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

}
