<?php

namespace DeliciousBrains\WPAutoLogin\Model;

use WeDevs\ORM\Eloquent\Model;

class AutoLoginKey extends Model {

	/**
	 * Name for table without prefix
	 *
	 * @var string
	 */
	protected $table = 'dbrns_auto_login_keys';

	/**
	 * @var array
	 */
	protected $fillable = [
		'login_key',
		'user_id',
		'created',
		'expires',
	];

	/**
	 * @var array
	 */
	protected $guarded = [
		'id',
	];

	/**
	 * @var bool
	 */
	public $timestamps = false;

	public function __construct( array $attributes = array() ) {
		$defaults = [
			'created' => gmdate( 'Y-m-d H:i:s' ),
		];

		$this->setRawAttributes( $defaults, true );

		parent::__construct( $attributes );
	}

	/**
	 * Overide parent method to make sure prefixing is correct.
	 *
	 * @return string
	 */
	public function getTable() {
		if ( isset( $this->table ) ) {
			$prefix = $this->getConnection()->db->prefix;

			return $prefix . $this->table;

		}

		return parent::getTable();
	}
}