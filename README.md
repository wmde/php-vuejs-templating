# VueJS templating implementation in PHP

Simple PHP implementation of the [Vue Template](https://vuejs.org/v2/guide/syntax.html) renderer.

The library has been created to be used for rendering templates
in the [Wikibase Lexeme extension](https://www.mediawiki.org/wiki/Extension:WikibaseLexeme).
It intentionally covers only a subset of Vue Template syntax that is used by the Wikibase
Lexeme extension. It is not going to cover all elements of Vue Template language.

## Migration notes

For the purpose of migrating to Vue 3, very basic support for the method syntax was added as an alternative to filters.
In order to keep the interface stable, method callbacks should also be provided as the third parameter when calling
`Templating::render()`.

## Installation

The recommended way of installing the library is using [Composer](https://getcomposer.org),
e.g. by adding the following line to the `require` section of the `composer.json` file:

```
	"wmde/php-vuejs-templating": "^1.1.0"
```

## Tests

The library comes with a set of PHPUnit tests, that include unit tests of library elements
(`tests/php` directory), and also integration tests of rendering the template syntax elements used
in the Wikibase Lexeme extension (`tests/integration` directory).

Tests could run by executing `composer phpunit` command.
