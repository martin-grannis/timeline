<?php

namespace ACA\ACF\Field;

use ACA\ACF\Editing;
use ACA\ACF\Field;
use ACA\ACF\Filtering;
use ACA\ACF\Formattable;
use ACA\ACF\Sorting;
use ACP;

class TrueFalse extends Field
	implements Formattable {

	public function get_value( $id ) {
		return $this->format( parent::get_value( $id ) );
	}

	public function format( $value ) {
		return ac_helper()->icon->yes_or_no( '1' == $value );
	}

	public function editing() {
		return new Editing\TrueFalse( $this->column );
	}

	public function filtering() {
		return new Filtering\TrueFalse( $this->column );
	}

	public function sorting() {
		return new Sorting( $this->column );
	}

	public function search() {
		return new ACP\Search\Comparison\Meta\Checkmark( $this->get_meta_key(), $this->get_meta_type() );
	}

}