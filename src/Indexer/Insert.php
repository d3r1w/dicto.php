<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the along with the code.
 */

namespace Lechimp\Dicto\Indexer;

use Lechimp\Dicto\Analysis\Consts;

/**
 * This is how to insert new entries in the index. 
 */
interface Insert {
    /**
     * Record general info about an entity.
     *
     * An entity is anything the user defined in its code like a class, a method or
     * a file, i.e. something where we know for sure how it looks like.
     *
     * Uses the same range for ids than reference, that is, each id either referers to
     * a entity or a reference.
     *
     * @param   int             $type       one of Consts::ENTITY_TYPES;
     * @param   string          $name
     * @param   string          $file
     * @param   int             $start_line
     * @param   int             $end_line
     * @param   string          $source
     * @return  int                         id of new entity
     */
    public function entity($type, $name, $file, $start_line, $end_line, $source);

    /**
     * Record general info about a reference to an entity.
     *
     * A reference to an entity, buildin or global, i.e. a place where we know there
     * should be something we are refering to by name, but can not get hold of the
     * source, i.e. a function in a function call or the usage of a global. There
     * might be the possibility to dereference the reference to an entity later.
     *
     * Uses the same range for ids than entity, that is, each id either referers to
     * a entity or a reference.
     *
     * @param   int             $type
     * @param   string          $name
     * @param   string          $file       where the entity was referenced
     * @param   int             $line       where the entity was referenced
     * @return  int                         id of new reference
     */
    public function reference($type, $name, $file, $line);

    /**
     * Record information about a dependency.
     *
     * A class or function is considered do depend on something if its body
     * of definition makes use of the thing. Language constructs, files or globals
     * can't depend on anything.
     *
     * @param   int             $dependent_id
     * @param   int             $dependency_id
     * @param   string          $file       where the dependency was created
     * @param   int             $line       where the dependency was created
     * @param   string          $source_line
     * @return  null
     */
    public function dependency($dependent_id, $dependency_id, $file, $line, $source_line);

    /**
     * Record information about an invocation.
     *
     * A class of function is considered to invoke something, it that thing is invoked
     * in its body.
     *
     * @param   int             $invoker_id
     * @param   int             $invokee_id
     * @param   string          $file       where the invocation was made
     * @param   int             $line       where the invocation was made
     * @param   string          $source_line
     * @return  null
     */
    public function invocation($invoker_id, $invokee_id, $file, $line, $source_line);
}