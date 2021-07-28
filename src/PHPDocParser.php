<?php

namespace WpifyModel;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

class PHPDocParser {
	public $lexer;
	public $parser;

	public function __construct() {
		$this->lexer     = new Lexer();
		$constExprParser = new ConstExprParser();
		$this->parser    = new \PHPStan\PhpDocParser\Parser\PhpDocParser( new TypeParser( $constExprParser ), $constExprParser );
	}

	public function parse( $input ) {
		$tokens = new TokenIterator( $this->lexer->tokenize( $input ) );

		return $this->parser->parse( $tokens );
	}
}
