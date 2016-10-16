<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 * 
 * Copyright (c) 2016 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the license along with the code.
 */

namespace Lechimp\Dicto\Graph;

/**
 * Some predicate over an entity.
 */
abstract class Predicate {
    /**
     * Compile the predicate to a function on an entity.
     *
     * @return  \Closure    Entity -> bool
     */
    final public function compile() {
        $custom_closures = [];
        $source =
            "return function (\\Lechimp\\Dicto\\Graph\\Entity \$e) use (\$custom_closures) {\n".
            "    \$stack = [];\n".
            "    \$pos = 0;\n".
            $this->compile_to_source($custom_closures).
            "    assert('count(\$stack) == 1');\n".
            "    assert('\$pos == 0');\n".
            "    return \$stack[0];\n".
            "};\n";
        $closure = eval($source);
        assert('$closure instanceof \Closure');

        return $closure;
    }

    /**
     * Compile the predicate to some php source code.
     *
     * @param   \Closure[]  &$custom_closures
     * @return  string
     */
    abstract public function compile_to_source(array &$custom_closures);

    /**
     * Get the entity-types that could be matched by this predicate.
     *
     * @param   string[]    $existing_types
     * @return  string[]
     */
    abstract public function for_types(array $existing_types);
}
