<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 * 
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the along with the code.
 */

use Lechimp\Dicto\Dicto as Dicto;
use Lechimp\Dicto\Verification as Ver;

class ClassMock implements Ver\ClassArtifact {
    public function __construct($name) {
        $this->name = $name;
    }

    public function name() { return $this->name; }
}

class FunctionMock implements Ver\FunctionArtifact {
    public function __construct($name) {
        $this->name = $name;
    }

    public function name() { return $this->name; }
}

class GlobalMock implements Ver\GlobalArtifact {
    public function __construct($name) {
        $this->name = $name;
    }

    public function name() { return $this->name; }
}

class FileMock implements Ver\FileArtifact {
    public function __construct($name) {
        $this->name = $name;
    }

    public function name() { return $this->name; }
}

class BuildinMock implements Ver\BuildinArtifact {
    public function __construct($name) {
        $this->name = $name;
    }

    public function name() { return $this->name; }
}

class ArtifactSelectionTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->class_one = new ClassMock("ClassOne");
        $this->function_one = new FunctionMock("function_one");
        $this->global_one = new GlobalMock("global_one");
        $this->file_one = new FileMock("file_one");
        $this->buildin_one = new BuildinMock("buildin_one");

        $this->selector = new Ver\Implementation\Selector;
    }

    public function test_match_every_class() {
        $var = Dicto::_every()->_class();
        $this->assertTrue($this->selector->matches($var, $this->class_one));
        $this->assertFalse($this->selector->matches($var, $this->function_one));
        $this->assertFalse($this->selector->matches($var, $this->global_one));
        $this->assertFalse($this->selector->matches($var, $this->file_one));
        $this->assertFalse($this->selector->matches($var, $this->buildin_one));
    }

    public function test_match_every_function() {
        $var = Dicto::_every()->_function();
        $this->assertFalse($this->selector->matches($var, $this->class_one));
        $this->assertTrue($this->selector->matches($var, $this->function_one));
        $this->assertFalse($this->selector->matches($var, $this->global_one));
        $this->assertFalse($this->selector->matches($var, $this->file_one));
        $this->assertFalse($this->selector->matches($var, $this->buildin_one));
    }

    public function test_match_every_global() {
        $var = Dicto::_every()->_global();
        $this->assertFalse($this->selector->matches($var, $this->class_one));
        $this->assertFalse($this->selector->matches($var, $this->function_one));
        $this->assertTrue($this->selector->matches($var, $this->global_one));
        $this->assertFalse($this->selector->matches($var, $this->file_one));
        $this->assertFalse($this->selector->matches($var, $this->buildin_one));
    }

    public function test_match_every_file() {
        $var = Dicto::_every()->_file();
        $this->assertFalse($this->selector->matches($var, $this->class_one));
        $this->assertFalse($this->selector->matches($var, $this->function_one));
        $this->assertFalse($this->selector->matches($var, $this->global_one));
        $this->assertTrue($this->selector->matches($var, $this->file_one));
        $this->assertFalse($this->selector->matches($var, $this->buildin_one));
    }

    public function test_match_every_buildin() {
        $var = Dicto::_every()->_buildin();
        $this->assertFalse($this->selector->matches($var, $this->class_one));
        $this->assertFalse($this->selector->matches($var, $this->function_one));
        $this->assertFalse($this->selector->matches($var, $this->global_one));
        $this->assertFalse($this->selector->matches($var, $this->file_one));
        $this->assertTrue($this->selector->matches($var, $this->buildin_one));
    }
}
