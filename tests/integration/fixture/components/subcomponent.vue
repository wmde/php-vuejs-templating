<template id="template" component-name="subcomponent">
<div><p>Subcomponent!</p></div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'SubComponent',
	setup() {
		return { };
	}
} );
</script>