<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

namespace Lechimp\Dicto\Rules;

use Lechimp\Dicto\Variables\Variable;
use Lechimp\Dicto\Indexer\Location;
use Lechimp\Dicto\Indexer\Insert;
use Lechimp\Dicto\Indexer\ListenerRegistry;
use PhpParser\Node as N;

/**
 * A class of function is considered to invoke something, it that thing is invoked
 * in its body.
 */
class Invoke extends Relation {
    /**
     * @inheritdoc
     */
    public function name() {
        return "invoke";    
    }

    /**
     * @inheritdoc
     */
    public function register_listeners(ListenerRegistry $registry) {
        $this->register_func_call_listener($registry);
        $this->register_method_call_listener($registry);
    }

    protected function register_func_call_listener(ListenerRegistry $registry) {
        $registry->on_enter_misc
            ( array(N\Expr\MethodCall::class)
            , function(Insert $insert, Location $location, N\Expr\MethodCall $node) {
                // The 'name' could also be a variable like in $this->$method();
                if (is_string($node->name)) {
                    $ref_id = $insert->get_reference
                        ( Variable::METHOD_TYPE
                        , $node->name
                        , $location->file_path()
                        , $node->getAttribute("startLine")
                        );
                    $this->insert_relation_into
                        ( $insert
                        , $location
                        , $ref_id
                        , $node->getAttribute("startLine")
                        );
                }
            });
    }

    protected function register_method_call_listener(ListenerRegistry $registry) {
        $registry->on_enter_misc
            ( array(N\Expr\FuncCall::class)
            , function(Insert $insert, Location $location, N\Expr\FuncCall $node) {
                // Omit calls to closures, we would not be able to
                // analyze them anyway atm.
                // Omit functions in arrays, we would not be able to
                // analyze them anyway atm.
                if (!($node->name instanceof N\Expr\Variable ||
                      $node->name instanceof N\Expr\ArrayDimFetch)) {
                    $ref_id = $insert->get_reference
                        ( Variable::FUNCTION_TYPE
                        , $node->name->parts[0]
                        , $location->file_path()
                        , $node->getAttribute("startLine")
                        );
                    $this->insert_relation_into
                        ( $insert
                        , $location
                        , $ref_id
                        , $node->getAttribute("startLine")
                        );
                }
            });
    }

}
