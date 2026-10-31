<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
* ------------------------------------------------------
*  Class Controller
* ------------------------------------------------------
 */
class Controller
{
	/**
	 * Controller Instance
	 *
	 * @var object
	 */
	private static $instance;
	/**
	 * Load class
	 *
	 * @var object
	 */
	public $call;

	/**
	 * Dynamic Properties using __set and __get
	 *
	 * @var array
	 */
	public $properties = [];

	/**
	 * Set Dynamic Properties
	 *
	 * @param string $prop
	 * @param string $val
	 */
	public function __set($prop, $val) {
		$this->properties[$prop] = $val;
	}

	/**
	 * Get Dynamic Properties
	 *
	 * @param string $prop
	 * @return void
	 */
	public function __get($prop) {
		if (array_key_exists($prop, $this->properties)) {
			return $this->properties[$prop];
		} else {
			throw new Exception("Undefined property $prop in class " . get_class($this));
		}
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{
		self::$instance = $this;

		foreach (loaded_class() as $var => $class)
		{
			$this->properties[$var] = load_class($class);
		}

		$this->call = load_class('invoker', 'kernel');
		$this->call->initialize();
		$this->before_action();
	}

	/**
     * Called before the controller action.
     * Used to perform logic that needs to happen before each controller action.
     *
     */
    public function before_action(){}

	/**
	 * Instance of controller
	 *
	 * @return object
	 */
	public static function instance()
	{
		return self::$instance;
	}

	/**
	 * Laravel-style compatibility: authorize()
	 * 
	 * @param string $role
	 * @return void
	 * @throws Exception
	 */
	public function authorize($role)
	{
		$user = function_exists('auth') ? auth()->user() : null;
		if (!$user || (($user['role'] ?? null) !== $role)) {
			$response = load_class('Response', 'kernel');
			$response->set_status_code(403);
			$response->set_json_content([
				'status' => 'error',
				'message' => 'Forbidden',
			]);
			$response->send();
			exit;
		}
	}

	public function authorizeAnyRole(array $roles)
	{
		$user = function_exists('auth') ? auth()->user() : null;
		if (!$user || !in_array($user['role'] ?? null, $roles, true)) {
			$response = load_class('Response', 'kernel');
			$response->set_status_code(403);
			$response->set_json_content([
				'status' => 'error',
				'message' => 'Forbidden',
			]);
			$response->send();
			exit;
		}
	}

	/**
	 * Laravel-style compatibility: validate()
	 * 
	 * @param array $rules
	 * @return void
	 * @throws Exception
	 */
	public function validate(array $rules)
	{
		$fv = load_class('Form_validation', 'libraries');
		foreach ($rules as $field => $ruleString) {
			$ruleParts = explode('|', $ruleString);
			foreach ($ruleParts as $rulePart) {
				if (strpos($rulePart, ':') !== false) {
					[$rule, $param] = explode(':', $rulePart, 2);
					if ($rule === 'min') {
						$fv->name($field)->min_length((int)$param);
					} elseif ($rule === 'max') {
						$fv->name($field)->max_length((int)$param);
					} elseif ($rule === 'in') {
						$params = explode(',', $param);
						$fv->name($field)->in_list($params);
					}
				} else {
					if ($rulePart === 'required') {
						$fv->name($field)->required();
					} elseif ($rulePart === 'email') {
						$fv->name($field)->valid_email();
					}
				}
			}
		}

		if (!empty($fv->errors)) {
			throw new Exception('Validation failed', 400);
		}
	}

	/**
	 * Laravel-style compatibility: request()
	 * 
	 * @return object
	 */
	public function request()
	{
		return load_class('Request', 'kernel');
	}

	/**
	 * Laravel-style compatibility: response() helper
	 * 
	 * @return object
	 */
	public function response()
	{
		return new class {
			public function json($data, $status = 200) {
				$res = load_class('Response', 'kernel');
				$res->set_status_code($status);
				$res->set_json_content($data);
				$res->send();
				exit;
			}
		};
	}
}

?>