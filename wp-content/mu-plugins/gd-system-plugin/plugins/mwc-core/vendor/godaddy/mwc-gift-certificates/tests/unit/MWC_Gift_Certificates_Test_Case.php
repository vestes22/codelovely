<?php

use Codeception\Test\Unit;

/**
 * PDF Product Vouchers Unit test case.
 */
class MWC_Gift_Certificates_Test_Case extends Unit {


	/**
	 * Gets a reflection of an inaccessible method.
	 *
	 * @param string|object $class
	 * @param string $method
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	public function get_inaccessible_method( $class, string $method ) : ReflectionMethod {

		$reflection = new ReflectionMethod( $class, $method );

		$reflection->setAccessible( true );

		return $reflection;
	}


	/**
	 * Gets the reflection of an inaccessible property.
	 *
	 * @param string|object $class
	 * @param string $property
	 * @return ReflectionProperty
	 * @throws ReflectionException
	 */
	public function get_inaccessible_property( $class, string $property ) : ReflectionProperty {

		$reflection = new ReflectionProperty( $class, $property );

		$reflection->setAccessible( true );

		return $reflection;
	}


}
