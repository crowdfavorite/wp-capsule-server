<?php //phpcs:disable Files.SideEffects

/**
 * Capsule server entry point.
 *
 * @package capsule-server
 *
 * This file is part of the Capsule Theme for WordPress
 * https://crowdfavorite.com/capsule/
 *
 * Copyright (c) 2020 Crowd Favorite, Ltd. All rights reserved.
 * https://crowdfavorite.com
 *
 * **********************************************************************
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * **********************************************************************
 */

define('CAPSULE_SERVER', true);

$body_classes = array( 'capsule-server' );

require 'ui/index.php';
