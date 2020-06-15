<?php

/*

	Plugin Name: Basic LTI Plutgin
	Plugin URI: https://sourceforge.net/projects/learningapps/
	Plugin Description: Allows users to log in Q2A  using BasicLTI
	Plugin Version: 1.0
	Plugin Date: 2011-09-28
	Plugin Author: Antoni Bertran
	Plugin Author URI: http://www.uoc.edu/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.4

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('login', 'qa-basic-lti.php', 'qa_basic_lti', 'Basic LTI Plugin');

/*
	Omit PHP closing tag to help avoid accidental output
*/