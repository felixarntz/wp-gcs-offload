<?php

class WPGCSO_UnitTestCase extends WP_UnitTestCase {
	protected function core() {
		return WPGCSOffload\App::instance();
	}
}
