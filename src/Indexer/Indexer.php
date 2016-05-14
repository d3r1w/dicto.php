<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the licence along with the code.
 */

namespace Lechimp\Dicto\Indexer;

use Lechimp\Dicto\Indexer as I;
use Lechimp\Dicto\Analysis\Consts;
use PhpParser\Node as N;

/**
 * Implementation of Indexer with PhpParser.
 */
class Indexer implements \PhpParser\NodeVisitor {
    /**
     * @var string
     */
    protected $project_root_path;

    /**
     * @var I\Insert|null
     */
    protected $insert;

    /**
     * @var \PhpParser\Parser
     */
    protected $parser;

    /**
     * @var Listener[];
     */
    protected $listeners;

    // state for parsing a file

    /**
     * @var string|null
     */
    protected $file_path = null;

    /**
     * @var string[]|null
     */
    protected $file_content = null;

    /**
     * This contains the stack of ids were currently in, i.e. the nesting of
     * known code blocks we are in.
     *
     * @var array|null  contain ($entity_type, $entity_id)
     */
    protected $entity_stack = null;

    /**
     * This contains cached reference ids.
     *
     * @var array|null   string => int 
     */
    protected $reference_cache = null; 
    

    public function __construct(\PhpParser\Parser $parser) {
        $this->project_root_path = "";
        $this->parser = $parser;
        $this->listeners = array();
    }

    protected function build_listeners() {
        if ($this->insert === null) {
            throw new \LogicException(
                "Needs an inserter before Listeners can be created.");
        }

        return array
            ( new DependenciesListener($this->insert, $this)
            , new InvocationsListener($this->insert, $this)
            );
    }

    /**
     * @inheritdoc
     */
    public function index_file($path) {
        if ($this->insert === null) {
            throw new \RuntimeException(
                "Set an inserter to be used before starting to index files.");
        }

        $content = file_get_contents($this->project_root_path."/$path");
        if ($content === false) {
            throw \InvalidArgumentException("Can't read file $path.");
        }

        $stmts = $this->parser->parse($content);
        if ($stmts === null) {
            throw new \RuntimeException("Can't parse file $path.");
        }

        if ($stmts === null) {
            throw new \RuntimeException("Could not parse file '$path'.");
        }

        $traverser = new \PhpParser\NodeTraverser;
        $traverser->addVisitor($this);

        $this->entity_stack = array();
        $this->file_path = $path;
        $this->file_content = explode("\n", $content);
        $this->reference_cache = array();
        $traverser->traverse($stmts);
        $this->entity_stack = null; 
        $this->file_path = null;
        $this->file_content = null;
        $this->reference_cache = null;
    }

    /**
     * @inheritdoc
     */
    public function use_insert(I\Insert $insert) {
        $this->insert = $insert;
        $this->listeners = $this->build_listeners();
    }

    /**
     * @inheritdoc
     */
    public function set_project_root_to($path) {
        assert('is_string($path)');
        $this->project_root_path = $path;
    }

    // helper

    private function lines_from_to($start, $end) {
        assert('is_int($start)');
        assert('is_int($end)');
        return implode("\n", array_slice($this->file_content, $start-1, $end-$start+1));
    }

    // TODO: This most propably should go to Insert.
    public function get_reference($entity_type, $name, $start_line) {
        assert('in_array($entity_type, \\Lechimp\\Dicto\\Analysis\\Consts::$ENTITY_TYPES)');
        assert('is_string($name)');
        assert('is_int($start_line)');

        // caching
        $key = $entity_type.":".$name.":".$this->file_path.":".$start_line;
        if (array_key_exists($key, $this->reference_cache)) {
            return $this->reference_cache[$key];
        }

        $ref_id = $this->insert->reference
            ( $entity_type 
            , $name 
            , $this->file_path
            , $start_line
            );

        $this->reference_cache[$key] = $ref_id;
        return $ref_id;
    }

    // from \PhpParser\NodeVisitor

    /**
     * @inheritdoc
     */
    public function beforeTraverse(array $nodes) {
        // for sure found a file
        $id = $this->insert->entity
            ( Consts::FILE_ENTITY
            , $this->file_path
            , $this->file_path
            , 1
            , count($this->file_content)
            , implode("\n", $this->file_content)
            );

        $this->entity_stack[] = array(Consts::FILE_ENTITY, $id);

        foreach ($this->listeners as $listener) {
            $listener->on_enter_file($id, $this->file_path, implode("\n", $this->file_content));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function afterTraverse(array $nodes) {
        $type_and_id = array_pop($this->entity_stack);

        foreach ($this->listeners as $listener) {
            $listener->on_leave_file($type_and_id[1]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function enterNode(\PhpParser\Node $node) {
        $start_line = $node->getAttribute("startLine");
        $end_line = $node->getAttribute("endLine");
        $source = $this->lines_from_to($start_line, $end_line);

        $id = null;
        $type = null;

        // Class
        if ($node instanceof N\Stmt\Class_) {
            $type = Consts::CLASS_ENTITY;
            $id = $this->insert->entity
                ( $type
                , $node->name
                , $this->file_path
                , $start_line
                , $end_line
                , $source
                );

            foreach ($this->listeners as $listener) {
                $listener->on_enter_class($id, $node);
            }
        }
        // Method or Function
        elseif ($node instanceof N\Stmt\ClassMethod) {
            $type = Consts::METHOD_ENTITY;
            $id = $this->insert->entity
                ( $type
                , $node->name
                , $this->file_path
                , $start_line
                , $end_line
                , $source
                );

            foreach ($this->listeners as $listener) {
                $listener->on_enter_method($id, $node);
            }
        }
        elseif ($node instanceof N\Stmt\Function_) {
            $type = Consts::FUNCTION_ENTITY;
            $id = $this->insert->entity
                ( $type 
                , $node->name
                , $this->file_path
                , $start_line
                , $end_line
                , $source
                );

            foreach ($this->listeners as $listener) {
                $listener->on_enter_function($id, $node);
            }
        }
        else {
            foreach ($this->listeners as $listener) {
                $listener->on_enter_misc($this->entity_stack, $node);
            }
        }

        if ($id !== null) {
            $this->entity_stack[] = array($type, $id);
        }
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(\PhpParser\Node $node) {
        // Class
        if ($node instanceof N\Stmt\Class_) {
            // We pushed it, we need to pop it now, as we are leaving the class.
            $type_and_id = array_pop($this->entity_stack);

            foreach ($this->listeners as $listener) {
                $listener->on_leave_class($type_and_id[1]);
            }
        }
        // Method or Function
        elseif ($node instanceof N\Stmt\ClassMethod) {
            // We pushed it, we need to pop it now, as we are leaving the method
            // or function.
            $type_and_id = array_pop($this->entity_stack);

            foreach ($this->listeners as $listener) {
                $listener->on_leave_method($type_and_id[1]);
            }
        }
        elseif ($node instanceof N\Stmt\Function_) {
            // We pushed it, we need to pop it now, as we are leaving the method
            // or function.
            $type_and_id = array_pop($this->entity_stack);

            foreach ($this->listeners as $listener) {
                $listener->on_leave_function($type_and_id[1]);
            }
        }
        else {
            foreach ($this->listeners as $listener) {
                $listener->on_leave_misc($this->entity_stack, $node);
            }
        }
    }
}
