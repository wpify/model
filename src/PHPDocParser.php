<?php

namespace WpifyModel;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

class PHPDocParser {
	public $lexer;
	public $parser;
	static $parsed;

	public function __construct() {
		$this->lexer     = new Lexer();
		$constExprParser = new ConstExprParser();
		$this->parser    = new \PHPStan\PhpDocParser\Parser\PhpDocParser( new TypeParser( $constExprParser ), $constExprParser );
	}

	public function parse( $class, $type, $input, $name = '' ) {
		if ( 'properties' === $type && isset( self::$parsed[ $class ][$type][ $name ] ) ) {
			return self::$parsed[ $class ][$type][ $name ];
		}

		$tokens = new TokenIterator( $this->lexer->tokenize( $input ) );

		$parsed = $this->parser->parse( $tokens );
		if ( $type === 'properties' ) {
			self::$parsed[ $class ][ $type ][ $name ] = $parsed;
		}

		return $parsed;
	}

}
