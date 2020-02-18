<?php

namespace ACA\ACF\Field;

use AC;
use AC\Collection;
use ACA\ACF\Editing;
use ACA\ACF\Field;
use ACA\ACF\Filtering;
use ACA\ACF\Search;
use ACP;

class User extends Field {

	public function get_value( $id ) {
		return $this->column->get_formatted_value( new Collection( $this->get_raw_value( $id ) ) );
	}

	public function get_raw_value( $id ) {
		return array_filter( (array) parent::get_raw_value( $id ) );
	}

	public function get_dependent_settings() {
		return array(
			new AC\Settings\Column\User( $this->column ),
		);
	}

	public function editing() {
		return new Editing\User( $this->column );
	}

	public function filtering() {
		return new Filtering\User( $this->column );
	}

	public function sorting() {
		return new ACP\Sorting\Model\Value( $this->column );
	}

	public function search() {
		if ( $this->is_serialized() ) {
			return new Search\Users( $this->get_meta_key(), $this->get_meta_type() );
		}

		return new Search\User( $this->get_meta_key(), $this->get_meta_type() );
	}

	public function export() {
		return new ACP\Export\Model\StrippedValue( $this->column );
	}

}