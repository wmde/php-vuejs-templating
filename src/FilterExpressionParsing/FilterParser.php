<?php

namespace WMDE\VueJsTemplating\FilterExpressionParsing;


class FilterParser {

	public function parse( $exp ) {
		$validDivisionCharRE = '/[\w).+\-_$\]]/';

		$inSingle = false;
		$inDouble = false;
		$inTemplateString = false;
		$inRegex = false;
		$curly = 0;
		$square = 0;
		$paren = 0;
		$lastFilterIndex = 0;
		$c = null;
		$prev = null;
		$i= null;
		$expression= null;
		$filters = [];

		$expressions = [];
		$prevExpressionStart = 0;

		$parsingFilterList = false;
		$filterArgStart = null;
		$filterArgEnd = null;

		$pushFilter = function () use (&$filters, &$exp, &$lastFilterIndex, &$i, &$filterArgStart, &$filterArgEnd) {
			$filterBody = trim( substr( $exp, $lastFilterIndex, $i - $lastFilterIndex ) );
			$openingParenthesisPos = strpos( $filterBody, '(' );
			if ($openingParenthesisPos === false) {
				$filterName = $filterBody;
				$args = [];
			} else {
				$filterName = substr( $filterBody, 0, $openingParenthesisPos );
				$argString = substr( $exp, $filterArgStart, $filterArgEnd - $filterArgStart + 1 );
				$args = $this->parse($argString)->expressions();
			}

			$lastFilterIndex = $i + 1;

			$filters[] = new FilterCall( $filterName, $args );
		};

		$doFinishExpression = function () use ($exp, &$expressions, &$prevExpressionStart, &$i) {
			$expressions[] = trim( substr( $exp, $prevExpressionStart, $i - $prevExpressionStart ) );
			$prevExpressionStart = $i + 1;
		};

		/** @noinspection CallableInLoopTerminationConditionInspection */
		for ( $i = 0; $i < strlen( $exp ); $i++) {
			$prev = $c;
			$c = $exp[$i];
			if ($inSingle) {
				if ( $c === "'" && $prev !== chr( 0x5C ) ) {
					$inSingle = false;
				}
			} else if ($inDouble) {
				if ($c === chr(0x22) && $prev !== chr(0x5C)) { $inDouble = false; }
			} else if ($inTemplateString) {
				if ($c === chr(0x60) && $prev !== chr(0x5C)) { $inTemplateString = false; }
			} else if ($inRegex) {
				if ($c === chr(0x2f) && $prev !== chr(0x5C)) { $inRegex = false; }
			} else if (
				$c === '|' && // pipe
				$exp[$i + 1] !== '|' &&
				$exp[$i - 1] !== '|' &&
				!$curly && !$square && !$paren
			) {
				if (!$parsingFilterList) {
					$doFinishExpression();
				}
				$parsingFilterList = true;
				if ( $expression === null ) {
					// first filter, end of $expression
					$lastFilterIndex = $i + 1;
					$expression = trim( substr( $exp, 0, $i ) );
				} else {
					$pushFilter();
				}
			} elseif ( $c === ',' && !$parsingFilterList && !$curly && !$square && !$paren ) {
				$doFinishExpression();
			} else {
				switch ($c) {
					case chr(0x22): $inDouble = true; break;         // "
					case chr(0x27): $inSingle = true; break;         // '
					case chr(0x60): $inTemplateString = true; break; // `
					case chr( 0x28 ): // (
						if ( $parsingFilterList && $paren === 0 ) {
							$filterArgStart = $i + 1;
						}
						$paren++;

						break;
					case chr( 0x29 ): // )
						$paren--;
						if ( $parsingFilterList && $paren === 0 ) {
							$filterArgEnd = $i - 1;
						}
						break;
					case '[': $square++; break;                // [
					case ']': $square--; break;                // ]
					case '{': $curly++; break;                 // {
					case '}': $curly--; break;                 // }
				}
				if ($c === chr(0x2f)) { // /
					$j = $i - 1;
					$p = null;
					// find first non-whitespace prev char
					for (; $j >= 0; $j--) {
						$p = $exp[$j];
						if ($p !== ' ') { break; }
					}

					if (!$p || !preg_match($validDivisionCharRE, $p)) {
						$inRegex = true;
					}
				}
			}
		}

		if (!$parsingFilterList) {
			$doFinishExpression();
		}

		if ($expression === null) {
			$expression = trim(substr($exp, 0, $i));
		} else if ($lastFilterIndex !== 0) {
			$pushFilter();
		}

		return new ParseResult( $expressions, $filters );
	}

}
