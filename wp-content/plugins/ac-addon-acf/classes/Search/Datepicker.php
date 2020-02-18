<?php

namespace ACA\ACF\Search;

use ACP\Search\Comparison\Meta;
use ACP\Search\Operators;
use ACP\Search\Value;

class Datepicker extends Meta {

	public function __construct( $meta_key, $type ) {
		$operators = new Operators( array(
			Operators::EQ,
			Operators::GT,
			Operators::LT,
			Operators::BETWEEN,
			Operators::IS_EMPTY,
			Operators::NOT_IS_EMPTY,
		) );

		parent::__construct( $operators, $meta_key, $type, Value::DATE );
	}

	protected function get_meta_query( $operator, Value $value ) {
		$value = new Value(
			$this->format_date( $value->get_value() ),
			Value::INT
		);

		return parent::get_meta_query( $operator, $value );
	}

	/**
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	private function format_date( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->format_date( $item );
			}
		} else {
			$value = date( 'Ymd', strtotime( $value ) );
		}

		return $value;
	}

}