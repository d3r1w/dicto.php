<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 * 
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the along with the code.
 */

namespace Lechimp\Dicto\App;

use Lechimp\Dicto\Definition\Rules as Rules;

interface RuleLoader {
    /**
     * @param   string  $rule_file_path
     * @throws  InvalidArgumentException    if rule_file_path is no valid file
     * @return  Rules\Rule[]
     */
    public function load_rules_from($rule_file_path);
}
