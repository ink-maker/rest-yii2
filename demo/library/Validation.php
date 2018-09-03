<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/12
 * Time: 下午4:59
 */

namespace demo\library;


class Validation extends \ArrayObject
{

    protected $source_filters = array();

    protected $pre_filters = array();

    protected $post_filters = array();

    protected $rules = array();

    protected $callbacks = array();

    /**
     * Rules that are allowed to run on empty fields
     * @var array $empty_rules
     */
    protected $empty_rules = array('required', 'matches', 'default_val');

    protected $errors = array();

    protected $messages = array();

    protected $labels = array();

    /**
     * Fields that are expected to be arrays
     * @var array $array_fields
     */
    protected $array_fields = array();

    protected $error_class = null;

    protected $check_data = [];

    protected $error_map = [];

    public static function factory(array $array)
    {
        return new Validation($array);
    }

    public function __construct(array $array, $errorCallback = 'Exception_Validation')
    {
        $this->source_filters = $array;
        $this->error_class = $errorCallback;
        parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS | \ArrayObject::STD_PROP_LIST);
    }

    /**
     * Add rules to a field. Validation rules may only return TRUE or FALSE and
     * can not manipulate the value of a field.
     *
     * ##### Example
     *
     *     $input = array('form_field1' => '5',
     *                    'form_field2' => '10',
     *                    'form_field3' => '15');
     *
     *     $post  = new Validation($input);
     *
     *     // Add rules to the fields of our form (these can be chained)
     *     $post->add_rules('form_field1', 'required', 'alpha_dash', 'length[1,5]')
     *          ->add_rules('form_field2', 'matches[form_field1]')
     *          ->add_rules('form_field3', 'digit');
     *
     *     // In case you may have custom validation helpers...
     *     $post->add_rules('form_field1', 'myhelper::func', 'digit');
     *
     *     // Commas in rule arguments can be escaped with a backslash: 'matches[some\,val]'
     *
     * @chainable
     * @param   string   $field  Field name
     * @param   mixed    $rules  Rules (one or more arguments)
     * @return  Validation
     */
    public function add_rules($field, $rules)
    {
        // Get the rules
        $rules = func_get_args();
        $rules = array_slice($rules, 1);

        // Set a default field label
        $this->label($field);

        if ($field === TRUE)
        {
            // Use wildcard
            $field = '*';
        }

        foreach ($rules as $rule)
        {
            // Arguments for rule
            $args = NULL;

            // False rule
            $false_rule = FALSE;

            $rule_tmp = trim(is_string($rule) ? $rule : $rule[1]);

            // Should the rule return false?
            if ($rule_tmp !== ($rule_name = ltrim($rule_tmp, '! ')))
            {
                $false_rule = TRUE;
            }

            if (is_string($rule))
            {
                // Use the updated rule name
                $rule = $rule_name;

                // Have arguments?
                if (preg_match('/^([^\[]++)\[(.+)\]$/', $rule, $matches))
                {
                    // Split the rule into the function and args
                    $rule = $matches[1];
                    $args = preg_split('/(?<!\\\\),\s*/', $matches[2]);

                    // Replace escaped comma with comma
                    $args = str_replace('\,', ',', $args);
                }
                //冒号分割
                if(strstr($rule,":")){
                    $match = explode(":",$rule);
                    $rule = $match[0];
                    $args = $match[1];
                }
            }
            else
            {
                $rule[1] = $rule_name;
            }

            if ($rule === 'is_array')
            {
                // This field is expected to be an array
                $this->array_fields[$field] = $field;
            }

            // Convert to a proper callback
            $rule = $this->callback($rule);

            // Add the rule, with args, to the field
            $this->rules[$field][] = array($rule, $args, $false_rule);
        }

        return $this;
    }

    /**
     * Validate by processing pre-filters, rules, callbacks, and post-filters.
     * All fields that have filters, rules, or callbacks will be initialized if
     * they are undefined.
     *
     * ##### Example
     *
     *     $input = array('form_field1' => '5',
     *                    'form_field2' => '10',
     *                    'form_field3' => '15');
     *
     *     $post  = Validation::factory($input)->add_rules('form_field1', 'required')->add_rules('form_field2', 'digit');
     *
     *     // Validate the input array!
     *     if ($post->validate())
     *     {
     *			// Validation succeeded
     *     }
     *     else
     *     {
     *			// Validation failed
     *     }
     *
     * @param   array   $object      Validation object, used only for recursion
     * @param   array   $field_name  Name of field for errors
     * @return  bool
     */
    public function validate($object = NULL, $field_name = NULL)
    {
        $this->check_data = [];
        if ($object === NULL)
        {
            // Use the current object
            $object = $this;
        }

        $array = $this->safe_array();

        // Get all defined field names
        $fields = array_keys($array);

        foreach ($this->pre_filters as $field => $callbacks)
        {
            foreach ($callbacks as $callback)
            {
                if ($field === '*')
                {
                    foreach ($fields as $f)
                    {
                        $array[$f] = is_array($array[$f]) ? array_map($callback, $array[$f]) : call_user_func($callback, $array[$f]);
                    }
                }
                else
                {
                    $array[$field] = is_array($array[$field]) ? array_map($callback, $array[$field]) : call_user_func($callback, $array[$field]);
                }
            }
        }

        foreach ($this->rules as $field => $callbacks)
        {
            foreach ($callbacks as $callback)
            {
                // Separate the callback, arguments and is false bool
                list ($callback, $args, $is_false) = $callback;

                // Function or method name of the rule
                $rule = is_array($callback) ? $callback[1] : $callback;

                if ($field === '*')
                {
                    foreach ($fields as $f)
                    {
                        if (isset($this->errors[$f]))
                        {
                            // Prevent other rules from being evaluated if an error has occurred
                            continue;
                        }

                        if (empty($array[$f]) AND ! in_array($rule, $this->empty_rules))
                        {
                            // This rule does not need to be processed on empty fields
                            continue;
                        }

                        $result = ($args === NULL) ? call_user_func($callback, $array[$f]) : call_user_func($callback, $array[$f], $args);

                        if (($result == $is_false))
                        {
                            $this->add_error($f, $rule, $args);

                            // Stop validating this field when an error is found
                            continue;
                        }

                        if (is_array($result)) {
                            $this->check_data[$f] = $result['val'];
                        } else {
                            $this->check_data[$f] = $array[$f];
                        }

                    }
                }
                else
                {
                    if (isset($this->errors[$field]))
                    {
                        // Prevent other rules from being evaluated if an error has occurred
                        break;
                    }

                    if ( ! in_array($rule, $this->empty_rules) AND ! $this->required($array[$field]))
                    {
                        // This rule does not need to be processed on empty fields
                        continue;
                    }

                    // Results of our test
                    $result = ($args === NULL) ? call_user_func($callback, $array[$field]) : call_user_func($callback, $array[$field], $args);

                    if (($result == $is_false))
                    {
                        $rule = $is_false ? '!'.$rule : $rule;
                        $this->add_error($field, $rule, $args);

                        // Stop validating this field when an error is found
                        break;
                    }

                    if (is_array($result)) {
                        $this->check_data[$field] = $result['val'];
                    } else {
                        $this->check_data[$field] = $array[$field];
                    }

                }
            }
        }

        foreach ($this->callbacks as $field => $callbacks)
        {
            foreach ($callbacks as $callback)
            {
                if ($field === '*')
                {
                    foreach ($fields as $f)
                    {
                        // Note that continue, instead of break, is used when
                        // applying rules using a wildcard, so that all fields
                        // will be validated.
                        if (isset($this->errors[$f]))
                        {
                            // Stop validating this field when an error is found
                            continue;
                        }

                        call_user_func($callback, $this, $f);
                    }
                }
                else
                {
                    if (isset($this->errors[$field]))
                    {
                        // Stop validating this field when an error is found
                        break;
                    }

                    call_user_func($callback, $this, $field);
                }
            }
        }

        foreach ($this->post_filters as $field => $callbacks)
        {
            foreach ($callbacks as $callback)
            {
                if ($field === '*')
                {
                    foreach ($fields as $f)
                    {
                        $array[$f] = is_array($array[$f]) ? array_map($callback, $array[$f]) : call_user_func($callback, $array[$f]);
                    }
                }
                else
                {
                    $array[$field] = is_array($array[$field]) ? array_map($callback, $array[$field]) : call_user_func($callback, $array[$field]);
                }
            }
        }

        // Swap the array back into the object
        $this->exchangeArray($array);

        // Return TRUE if there are no errors
        return $this->errors === array();
    }

    /**
     * 验证参数合法性,如果不合法 抛出异常
     * @param null $object
     * @param null $field_name
     * @throws BizException
     */
    public function validateOrThrowException($object = null, $field_name = null)
    {
        if ($this->validate($object, $field_name) === false) {
            throw new BizException(0, $this->getErrorMsg(), BizException::PARAM_ERROR);
        }
    }

    /**
     * 根据这个提示快速定位不合规则的字段
     * @return string
     */
    public function getErrorMsg(): string
    {
        $keys = array_keys($this->errors);
        $msg = implode(',', $keys) . '参数错误!';
        return $msg;
    }
    /**
     * Magic clone method, clears errors and messages.
     *
     * @return  void
     */
    public function __clone()
    {
        $this->errors = array();
        $this->messages = array();
    }


    /**
     * Returns an array of all the field names that have filters, rules, or callbacks.
     *
     * ##### Example
     *
     *     $fields	= $post->field_names();
     *     // Outputs an array with the names of all fields that have filters, rules, callbacks.
     *
     * @return  array
     */
    public function field_names()
    {
        // All the fields that are being validated
        $fields = array_keys(array_merge
        (
            $this->source_filters,
            $this->pre_filters,
            $this->rules,
            $this->callbacks,
            $this->post_filters
        ));

        // Remove wildcard fields
        $fields = array_diff($fields, array('*'));

        return $fields;
    }

    /**
     * Returns the ArrayObject values, removing all inputs without rules.
     * To choose specific inputs, list the field name as arguments.
     *
     * ##### Example
     *
     *     // Similar to as_array() but only returns array values that have had rules, filters, and/or callbacks assigned.
     *     $input = array('form_field1' => '5',
     *                    'form_field2' => '10',
     *                    'form_field3' => '15');
     *
     *     $post  = Validation::factory($input)->add_rules('form_field1', 'required')->add_rules('form_field2', 'digit');
     *     echo print_r($post->safe_array());
     *
     *     // Output: Array ( [form_field1] => 5 [form_field2] => 10 )
     *
     *     // Same as above but here we specify (using the field name) which ones we want to recieve back.
     *     $post  = Validation::factory($input)->add_rules('form_field1', 'required')->add_rules('form_field2', 'digit');
     *     echo print_r($post->safe_array('form_field2'));
     *
     *     // Output: Array ( [form_field2] => 10 )
     *
     * @param   boolean  Return only fields with filters, rules, and callbacks
     * @return  array
     */
    public function safe_array()
    {
        // Load choices
        $choices = func_get_args();
        $choices = empty($choices) ? NULL : array_combine($choices, $choices);

        // Get field names
        $fields = $this->field_names();

        $safe = array();
        foreach ($fields as $field)
        {
            if ($choices === NULL OR isset($choices[$field]))
            {
                if (isset($this[$field]))
                {
                    $value = $this[$field];

                    if (is_object($value))
                    {
                        // Convert the value back into an array
                        $value = $value->getArrayCopy();
                    }
                }
                else
                {
                    // Even if the field is not in this array, it must be set
                    $value = NULL;
                }

                // Add the field to the array
                $safe[$field] = $value;
            }
        }

        return $safe;
    }

    /**
     * Sets or overwrites the label name for a field. Label names are used in the
     * default validation error messages. You can use a label name in custom error
     * messages with the `:field` place holder.
     *
     * ##### Example
     *
     *     // Set a default label
     *     $post->label('form_field1');
     *     // Label will be set to 'Form Field'
     *
     *     // Set an alternative label
     *     $post->label('form_field1', 'My Field Name');
     *
     * @param   string   $field   Field name
     * @param   string   $label   Label
     * @return Validation
     */
    public function label($field, $label = NULL)
    {
        if ($label === NULL AND ($field !== TRUE OR $field !== '*') AND ! isset($this->labels[$field]))
        {
            // Set the field label to the field name
            $this->labels[$field] = ucfirst(preg_replace('/[^\pL]+/u', ' ', $field));
        }
        elseif ($label !== NULL)
        {
            // Set the label for this field
            $this->labels[$field] = $label;
        }

        return $this;
    }

    /**
     * Converts a filter, rule, or callback into a fully-qualified callback array.
     *
     * @param  mixed   $callback   Valid callback
     * @return  mixed
     */
    protected function callback($callback)
    {
        if (is_string($callback))
        {
            if (strpos($callback, '::') !== FALSE)
            {
                $callback = explode('::', $callback);
            }
            elseif (function_exists($callback))
            {
                // No need to check if the callback is a method
                $callback = $callback;
            }
            elseif (method_exists($this, $callback))
            {
                // The callback exists in Validation
                $callback = array($this, $callback);
            }
            elseif (method_exists('Valid', $callback))
            {
                // The callback exists in Valid::
                $callback = array('Valid', $callback);
            }
        }

        if ( ! is_callable($callback, FALSE))
        {
            if (is_array($callback))
            {
                if (is_object($callback[0]))
                {
                    // Object instance syntax
                    $name = get_class($callback[0]).'->'.$callback[1];
                }
                else
                {
                    // Static class syntax
                    $name = $callback[0].'::'.$callback[1];
                }
            }
            else
            {
                // Function syntax
                $name = $callback;
            }

            exit(sprintf('Callback %s used for Validation is not callable', $name));
        }

        return $callback;
    }


    /**
     * Add an error to an input.
     *
     * ##### Example
     *
     *     $post->add_array('form_field1', 'email_exists');
     *
     *     print_r($post->errors());
     *
     *     // Output: Array ( [form_field1] => email_exists )
     *
     * @chainable
     * @param   string  $field  Input name
     * @param   string  $name   Unique error name
     * @param   string  $args   Arguments to pass to lang file
     * @return  Validation
     */
    public function add_error($field, $name, $args = NULL)
    {
        $this->errors[$field] = array($name, $args);

        return $this;
    }

    /**
     * Rule: required. Generates an error if the field has an empty value.
     *
     * ##### Example
     *
     *     $post->add_rules('form_field1', 'required');
     *
     * @param   mixed   $str  Input value
     * @return  bool
     */
    public function required($str)
    {
        if (is_object($str) && $str instanceof \ArrayObject)
        {
            // Get the array from the ArrayObject
            $str = $str->getArrayCopy();
        }

        if (is_array($str))
        {
            return ! empty($str);
        }
        else
        {
            return ! ($str === '' || $str === NULL || $str === FALSE);
        }
    }

    /**
     * Rule: matches. Generates an error if the field does not match one or more
     * other fields.
     *
     * ##### Example
     *
     *     $post->add_rules('form_field1', 'matches');
     *
     * @param   mixed   $str     Input value
     * @param   array   $inputs  Input names to match against
     * @return  bool
     */
    public function matches($str, array $inputs)
    {
        foreach ($inputs as $key)
        {
            if ($str !== (isset($this[$key]) ? $this[$key] : NULL))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Rule: length. Generates an error if the field is too long or too short.
     *
     * ##### Example
     *
     *     // For a minimum of 1 to a maximum of 5 characters
     *     $post->add_rules('form_field1', 'length[1,5]');
     *
     *     // For an exact length of 5
     *     $post->add_rules('form_field1', 'length[5]');
     *
     * @param   mixed   $str     Input value
     * @param   array   $length  Minimum, maximum, or exact length to match
     * @return  bool
     */
    public function length($str, array $length)
    {
        if ( ! is_string($str))
            return FALSE;

        $size = mb_strlen($str);
        $status = FALSE;

        if (count($length) > 1)
        {
            list ($min, $max) = $length;

            if ($size >= $min AND $size <= $max)
            {
                $status = TRUE;
            }
        }
        else
        {
            $status = ($size === (int) $length[0]);
        }

        return $status;
    }

    /***
     * 中英文混合数字限制
     *
     */
    public function mb_length($str, array $length)
    {
        if ( ! is_string($str))
            return FALSE;

        $tmp_str = preg_replace('/[\x80-\xff]{1,3}/', '', $str, -1, $n);
        $size = $n + mb_strlen($tmp_str);

        $status = FALSE;

        if (count($length) > 1)
        {
            list ($min, $max) = $length;

            if ($size >= $min AND $size <= $max)
            {
                $status = TRUE;
            }
        }
        else
        {
            $status = ($size === (int) $length[0]);
        }

        return $status;
    }

    /**
     * Rule: chars. Generates an error if the field contains characters outside of the list.
     *
     * ##### Example
     *
     *     $post->add_rules('form_field1', 'chars[a,b,c,d]');
     *
     * @param   string  $value  Field value
     * @param   array   $chars  Allowed characters
     * @return  bool
     */
    public function chars($value, array $chars)
    {
        return ! preg_match('![^'.implode('', $chars).']!u', $value);
    }

    /**
     * Rule: integer. Generates an error if the field isn't integer
     *
     * ##### Example
     *
     *     $post->add_rules('form_field1', 'integer');
     *
     * @param   string  $value  Field value
     * @return  bool
     */
    public function integer($value)
    {
        if (preg_match("/^(\-|\+)?\d*$/", $value))
            return TRUE;
        return FALSE;
    }

    /**
     * 验证是否等于某个值('score'=>'eq:100')
     * @param $input
     * @param $rule
     * @return bool
     */
    public function eq($input,$rule)
    {
        if($input == $rule)
            return TRUE;
        return FALSE;
    }

    /**
     * 验证是否大于等于某个值('score'=>'egt:60')
     * @param $input
     * @param $rule
     * @return bool
     */
    public function egt($input,$rule)
    {
        if($input >= $rule)
            return TRUE;
        return FALSE;
    }

    /**
     * 验证是否大于某个('score'=>'gt:60')
     * @param $input
     * @param $rule
     * @return bool
     */
    public function gt($input,$rule)
    {
        if($input > $rule)
            return TRUE;
        return FALSE;
    }

    /**
     * 验证是否小于等于某个值('score'=>'elt:100')
     * @param $input
     * @param $rule
     * @return bool
     */
    public function elt($input,$rule)
    {
        if($input <= $rule)
            return TRUE;
        return FALSE;
    }

    /**
     * 验证是否小于某个值('score'=>'lt:100')
     * @param $input
     * @param $rule
     * @return bool
     */
    public function lt($input,$rule)
    {
        if($input < $rule)
            return TRUE;
        return FALSE;
    }


    /**
     * 验证某个字段的值是否在某个区间('num'=>'between[1,10]')
     * @param $input
     * @param array $rules
     * @return bool
     */
    public function between($input,array $rules)
    {
        if(count($rules) == 2 && $input>=$rules[0] && $input<=$rules[1])
            return TRUE;
        return FALSE;
    }

    /**
     * 验证某个字段的值不在某个范围('num'=>'notBetween[1,10]')
     * @param $input
     * @param array $rules
     * @return bool
     */
    public function notBetween($input,array $rules)
    {
        if(count($rules) == 2 && ($input<$rules[0] || $input>$rules[1]))
            return TRUE;
        return FALSE;
    }

    /**
     * 验证某个字段的值是否在某个范围('num'=>'in[1,2,3]')
     * @param $input
     * @param array $rules
     * @return bool
     */
    public function in($input,array $rules)
    {
        if(count($rules)>0 && in_array($input,$rules))
            return TRUE;
        return FALSE;
    }

    /**
     * 验证某个字段的值不在某个范围('num'=>'notIn[1,2,3]')
     * @param $input
     * @param array $rules
     * @return bool
     */
    public function notIn($input,array $rules)
    {
        if(count($rules)>0 && !in_array($input,$rules))
            return TRUE;
        return FALSE;
    }

}